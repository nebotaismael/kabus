# Design Document: Hecate Market Rebrand

## Overview

This design document outlines the technical approach for rebranding the "Kabus" marketplace to "Hecate Market". The rebrand involves configuration changes, CSS color/typography updates, authentication page redesign, dashboard modifications, and new static pages. The implementation follows a modular approach, updating each component independently while maintaining backward compatibility.

## Architecture

The rebrand affects the following layers of the Laravel application:

```
┌─────────────────────────────────────────────────────────────────┐
│                     Configuration Layer                          │
│  (.env, config/app.php)                                         │
├─────────────────────────────────────────────────────────────────┤
│                     Presentation Layer                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   Layouts   │  │    Views    │  │ Components  │             │
│  │ (app.blade) │  │ (auth/*)    │  │ (navbar,    │             │
│  │             │  │ (dashboard) │  │  footer)    │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
├─────────────────────────────────────────────────────────────────┤
│                      Styling Layer                               │
│  ┌─────────────────────┐  ┌─────────────────────┐              │
│  │   public/css/       │  │   public/images/    │              │
│  │   styles.css        │  │   hecate.png        │              │
│  │   auth.css          │  │   (new logo)        │              │
│  └─────────────────────┘  └─────────────────────┘              │
├─────────────────────────────────────────────────────────────────┤
│                      Routing Layer                               │
│  (routes/web.php - new static page routes)                      │
└─────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Configuration Component

Updates to application identity configuration.

**Files Modified:**
- `.env` - APP_NAME value
- `.env.example` - APP_NAME default
- `config/app.php` - Default fallback name

**Interface:**
```php
// config/app.php
'name' => env('APP_NAME', 'Hecate Market'),
```

### 2. Stylesheet Component

Color and typography transformations across all CSS files.

**Color Mapping:**
| Original (Purple) | New (Red) | Usage |
|-------------------|-----------|-------|
| #bb86fc | #d32f2f | Primary brand color |
| #96c | #b71c1c | Hover/accent states |
| #3c3c3c | #3c3c3c | Borders (unchanged) |

**Typography Change:**
```css
/* Before */
font-family: "Arial", sans-serif;

/* After */
font-family: "Verdana", "Geneva", sans-serif;
```

**Affected Classes (styles.css):**
- `.navbar` (border-bottom)
- `.navbar-icon-btn` (border, background on active)
- `.footer` (border-top)
- `.footer-button` (color, border)
- `.footer-xmr-price` (border, color)
- `.left-bar`, `.right-bar` (border)
- `.dashboard-card-title` (color)
- `.a-v-panel-title` (color)
- `::-webkit-scrollbar-thumb` (background-color)
- `.pagination-active` (background-color)
- All button hover states

**Affected Classes (auth.css):**
- `.auth-login-title`, `.auth-register-title` (color, border)
- `.auth-login-submit-btn`, `.auth-register-submit-btn` (background)
- All link colors and hover states

### 3. Authentication Pages Component

Redesigned login and register pages with security-focused styling.

**New CSS Classes:**
```css
.hecate-login-box {
    border: 3px solid #d32f2f;
    background-color: #000000;
}

.hecate-security-header {
    /* Security check visual header */
}

.hecate-input-minimal {
    background: transparent;
    border: none;
    border-bottom: 2px solid #d32f2f;
}

.hecate-captcha-terminal {
    background-color: #000000;
    color: #00ff00;
    font-family: "Courier New", monospace;
}
```

**Files Modified:**
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `public/css/auth.css`

### 4. Dashboard Component

Layout modifications and red theme integration.

**Status Ticker:**
```html
<div class="hecate-status-ticker">
    Hecate Market Status: Online
</div>
```

**Dashboard Card Glow Effect:**
```css
.dashboard-card {
    box-shadow: 0 0 15px rgba(211, 47, 47, 0.2);
}
```

**Profile Card Repositioning:**
- Move from left column to horizontal banner at top
- Modify `.dashboard-grid` from `1fr 2fr` to single column with banner

### 5. Admin Panel Component

Enhanced layout with emphasized critical cards.

**Grid Modification:**
```css
.a-v-panel-item.a-v-panel-item-important {
    grid-column: span 2;
}
```

**Important Cards:**
- User Management
- Disputes

### 6. Vendor Panel Component

Renamed and reorganized interface.

**Title Change:**
- "Vendor Panel" → "Hecate Merchant Command"

**Product Management Grouping:**
```html
<div class="hecate-product-management-section">
    <h3>Product Management</h3>
    <div class="hecate-product-actions">
        <!-- Digital, Cargo, Dead Drop grouped -->
    </div>
</div>
```

### 7. Footer Component

Updated with new page links.

**New Links:**
```html
<a href="{{ route('disclaimer') }}" class="footer-button">Disclaimer</a>
<a href="{{ route('refund-policy') }}" class="footer-button">Refund Policy</a>
```

### 8. New Static Pages Component

**Files Created:**
- `resources/views/disclaimer.blade.php`
- `resources/views/refund-policy.blade.php`

**Routes Added:**
```php
Route::view('/disclaimer', 'disclaimer')->name('disclaimer');
Route::view('/refund-policy', 'refund-policy')->name('refund-policy');
```

### 9. Asset Component

**Files to Replace:**
- `public/images/kabus.png` → `public/images/hecate.png`

**References to Update:**
- `resources/views/layouts/app.blade.php` (favicon)
- `resources/views/components/navbar.blade.php` (logo)

## Data Models

No database schema changes required. This rebrand is purely presentational.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: CSS Primary Color Migration

*For any* CSS color declaration in styles.css or auth.css that previously used the purple primary color (#bb86fc), the stylesheet SHALL contain the red primary color (#d32f2f) instead, and no instances of #bb86fc SHALL remain.

**Validates: Requirements 2.1, 2.3, 2.4, 2.5, 2.6**

### Property 2: CSS Hover Color Migration

*For any* CSS hover state declaration that previously used the purple hover color (#96c), the stylesheet SHALL contain the red hover color (#b71c1c) instead, and no instances of #96c SHALL remain.

**Validates: Requirements 2.2**

### Property 3: Font Family Migration

*For any* font-family declaration in styles.css or auth.css, the value SHALL be "Verdana", "Geneva", sans-serif and no instances of "Arial" as the primary font SHALL remain.

**Validates: Requirements 3.1**

## Error Handling

### Missing Asset Handling

When the new logo file (`hecate.png`) does not exist:
- The application SHALL continue to render without fatal errors
- The browser SHALL display the alt text or broken image indicator
- No PHP exceptions SHALL be thrown

### Configuration Fallback

When APP_NAME is not set in `.env`:
- The application SHALL use "Hecate Market" as the default value
- All views referencing `config('app.name')` SHALL display "Hecate Market"

## Testing Strategy

### Unit Tests

Unit tests will verify specific examples and edge cases:

1. **Configuration Tests**
   - Verify `config('app.name')` returns "Hecate Market"
   - Verify default fallback when APP_NAME is unset

2. **Route Tests**
   - Verify `/disclaimer` route returns 200 status
   - Verify `/refund-policy` route returns 200 status
   - Verify routes are named correctly

3. **View Tests**
   - Verify login view renders without errors
   - Verify register view renders without errors
   - Verify dashboard view renders without errors
   - Verify footer contains disclaimer and refund policy links
   - Verify navbar references new logo path
   - Verify vendor panel displays "Hecate Merchant Command"

### Property-Based Tests

Property-based tests will verify universal properties across all CSS content:

1. **CSS Color Migration Test**
   - Parse all CSS files
   - Generate assertions for each color value found
   - Verify no purple colors (#bb86fc, #96c) exist
   - Verify red colors (#d32f2f, #b71c1c) are present where expected

2. **CSS Font Migration Test**
   - Parse all CSS files
   - Find all font-family declarations
   - Verify none use "Arial" as primary font
   - Verify all use "Verdana" as primary font

### Test Configuration

- Testing Framework: PHPUnit (Laravel's default)
- Property tests should run minimum 100 iterations where applicable
- Each property test must reference its design document property
- Tag format: **Feature: hecate-rebrand, Property {number}: {property_text}**

