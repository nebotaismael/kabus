<?php

namespace App\Http\Controllers;

use App\Services\AntiPhishingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AntiPhishingController extends Controller
{
    /**
     * Session keys for anti-phishing data
     */
    private const CHALLENGE_SESSION_KEY = 'anti_phishing_challenge';
    private const VERIFIED_SESSION_KEY = 'anti_phishing_verified';
    private const VERIFIED_AT_SESSION_KEY = 'anti_phishing_verified_at';
    private const VERIFICATION_TOKEN_KEY = 'anti_phishing_verification_token';

    /**
     * Length of the cryptographically secure verification token
     * Requirements: 5.2 - Must be at least 32 characters (using 64 for extra security)
     */
    private const VERIFICATION_TOKEN_LENGTH = 64;

    public function __construct(
        private readonly AntiPhishingService $antiPhishingService
    ) {}

    /**
     * Show the address verification challenge page
     * GET /anti-phishing/challenge
     * 
     * Requirements: 1.1, 1.7
     */
    public function showChallenge(): View|RedirectResponse
    {
        // If anti-phishing is disabled, redirect to login
        if (!$this->antiPhishingService->isEnabled()) {
            return redirect()->route('login');
        }

        // If already verified, redirect to login
        if ($this->isVerified()) {
            return redirect()->route('login');
        }

        // Check rate limiting
        $sessionId = session()->getId();
        if ($this->antiPhishingService->isRateLimited($sessionId)) {
            $remainingMinutes = $this->antiPhishingService->getRemainingLockoutMinutes($sessionId);
            return view('auth.anti-phishing-challenge', [
                'rateLimited' => true,
                'remainingMinutes' => $remainingMinutes,
                'maskedAddress' => null,
                'positions' => [],
                'expiresAt' => null,
                'timeRemaining' => 0,
            ]);
        }

        // Generate a new challenge
        $challenge = $this->antiPhishingService->generateChallenge();

        // Store challenge data in session (server-side only, not exposed to client)
        session()->put(self::CHALLENGE_SESSION_KEY, [
            'positions' => $challenge['positions'],
            'expected' => $challenge['expected'],
            'expires_at' => $challenge['expires_at']->toDateTimeString(),
            'created_at' => Carbon::now()->toDateTimeString(),
            'address' => $challenge['original_address'],
        ]);

        // Calculate time remaining in seconds for display
        $timeRemaining = Carbon::now()->diffInSeconds($challenge['expires_at'], false);
        $timeRemaining = max(0, $timeRemaining);

        return view('auth.anti-phishing-challenge', [
            'rateLimited' => false,
            'remainingMinutes' => 0,
            'maskedAddress' => $challenge['masked_address'],
            'positions' => $challenge['positions'],
            'expiresAt' => $challenge['expires_at']->toDateTimeString(),
            'timeRemaining' => $timeRemaining,
        ]);
    }

    /**
     * Verify the user's challenge answer
     * POST /anti-phishing/verify
     * 
     * Requirements: 1.3, 1.4, 1.7, 3.5, 5.6
     */
    public function verifyChallenge(Request $request): RedirectResponse
    {
        // If anti-phishing is disabled, redirect to login
        if (!$this->antiPhishingService->isEnabled()) {
            return redirect()->route('login');
        }

        // Check rate limiting first
        $sessionId = session()->getId();
        if ($this->antiPhishingService->isRateLimited($sessionId)) {
            return redirect()->route('anti-phishing.challenge')
                ->with('error', 'Too many failed attempts. Please wait before trying again.');
        }

        // Get challenge data from session
        $challengeData = session()->get(self::CHALLENGE_SESSION_KEY);

        // If no challenge exists, redirect to generate a new one
        if (!$challengeData) {
            return redirect()->route('anti-phishing.challenge')
                ->with('error', 'Session invalid. Please start a new verification.');
        }

        // Check if challenge has expired (Requirement 1.5, 1.6)
        $expiresAt = Carbon::parse($challengeData['expires_at']);
        if (Carbon::now()->greaterThan($expiresAt)) {
            // Clear expired challenge
            session()->forget(self::CHALLENGE_SESSION_KEY);
            return redirect()->route('anti-phishing.challenge')
                ->with('error', 'Challenge expired. Please complete a new verification.');
        }

        // Get user input - expecting an array of characters
        $userAnswer = $request->input('characters', []);

        // Ensure userAnswer is an array
        if (!\is_array($userAnswer)) {
            $userAnswer = [];
        }

        // Clean up user input (trim whitespace from each character)
        $userAnswer = array_map(fn($char) => trim((string) $char), $userAnswer);

        // Validate the challenge (case-insensitive per Requirement 1.3)
        $isValid = $this->antiPhishingService->validateChallenge(
            $userAnswer,
            $challengeData['positions'],
            $challengeData['expected']
        );

        if ($isValid) {
            // Success! Generate cryptographically secure verification token (Requirement 5.2)
            // Using Str::random(64) which uses random_bytes() internally for cryptographic security
            $verificationToken = Str::random(self::VERIFICATION_TOKEN_LENGTH);
            
            // Store verification token in session (Requirements 1.7, 5.2, 5.3)
            session()->put(self::VERIFICATION_TOKEN_KEY, $verificationToken);
            session()->put(self::VERIFIED_SESSION_KEY, true);
            session()->put(self::VERIFIED_AT_SESSION_KEY, Carbon::now()->toDateTimeString());

            // Clear the challenge data
            session()->forget(self::CHALLENGE_SESSION_KEY);

            // Clear rate limiting attempts on success
            $this->antiPhishingService->clearAttempts();

            // Redirect to login form (Requirement 3.5)
            return redirect()->route('login')
                ->with('success', 'Verification successful. You may now log in.');
        }

        // Failed verification - record attempt for rate limiting (Requirement 5.6)
        $this->antiPhishingService->recordAttempt($sessionId);

        // Clear the current challenge (Requirement 1.4 - generate new challenge on failure)
        session()->forget(self::CHALLENGE_SESSION_KEY);

        // Redirect back to challenge page with error (new challenge will be generated)
        return redirect()->route('anti-phishing.challenge')
            ->with('error', 'Incorrect characters. Please try again with a new challenge.');
    }

    /**
     * Check if the current session has a valid verification token
     * 
     * Requirements: 5.2, 5.3 - Validates that a cryptographically secure token exists
     */
    private function isVerified(): bool
    {
        // Check if verification token exists and is valid
        $token = session()->get(self::VERIFICATION_TOKEN_KEY);
        
        if (!$token || !\is_string($token) || \strlen($token) < 32) {
            return false;
        }
        
        // Also check the verified flag for backwards compatibility
        return session()->get(self::VERIFIED_SESSION_KEY, false) === true;
    }
}
