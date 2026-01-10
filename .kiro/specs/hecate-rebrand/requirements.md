# Requirements Document

## Introduction

This document defines the requirements for rebranding the "Kabus" marketplace to "Hecate Market". The rebrand involves a complete visual overhaul from Purple to Red theme, name changes throughout the application, font updates, and structural modifications to authentication pages, dashboards, and footer components.

## Glossary

- **Hecate_Market**: The new brand name replacing "Kabus"
- **Primary_Red**: The new primary color (#d32f2f) replacing the purple theme
- **Dark_Red**: The hover/accent color (#b71c1c) for interactive states
- **Auth_System**: The authentication pages including login, register, and related views
- **Dashboard_System**: The user, vendor, and admin dashboard interfaces
- **Footer_Component**: The global footer component with navigation links
- **Navbar_Component**: The global navigation bar with logo and branding

## Requirements

### Requirement 1: Global Configuration Updates

**User Story:** As a site administrator, I want all application configuration to reflect the new "Hecate Market" branding, so that the application identity is consistent across all environments.

#### Acceptance Criteria

1. WHEN the application loads, THE Configuration_System SHALL display "Hecate Market" as the application name
2. WHEN viewing the browser tab, THE Layout_System SHALL display "Hecate Market" in the page title
3. WHEN the .env file is missing APP_NAME, THE Configuration_System SHALL default to "Hecate Market"

### Requirement 2: Color Theme Migration

**User Story:** As a user, I want to see a cohesive red color theme throughout the application, so that the visual identity matches the new Hecate Market brand.

#### Acceptance Criteria

1. WHEN any UI element uses the primary brand color, THE Stylesheet_System SHALL render it as #d32f2f (Hecate Red) instead of #bb86fc (Purple)
2. WHEN hovering over interactive elements, THE Stylesheet_System SHALL use #b71c1c (Dark Red) instead of #96c (Hover Purple)
3. WHEN rendering the navbar border, THE Stylesheet_System SHALL use the Primary_Red color
4. WHEN rendering the footer border, THE Stylesheet_System SHALL use the Primary_Red color
5. WHEN rendering scrollbar thumbs, THE Stylesheet_System SHALL use the Primary_Red color
6. WHEN rendering dashboard card titles, THE Stylesheet_System SHALL use the Primary_Red color

### Requirement 3: Typography Updates

**User Story:** As a user, I want to see a distinctive font throughout the application, so that the visual identity feels unique and professional.

#### Acceptance Criteria

1. WHEN rendering any text content, THE Stylesheet_System SHALL use "Verdana", "Geneva", sans-serif font stack instead of Arial

### Requirement 4: Authentication Page Redesign

**User Story:** As a user, I want the login and registration pages to have a unique, secure-looking design, so that I feel confident in the platform's security.

#### Acceptance Criteria

1. WHEN viewing the login page, THE Auth_System SHALL display the form within a container with thick red border and black background
2. WHEN viewing the login page, THE Auth_System SHALL display a "Security Check" header above the form
3. WHEN viewing input fields on auth pages, THE Auth_System SHALL render them with transparent background and red bottom border only
4. WHEN viewing the register page, THE Auth_System SHALL mirror the login page design styling
5. WHEN viewing the captcha on register page, THE Auth_System SHALL display it with terminal-style appearance (green text, black background, monospace font)

### Requirement 5: Dashboard Layout Modifications

**User Story:** As a user, I want the dashboard to have an updated layout with red theme accents, so that the interface feels modern and matches the new brand.

#### Acceptance Criteria

1. WHEN viewing any dashboard, THE Layout_System SHALL display a "Hecate Market Status: Online" ticker bar at the top
2. WHEN viewing the user dashboard, THE Dashboard_System SHALL display the Profile Information Card as a horizontal banner at the top
3. WHEN viewing dashboard cards, THE Dashboard_System SHALL apply a subtle red glow effect (box-shadow with rgba(211, 47, 47, 0.2))

### Requirement 6: Admin Panel Customization

**User Story:** As an administrator, I want the admin panel to emphasize important management cards, so that I can quickly access critical functions.

#### Acceptance Criteria

1. WHEN viewing the admin panel, THE Admin_System SHALL display "User Management" and "Disputes" cards at double-width
2. WHEN viewing admin panel icons, THE Admin_System SHALL render them in the Primary_Red color

### Requirement 7: Vendor Panel Customization

**User Story:** As a vendor, I want the vendor panel to have a unique identity and organized product management, so that I can efficiently manage my store.

#### Acceptance Criteria

1. WHEN viewing the vendor panel header, THE Vendor_System SHALL display "Hecate Merchant Command" instead of "Vendor Panel"
2. WHEN viewing product actions, THE Vendor_System SHALL group "Add Digital", "Cargo", and "Dead Drop" into a single "Product Management" section

### Requirement 8: Footer Updates and New Pages

**User Story:** As a user, I want access to Disclaimer and Refund Policy pages from the footer, so that I can understand the platform's terms.

#### Acceptance Criteria

1. WHEN viewing the footer, THE Footer_Component SHALL display links to Disclaimer and Refund Policy pages
2. WHEN clicking the Disclaimer link, THE Routing_System SHALL navigate to the disclaimer page
3. WHEN clicking the Refund Policy link, THE Routing_System SHALL navigate to the refund-policy page
4. WHEN viewing the disclaimer page, THE Page_System SHALL display standard market disclaimer content
5. WHEN viewing the refund policy page, THE Page_System SHALL display refund terms content

### Requirement 9: Asset and Image Branding

**User Story:** As a user, I want to see the new Hecate Market logo and branding images throughout the application, so that the visual identity is consistent.

#### Acceptance Criteria

1. WHEN viewing the navbar, THE Navbar_Component SHALL display the new Hecate logo instead of kabus.png
2. WHEN viewing the browser favicon, THE Layout_System SHALL display the new Hecate icon
3. IF the new logo file does not exist, THEN THE Asset_System SHALL gracefully handle the missing asset
