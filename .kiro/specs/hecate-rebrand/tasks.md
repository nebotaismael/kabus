# Implementation Plan: Hecate Market Rebrand

## Overview

This implementation plan transforms the Kabus marketplace into Hecate Market through configuration updates, CSS modifications, view changes, and new page creation. Tasks are ordered to build incrementally, with styling changes first, then view modifications, and finally new features.

## Tasks

- [x] 1. Update global configuration
  - [x] 1.1 Update .env and .env.example APP_NAME to "Hecate Market"
    - Change `APP_NAME=Kabus` to `APP_NAME="Hecate Market"`
    - _Requirements: 1.1, 1.2, 1.3_
  - [x] 1.2 Update config/app.php default name fallback
    - Change default from 'Laravel' to 'Hecate Market'
    - _Requirements: 1.3_

- [x] 2. Migrate CSS colors in styles.css
  - [x] 2.1 Replace all primary purple (#bb86fc) with red (#d32f2f)
    - Find and replace all instances of #bb86fc with #d32f2f
    - _Requirements: 2.1, 2.3, 2.4, 2.5, 2.6_
  - [x] 2.2 Replace all hover purple (#96c) with dark red (#b71c1c)
    - Find and replace all instances of #96c with #b71c1c
    - _Requirements: 2.2_
  - [ ]* 2.3 Write property test for CSS color migration in styles.css
    - **Property 1: CSS Primary Color Migration**
    - **Property 2: CSS Hover Color Migration**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6**

- [x] 3. Migrate CSS colors in auth.css
  - [x] 3.1 Replace all primary purple (#bb86fc) with red (#d32f2f)
    - Find and replace all instances of #bb86fc with #d32f2f
    - _Requirements: 2.1_
  - [x] 3.2 Replace all hover purple (#96c) with dark red (#b71c1c)
    - Find and replace all instances of #96c with #b71c1c
    - _Requirements: 2.2_

- [x] 4. Update typography in CSS files
  - [x] 4.1 Update font-family in styles.css
    - Change `font-family:"Arial",sans-serif` to `font-family:"Verdana","Geneva",sans-serif`
    - _Requirements: 3.1_
  - [x] 4.2 Update font-family in auth.css
    - Change `font-family:"Arial",sans-serif` to `font-family:"Verdana","Geneva",sans-serif`
    - _Requirements: 3.1_
  - [ ]* 4.3 Write property test for font migration
    - **Property 3: Font Family Migration**
    - **Validates: Requirements 3.1**

- [x] 5. Checkpoint - Verify CSS changes
  - Ensure all color and font changes are applied correctly
  - Verify no purple colors remain in CSS files
  - Ask the user if questions arise

- [x] 6. Redesign authentication pages
  - [x] 6.1 Add new CSS classes for auth redesign in auth.css
    - Add .hecate-login-box with thick red border and black background
    - Add .hecate-security-header for security check visual
    - Add .hecate-input-minimal for transparent inputs with bottom border
    - Add .hecate-captcha-terminal for terminal-style captcha
    - _Requirements: 4.1, 4.3, 4.5_
  - [x] 6.2 Update login.blade.php with new design
    - Wrap form in .hecate-login-box container
    - Add "Security Check" header above form
    - Apply .hecate-input-minimal to input fields
    - _Requirements: 4.1, 4.2, 4.3_
  - [x] 6.3 Update register.blade.php with new design
    - Mirror login page design changes
    - Apply .hecate-captcha-terminal to captcha wrapper
    - _Requirements: 4.4, 4.5_

- [x] 7. Update dashboard layout
  - [x] 7.1 Add status ticker CSS to styles.css
    - Create .hecate-status-ticker class for top status bar
    - _Requirements: 5.1_
  - [x] 7.2 Add status ticker to layouts/app.blade.php
    - Add "Hecate Market Status: Online" ticker after navbar
    - _Requirements: 5.1_
  - [x] 7.3 Update dashboard card styling in styles.css
    - Add red glow box-shadow to .dashboard-card
    - _Requirements: 5.3_
  - [x] 7.4 Reorganize dashboard.blade.php layout
    - Move Profile Information Card to horizontal banner at top
    - Update .dashboard-grid to single column layout with banner
    - _Requirements: 5.2_

- [x] 8. Update admin panel
  - [x] 8.1 Add important card styling to styles.css
    - Create .a-v-panel-item-important class with grid-column: span 2
    - _Requirements: 6.1_
  - [x] 8.2 Update admin/index.blade.php
    - Add .a-v-panel-item-important class to User Management card
    - Add .a-v-panel-item-important class to Disputes card
    - _Requirements: 6.1, 6.2_

- [x] 9. Update vendor panel
  - [x] 9.1 Rename vendor panel title
    - Change "Vendor Panel" to "Hecate Merchant Command" in vendor/index.blade.php
    - _Requirements: 7.1_
  - [x] 9.2 Add product management section CSS to styles.css
    - Create .hecate-product-management-section class
    - Create .hecate-product-actions class for grouped actions
    - _Requirements: 7.2_
  - [x] 9.3 Reorganize vendor/index.blade.php product actions
    - Group Digital, Cargo, Dead Drop into single "Product Management" section
    - _Requirements: 7.2_

- [x] 10. Checkpoint - Verify view changes
  - Ensure all dashboard, admin, and vendor panel changes render correctly
  - Ask the user if questions arise

- [x] 11. Create new static pages
  - [x] 11.1 Create disclaimer.blade.php
    - Extend layouts.app
    - Add standard market disclaimer content
    - _Requirements: 8.4_
  - [x] 11.2 Create refund-policy.blade.php
    - Extend layouts.app
    - Add refund terms content
    - _Requirements: 8.5_
  - [x] 11.3 Add routes for new pages in routes/web.php
    - Add Route::view('/disclaimer', 'disclaimer')->name('disclaimer')
    - Add Route::view('/refund-policy', 'refund-policy')->name('refund-policy')
    - _Requirements: 8.2, 8.3_

- [x] 12. Update footer component
  - [x] 12.1 Add Disclaimer and Refund Policy links to footer.blade.php
    - Add link to disclaimer route
    - Add link to refund-policy route
    - _Requirements: 8.1_

- [x] 13. Update branding assets
  - [x] 13.1 Update navbar logo reference
    - Change kabus.png to hecate.png in navbar.blade.php
    - _Requirements: 9.1_
  - [x] 13.2 Update favicon reference
    - Change kabus.png to hecate.png in layouts/app.blade.php
    - _Requirements: 9.2_
  - [x] 13.3 Add placeholder for new logo file
    - Note: User must provide actual hecate.png image file
    - _Requirements: 9.1, 9.2, 9.3_

- [x] 14. Final checkpoint - Verify complete rebrand
  - Ensure all configuration, styling, and view changes are complete
  - Verify new routes work correctly
  - Verify no references to "Kabus" remain in user-facing content
  - Ask the user if questions arise

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate CSS migration correctness
- User must provide actual hecate.png logo file for task 13.3
