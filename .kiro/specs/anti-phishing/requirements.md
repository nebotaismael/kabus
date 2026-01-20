# Requirements Document

## Introduction

This document specifies the requirements for an anti-phishing protection system for Hecate Market. The system provides multiple layers of protection to help users verify they are on the legitimate marketplace and not a phishing clone. The feature includes an address verification challenge on the login page, user-configurable anti-phishing phrases displayed after login, and admin configuration options.

## Glossary

- **Anti_Phishing_System**: The complete system responsible for protecting users from phishing attacks through address verification and personal phrase display
- **Address_Challenge**: A security challenge that masks random characters in the official .onion address and requires users to type the missing characters
- **Anti_Phishing_Phrase**: A user-defined secret phrase displayed on every page after login to verify site authenticity
- **Challenge_Session**: A time-limited session storing the masked positions and expected characters for address verification
- **Masked_Character**: A character in the .onion address replaced with asterisks (***) that the user must identify
- **Admin_Panel**: The administrative interface for configuring anti-phishing settings
- **Onion_Address**: The official .onion URL of the marketplace stored in configuration

## Requirements

### Requirement 1: Address Verification Challenge

**User Story:** As a user, I want to verify I'm on the real marketplace by typing missing characters from the official .onion address, so that I can detect phishing sites that don't know the real address.

#### Acceptance Criteria

1. WHEN a user visits the login page AND anti-phishing is enabled, THE Address_Challenge SHALL display the official .onion address with random characters replaced by asterisks
2. WHEN generating a challenge, THE Anti_Phishing_System SHALL randomly select characters to mask based on the configured difficulty level (number of masked characters)
3. WHEN a user submits the challenge form, THE Anti_Phishing_System SHALL validate that all entered characters match the masked positions exactly (case-insensitive)
4. IF a user enters incorrect characters, THEN THE Anti_Phishing_System SHALL display an error message and generate a new challenge
5. WHEN a challenge is created, THE Challenge_Session SHALL expire after the configured time limit (default 5 minutes)
6. IF a challenge session expires, THEN THE Anti_Phishing_System SHALL require the user to complete a new challenge
7. WHEN a user successfully completes the challenge, THE Anti_Phishing_System SHALL store a verification token in the session allowing access to the login form
8. THE Address_Challenge SHALL display a countdown timer showing remaining time for the challenge

### Requirement 2: User Anti-Phishing Phrase

**User Story:** As a user, I want to set a personal anti-phishing phrase that displays on every page after login, so that I can verify I'm on the real site (phishing sites won't know my phrase).

#### Acceptance Criteria

1. WHEN a logged-in user has set an anti-phishing phrase, THE Anti_Phishing_System SHALL display the phrase prominently on every authenticated page
2. WHEN a user accesses the settings page without an anti-phishing phrase, THE Anti_Phishing_System SHALL display a form to set a new phrase
3. WHEN a user submits a new anti-phishing phrase, THE Anti_Phishing_System SHALL validate it contains only letters (4-32 characters)
4. WHEN a user has already set an anti-phishing phrase, THE Anti_Phishing_System SHALL allow updating the phrase through settings
5. THE Anti_Phishing_Phrase SHALL be stored securely in the database associated with the user account
6. WHEN displaying the anti-phishing phrase, THE Anti_Phishing_System SHALL show it in a consistent, recognizable location (navbar or header area)

### Requirement 3: Login Page Security Indicators

**User Story:** As a user, I want clear visual indicators on the login page showing the security challenge status, so that I understand the verification process.

#### Acceptance Criteria

1. WHEN displaying the address challenge, THE Anti_Phishing_System SHALL show clear instructions explaining the verification process
2. WHEN the challenge timer is running, THE Anti_Phishing_System SHALL display a visible countdown showing minutes and seconds remaining
3. WHEN the countdown reaches zero, THE Anti_Phishing_System SHALL automatically invalidate the current challenge
4. THE Address_Challenge page SHALL display the marketplace name and security badge for visual authenticity
5. WHEN a challenge is successfully completed, THE Anti_Phishing_System SHALL redirect the user to the actual login form

### Requirement 4: Admin Configuration

**User Story:** As an admin, I want to configure the anti-phishing system settings, so that I can adjust security levels and manage the official address.

#### Acceptance Criteria

1. THE Admin_Panel SHALL provide a settings page for anti-phishing configuration
2. WHEN an admin accesses anti-phishing settings, THE Admin_Panel SHALL display the current official .onion address
3. WHEN an admin updates the .onion address, THE Anti_Phishing_System SHALL validate it matches the .onion format (56 characters + .onion)
4. THE Admin_Panel SHALL allow enabling or disabling the address verification challenge globally
5. THE Admin_Panel SHALL allow configuring the challenge difficulty (number of masked characters, range 2-8)
6. THE Admin_Panel SHALL allow configuring the challenge time limit (range 1-10 minutes)
7. WHEN anti-phishing settings are updated, THE Anti_Phishing_System SHALL log the change with admin ID and timestamp

### Requirement 5: Session and Security

**User Story:** As a system administrator, I want the anti-phishing system to maintain secure session handling, so that the verification cannot be bypassed.

#### Acceptance Criteria

1. THE Challenge_Session SHALL store masked positions and expected characters server-side only (not exposed to client)
2. WHEN a challenge is completed successfully, THE Anti_Phishing_System SHALL generate a cryptographically secure verification token
3. THE verification token SHALL expire after a configurable duration or upon successful login
4. IF a user attempts to access the login form without a valid verification token AND anti-phishing is enabled, THEN THE Anti_Phishing_System SHALL redirect to the address challenge
5. WHEN storing challenge data, THE Anti_Phishing_System SHALL use Laravel's session encryption
6. THE Anti_Phishing_System SHALL rate-limit challenge attempts to prevent brute-force attacks (maximum 5 attempts per 10 minutes)

### Requirement 6: Database and Storage

**User Story:** As a developer, I want the anti-phishing data stored properly in the database, so that user phrases persist and admin settings are maintained.

#### Acceptance Criteria

1. THE Anti_Phishing_System SHALL extend the existing secret_phrases table to support longer phrases (up to 32 characters)
2. THE Anti_Phishing_System SHALL store admin configuration in a dedicated anti_phishing_settings table or config file
3. WHEN a user updates their anti-phishing phrase, THE Anti_Phishing_System SHALL update the existing record rather than creating duplicates
4. THE Anti_Phishing_System SHALL use database transactions when updating settings to ensure data integrity
