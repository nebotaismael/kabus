# Implementation Plan: Anti-Phishing Protection System

## Overview

This implementation plan breaks down the anti-phishing feature into discrete coding tasks. The approach is incremental: first establishing configuration and service layer, then building the challenge flow, followed by phrase display integration, and finally admin configuration. Each task builds on previous work to ensure no orphaned code.

## Tasks

- [x] 1. Set up configuration and database schema
  - [x] 1.1 Create anti-phishing configuration file
    - Create `config/antiphishing.php` with settings for enabled, onion_address, difficulty, time_limit, max_attempts, lockout_minutes
    - Add corresponding environment variables to `.env.example`
    - _Requirements: 4.1, 4.4, 4.5, 4.6, 6.2_
  
  - [x] 1.2 Create database migration for extended phrase length
    - Create migration to modify `secret_phrases` table, changing `phrase` column from varchar(16) to varchar(32)
    - _Requirements: 6.1_
  
  - [x] 1.3 Create database migration for anti-phishing settings table
    - Create `anti_phishing_settings` table with id, key, value, timestamps
    - This allows admin-configurable settings to override config defaults
    - _Requirements: 6.2_

- [x] 2. Implement AntiPhishingService
  - [x] 2.1 Create AntiPhishingService class with core methods
    - Implement `generateChallenge()` - randomly selects positions to mask based on difficulty
    - Implement `validateChallenge()` - case-insensitive comparison of user input
    - Implement `isEnabled()`, `getOnionAddress()`, `getDifficulty()`, `getTimeLimit()`
    - Implement `isRateLimited()` and `recordAttempt()` for rate limiting
    - Register service in AppServiceProvider
    - _Requirements: 1.2, 1.3, 5.6_
  
  - [ ]* 2.2 Write property test for challenge generation (Property 1)
    - **Property 1: Challenge Difficulty Consistency**
    - Test that generated challenges have exactly N masked positions for difficulty N
    - **Validates: Requirements 1.2**
  
  - [ ]* 2.3 Write property test for case-insensitive validation (Property 2)
    - **Property 2: Case-Insensitive Challenge Validation**
    - Test validation passes for any case combination of correct characters
    - **Validates: Requirements 1.3**
  
  - [ ]* 2.4 Write property test for rate limiting (Property 15)
    - **Property 15: Rate Limiting Enforcement**
    - Test that after 5 failed attempts, further attempts are blocked
    - **Validates: Requirements 5.6**

- [x] 3. Checkpoint - Ensure service layer tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement challenge flow controllers and middleware
  - [x] 4.1 Create AntiPhishingController
    - Implement `showChallenge()` - generates challenge, stores in session, renders view
    - Implement `verifyChallenge()` - validates input, handles success/failure, rate limiting
    - Add routes to `routes/web.php` in guest middleware group
    - _Requirements: 1.1, 1.3, 1.4, 1.7, 3.5_
  
  - [x] 4.2 Create AntiPhishingMiddleware
    - Check if anti-phishing is enabled
    - Check for valid verification token in session
    - Redirect to challenge if no valid token
    - Register middleware in bootstrap/app.php
    - _Requirements: 5.4_
  
  - [x] 4.3 Apply middleware to login route
    - Modify `routes/web.php` to apply AntiPhishingMiddleware to login routes
    - Ensure challenge routes are excluded from the middleware
    - _Requirements: 1.1, 5.4_
  
  - [ ]* 4.4 Write property test for challenge expiration (Property 4)
    - **Property 4: Challenge Time-Based Expiration**
    - Test that expired challenges fail validation regardless of input
    - **Validates: Requirements 1.5, 1.6, 3.3**
  
  - [ ]* 4.5 Write property test for middleware enforcement (Property 14)
    - **Property 14: Middleware Enforces Verification**
    - Test that requests without valid token are redirected
    - **Validates: Requirements 5.3, 5.4**

- [x] 5. Create challenge view and styling
  - [x] 5.1 Create anti-phishing challenge Blade view
    - Create `resources/views/auth/anti-phishing-challenge.blade.php`
    - Display masked address with input fields for each masked character
    - Show countdown timer (static display of time remaining)
    - Include marketplace branding and security badge
    - Show clear instructions for the verification process
    - _Requirements: 1.1, 1.8, 3.1, 3.2, 3.4_
  
  - [x] 5.2 Add CSS styles for challenge page
    - Add styles to `public/css/auth.css` for challenge layout
    - Style masked address display, input fields, timer, and error messages
    - Maintain consistency with existing auth page styling
    - _Requirements: 3.1, 3.4_

- [x] 6. Checkpoint - Ensure challenge flow works end-to-end
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Implement anti-phishing phrase display
  - [x] 7.1 Create anti-phishing badge Blade component
    - Create `resources/views/components/anti-phishing-badge.blade.php`
    - Display user's phrase in a styled badge
    - Only render if user has a phrase set
    - _Requirements: 2.1, 2.6_
  
  - [x] 7.2 Integrate badge into navbar component
    - Modify `resources/views/components/navbar.blade.php`
    - Include anti-phishing badge component for authenticated users
    - Position in consistent, recognizable location
    - _Requirements: 2.1, 2.6_
  
  - [x] 7.3 Add CSS styles for anti-phishing badge
    - Add styles to `public/css/styles.css` for the badge display
    - Ensure visibility and recognition without being intrusive
    - _Requirements: 2.6_
  
  - [ ]* 7.4 Write property test for phrase display (Property 6)
    - **Property 6: Anti-Phishing Phrase Display for Authenticated Users**
    - Test that authenticated pages contain the user's phrase
    - **Validates: Requirements 2.1**

- [x] 8. Extend settings for phrase management
  - [x] 8.1 Update SettingsController for phrase updates
    - Modify `updateSecretPhrase()` to allow updates (not just one-time setting)
    - Extend validation to accept 4-32 character phrases
    - Ensure update replaces existing record (no duplicates)
    - _Requirements: 2.3, 2.4, 6.3_
  
  - [x] 8.2 Update settings view for phrase management
    - Modify `resources/views/settings.blade.php`
    - Show current phrase with option to update
    - Update form validation for new length limits
    - _Requirements: 2.2, 2.4_
  
  - [ ]* 8.3 Write property test for phrase validation (Property 7)
    - **Property 7: Phrase Validation Rules**
    - Test that only alphabetic strings 4-32 chars are accepted
    - **Validates: Requirements 2.3**
  
  - [ ]* 8.4 Write property test for phrase storage round-trip (Property 8)
    - **Property 8: Phrase Storage Round-Trip**
    - Test that stored phrases are retrieved exactly as entered
    - **Validates: Requirements 2.5**
  
  - [ ]* 8.5 Write property test for phrase update idempotence (Property 17)
    - **Property 17: Phrase Update Idempotence**
    - Test that multiple updates result in exactly one record
    - **Validates: Requirements 6.3**

- [x] 9. Checkpoint - Ensure phrase features work correctly
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Implement admin configuration panel
  - [x] 10.1 Add admin routes for anti-phishing settings
    - Add routes to `routes/web.php` in admin middleware group
    - Routes for showing and updating anti-phishing settings
    - _Requirements: 4.1_
  
  - [x] 10.2 Extend AdminController with anti-phishing methods
    - Implement `showAntiPhishingSettings()` - display current settings
    - Implement `updateAntiPhishingSettings()` - validate and save settings
    - Include onion address format validation
    - Include difficulty and time limit range validation
    - Log settings changes with admin ID and timestamp
    - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_
  
  - [x] 10.3 Create admin anti-phishing settings view
    - Create `resources/views/admin/anti-phishing.blade.php`
    - Form for onion address, enabled toggle, difficulty, time limit
    - Display current values and validation errors
    - _Requirements: 4.1, 4.2, 4.4, 4.5, 4.6_
  
  - [x] 10.4 Create AntiPhishingSetting model
    - Create model for database-stored settings
    - Implement methods to get/set settings with config fallback
    - _Requirements: 6.2_
  
  - [x] 10.5 Add admin panel navigation link
    - Add link to anti-phishing settings in admin panel index
    - _Requirements: 4.1_
  
  - [ ]* 10.6 Write property test for onion address validation (Property 9)
    - **Property 9: Onion Address Format Validation**
    - Test that only valid v3 onion addresses are accepted
    - **Validates: Requirements 4.3**
  
  - [ ]* 10.7 Write property test for config range validation (Property 10)
    - **Property 10: Admin Configuration Range Validation**
    - Test difficulty accepts only 2-8, time limit accepts only 1-10
    - **Validates: Requirements 4.5, 4.6**
  
  - [ ]* 10.8 Write property test for audit logging (Property 11)
    - **Property 11: Settings Change Audit Logging**
    - Test that settings changes create log entries
    - **Validates: Requirements 4.7**

- [x] 11. Implement security properties
  - [x] 11.1 Ensure challenge data not exposed to client
    - Review challenge view to ensure expected characters are not in HTML
    - Only masked address with asterisks should be visible
    - _Requirements: 5.1_
  
  - [x] 11.2 Implement secure verification token generation
    - Use Laravel's Str::random(64) for token generation
    - Store token with expiration timestamp in session
    - _Requirements: 5.2, 5.3_
  
  - [ ]* 11.3 Write property test for data not exposed (Property 12)
    - **Property 12: Challenge Data Not Exposed to Client**
    - Test that HTML response doesn't contain expected characters
    - **Validates: Requirements 5.1**
  
  - [ ]* 11.4 Write property test for token security (Property 13)
    - **Property 13: Verification Token Security**
    - Test that tokens are at least 32 chars and cryptographically random
    - **Validates: Requirements 5.2**

- [x] 12. Final checkpoint - Full integration testing
  - Ensure all tests pass, ask the user if questions arise.
  - Verify complete flow: challenge → login → phrase display
  - Verify admin can configure all settings
  - Verify rate limiting works correctly

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The implementation uses PHP 8.3 with Laravel 11 conventions
- No JavaScript is used - all functionality is server-side with Blade templates
