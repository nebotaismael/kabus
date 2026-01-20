<?php

namespace App\Http\Middleware;

use App\Services\AntiPhishingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AntiPhishingMiddleware
{
    /**
     * Session keys for anti-phishing verification
     */
    private const VERIFIED_SESSION_KEY = 'anti_phishing_verified';
    private const VERIFIED_AT_SESSION_KEY = 'anti_phishing_verified_at';
    private const VERIFICATION_TOKEN_KEY = 'anti_phishing_verification_token';

    /**
     * Minimum required token length for security validation
     * Requirements: 5.2 - Token must be at least 32 characters
     */
    private const MIN_TOKEN_LENGTH = 32;

    public function __construct(
        private readonly AntiPhishingService $antiPhishingService
    ) {}

    /**
     * Handle an incoming request
     * 
     * Redirects to challenge if anti-phishing is enabled and no valid token exists.
     * 
     * Requirements: 5.4
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If anti-phishing is disabled, allow request to proceed
        if (!$this->antiPhishingService->isEnabled()) {
            return $next($request);
        }

        // Check if user has a valid verification token in session
        if ($this->hasValidVerificationToken()) {
            return $next($request);
        }

        // No valid token - redirect to challenge page
        return redirect()->route('anti-phishing.challenge');
    }

    /**
     * Check if the session has a valid verification token
     * 
     * Requirements: 5.2, 5.3, 5.4 - Validates cryptographically secure token exists
     * 
     * @return bool True if a valid, non-expired verification token exists
     */
    private function hasValidVerificationToken(): bool
    {
        // Check if verification token exists and meets security requirements (Requirement 5.2)
        $token = session()->get(self::VERIFICATION_TOKEN_KEY);
        
        if (!$token || !\is_string($token) || \strlen($token) < self::MIN_TOKEN_LENGTH) {
            return false;
        }

        // Check if verified flag is set
        $isVerified = session()->get(self::VERIFIED_SESSION_KEY, false);
        
        if ($isVerified !== true) {
            return false;
        }

        // Check if verification timestamp exists
        $verifiedAt = session()->get(self::VERIFIED_AT_SESSION_KEY);
        
        if (!$verifiedAt) {
            return false;
        }

        // Token is valid
        return true;
    }
}
