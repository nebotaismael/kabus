<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class AntiPhishingService
{
    /**
     * Session key for storing rate limiting attempts
     */
    private const ATTEMPTS_SESSION_KEY = 'anti_phishing_attempts';

    /**
     * Generate a new address challenge
     * 
     * Randomly selects positions to mask based on the configured difficulty level.
     * Returns the masked address, positions, expected characters, and expiration time.
     * 
     * @param string|null $specificAddress Optional specific address to use for the challenge
     * @return array{masked_address: string, positions: array<int>, expected: array<string>, expires_at: \Carbon\Carbon, original_address: string}
     */
    public function generateChallenge(?string $specificAddress = null): array
    {
        $onionAddress = $specificAddress ?? $this->getCurrentOnionAddress();
        $difficulty = $this->getDifficulty();
        $timeLimit = $this->getTimeLimit();

        // Get the base address without .onion suffix for masking
        // Only mask characters in the 56-character hash portion
        $addressWithoutSuffix = str_replace('.onion', '', $onionAddress);
        $addressLength = strlen($addressWithoutSuffix);

        // Ensure difficulty doesn't exceed available characters
        $difficulty = min($difficulty, $addressLength);

        // Randomly select positions to mask
        $allPositions = range(0, $addressLength - 1);
        shuffle($allPositions);
        $positions = array_slice($allPositions, 0, $difficulty);
        sort($positions); // Sort for consistent display order

        // Extract expected characters at those positions
        $expected = [];
        foreach ($positions as $position) {
            $expected[] = $addressWithoutSuffix[$position];
        }

        // Create masked address
        $maskedAddress = $addressWithoutSuffix;
        foreach ($positions as $position) {
            $maskedAddress[$position] = '*';
        }
        $maskedAddress .= '.onion';

        // Calculate expiration time
        $expiresAt = Carbon::now()->addMinutes($timeLimit);

        return [
            'masked_address' => $maskedAddress,
            'positions' => $positions,
            'expected' => $expected,
            'expires_at' => $expiresAt,
            'original_address' => $onionAddress,
        ];
    }

    /**
     * Validate user's answer against the challenge
     * 
     * Performs case-insensitive comparison of user input against expected characters.
     * 
     * @param array<string> $userAnswer Array of characters entered by user
     * @param array<int> $positions Array of masked positions (for validation)
     * @param array<string> $expected Array of expected characters
     * @return bool True if all characters match (case-insensitive), false otherwise
     */
    public function validateChallenge(array $userAnswer, array $positions, array $expected): bool
    {
        // Ensure arrays have the same length
        if (count($userAnswer) !== count($expected)) {
            return false;
        }

        // Compare each character case-insensitively
        foreach ($userAnswer as $index => $char) {
            if (!isset($expected[$index])) {
                return false;
            }

            // Case-insensitive comparison
            if (strtolower($char) !== strtolower($expected[$index])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if anti-phishing is enabled
     * 
     * @return bool True if the anti-phishing feature is enabled
     */
    public function isEnabled(): bool
    {
        return (bool) config('antiphishing.enabled', true);
    }

    /**
     * Get all configured onion addresses
     * 
     * @return array<string> Array of configured .onion addresses
     */
    public function getOnionAddresses(): array
    {
        // First check database for admin-configured addresses
        if (class_exists(\App\Models\AntiPhishingSetting::class)) {
            $setting = \App\Models\AntiPhishingSetting::where('key', 'onion_addresses')->first();
            if ($setting && !empty($setting->value)) {
                $addresses = json_decode($setting->value, true);
                if (is_array($addresses) && !empty($addresses)) {
                    return array_values(array_filter($addresses, fn($addr) => !empty($addr)));
                }
            }
        }
        
        // Fall back to config
        $addresses = config('antiphishing.onion_addresses', []);
        
        // Filter out empty addresses
        $addresses = array_filter($addresses, fn($addr) => !empty($addr));
        
        // If no addresses configured in array, fall back to legacy single address
        if (empty($addresses)) {
            $legacyAddress = config('antiphishing.onion_address', '');
            if (!empty($legacyAddress)) {
                $addresses = [$legacyAddress];
            }
        }
        
        return array_values($addresses);
    }

    /**
     * Get the current onion address based on the request host
     * 
     * Detects which of the configured addresses the user is accessing
     * and returns that address. Falls back to the first configured address.
     * 
     * @return string The current .onion address
     */
    public function getCurrentOnionAddress(): string
    {
        $addresses = $this->getOnionAddresses();
        
        if (empty($addresses)) {
            return '';
        }
        
        // Get the current host from the request
        $currentHost = request()->getHost();
        
        // Check if current host matches any configured address
        foreach ($addresses as $address) {
            if (strcasecmp($currentHost, $address) === 0) {
                return $address;
            }
        }
        
        // Fall back to first address if no match (e.g., localhost development)
        return $addresses[0];
    }

    /**
     * Get the official onion address (legacy method for backwards compatibility)
     * 
     * @return string The first configured .onion address
     * @deprecated Use getOnionAddresses() or getCurrentOnionAddress() instead
     */
    public function getOnionAddress(): string
    {
        return $this->getCurrentOnionAddress();
    }

    /**
     * Check if an address is one of the configured official addresses
     * 
     * @param string $address The address to check
     * @return bool True if the address is official
     */
    public function isOfficialAddress(string $address): bool
    {
        $addresses = $this->getOnionAddresses();
        
        foreach ($addresses as $official) {
            if (strcasecmp($address, $official) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get challenge difficulty (number of masked characters)
     * 
     * @return int Number of characters to mask (2-8)
     */
    public function getDifficulty(): int
    {
        $difficulty = (int) config('antiphishing.difficulty', 4);
        
        // Ensure difficulty is within valid range
        return max(2, min(8, $difficulty));
    }

    /**
     * Get challenge time limit in minutes
     * 
     * @return int Time limit in minutes (1-10)
     */
    public function getTimeLimit(): int
    {
        $timeLimit = (int) config('antiphishing.time_limit', 5);
        
        // Ensure time limit is within valid range
        return max(1, min(10, $timeLimit));
    }

    /**
     * Check if user has exceeded rate limit
     * 
     * Checks if the session has exceeded the maximum number of failed attempts
     * within the lockout window.
     * 
     * @param string $sessionId The session identifier
     * @return bool True if rate limited, false otherwise
     */
    public function isRateLimited(string $sessionId): bool
    {
        $attempts = Session::get(self::ATTEMPTS_SESSION_KEY);

        if (!$attempts) {
            return false;
        }

        $maxAttempts = (int) config('antiphishing.max_attempts', 5);
        $lockoutMinutes = (int) config('antiphishing.lockout_minutes', 10);

        // Check if we have attempts recorded
        if (!isset($attempts['count']) || !isset($attempts['first_attempt_at'])) {
            return false;
        }

        $firstAttemptAt = Carbon::parse($attempts['first_attempt_at']);
        $lockoutExpiresAt = $firstAttemptAt->copy()->addMinutes($lockoutMinutes);

        // If lockout period has expired, reset and allow
        if (Carbon::now()->greaterThan($lockoutExpiresAt)) {
            Session::forget(self::ATTEMPTS_SESSION_KEY);
            return false;
        }

        // Check if attempts exceed maximum
        return $attempts['count'] >= $maxAttempts;
    }

    /**
     * Record a challenge attempt
     * 
     * Increments the attempt counter for rate limiting purposes.
     * Resets the counter if the lockout window has expired.
     * 
     * @param string $sessionId The session identifier
     * @return void
     */
    public function recordAttempt(string $sessionId): void
    {
        $attempts = Session::get(self::ATTEMPTS_SESSION_KEY);
        $lockoutMinutes = (int) config('antiphishing.lockout_minutes', 10);

        if (!$attempts) {
            // First attempt
            Session::put(self::ATTEMPTS_SESSION_KEY, [
                'count' => 1,
                'first_attempt_at' => Carbon::now()->toDateTimeString(),
            ]);
            return;
        }

        $firstAttemptAt = Carbon::parse($attempts['first_attempt_at']);
        $lockoutExpiresAt = $firstAttemptAt->copy()->addMinutes($lockoutMinutes);

        // If lockout period has expired, reset counter
        if (Carbon::now()->greaterThan($lockoutExpiresAt)) {
            Session::put(self::ATTEMPTS_SESSION_KEY, [
                'count' => 1,
                'first_attempt_at' => Carbon::now()->toDateTimeString(),
            ]);
            return;
        }

        // Increment counter
        Session::put(self::ATTEMPTS_SESSION_KEY, [
            'count' => $attempts['count'] + 1,
            'first_attempt_at' => $attempts['first_attempt_at'],
        ]);
    }

    /**
     * Clear rate limiting attempts
     * 
     * Resets the attempt counter, typically called after successful verification.
     * 
     * @return void
     */
    public function clearAttempts(): void
    {
        Session::forget(self::ATTEMPTS_SESSION_KEY);
    }

    /**
     * Get remaining lockout time in minutes
     * 
     * @param string $sessionId The session identifier
     * @return int Remaining minutes until lockout expires, 0 if not locked out
     */
    public function getRemainingLockoutMinutes(string $sessionId): int
    {
        $attempts = Session::get(self::ATTEMPTS_SESSION_KEY);

        if (!$attempts || !$this->isRateLimited($sessionId)) {
            return 0;
        }

        $lockoutMinutes = (int) config('antiphishing.lockout_minutes', 10);
        $firstAttemptAt = Carbon::parse($attempts['first_attempt_at']);
        $lockoutExpiresAt = $firstAttemptAt->copy()->addMinutes($lockoutMinutes);

        $remainingMinutes = Carbon::now()->diffInMinutes($lockoutExpiresAt, false);

        return max(0, (int) ceil($remainingMinutes));
    }
}
