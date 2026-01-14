# Project Structure

Standard Laravel 11 structure with marketplace-specific organization.

## Directory Layout

```
app/
├── Console/Commands/     # Artisan commands (e.g., SimulatePayment)
├── Http/
│   ├── Controllers/      # Request handlers
│   │   ├── AdminController.php      # Admin panel operations
│   │   ├── VendorController.php     # Vendor panel operations
│   │   ├── OrdersController.php     # Order management
│   │   ├── WebhookController.php    # Payment webhooks
│   │   └── ...
│   └── Middleware/       # Auth, bans, CSRF, vendor/admin checks
├── Models/               # Eloquent models (UUID primary keys)
├── Policies/             # Authorization policies
├── Providers/            # Service providers
└── Services/             # Business logic (NowPaymentsService)

config/
├── marketplace.php       # Platform settings (commission, references)
├── nowpayments.php       # Payment gateway config
└── ...                   # Standard Laravel configs

database/
├── migrations/           # Timestamped migrations (2025_XX_XX_*)
└── seeders/              # Test data (UserSeeder, ProductSeeder)

resources/views/
├── admin/                # Admin panel views
├── vendor/               # Vendor panel views
├── auth/                 # Login, register, 2FA
├── cart/                 # Shopping cart
├── orders/               # Order management
├── products/             # Product listing/detail
├── components/           # Reusable Blade components
├── layouts/              # Page layouts
└── ...

routes/
├── web.php               # All HTTP routes (grouped by auth/role)
└── console.php           # Console commands

public/
├── css/                  # Stylesheets (auth.css, styles.css)
├── icons/                # UI icons
└── images/               # Static images
```

## Key Patterns

### Models
- All models use UUID primary keys (`$incrementing = false`)
- Soft deletes on `Product` model
- Encrypted attributes for sensitive data (mnemonic, reference_id)

### Routes
- Guest routes: auth pages, password reset
- Authenticated routes: main marketplace features
- Admin routes: protected by `AdminMiddleware`
- Vendor routes: protected by `VendorMiddleware`
- Webhook routes: excluded from CSRF, no auth required

### Controllers
- `AdminController` - Admin panel (users, categories, disputes, support)
- `VendorController` - Vendor panel (products, sales, advertisements)
- `OrdersController` - Order lifecycle (create, pay, ship, complete, cancel)
- `WebhookController` - NowPayments IPN callbacks

### Views
- Blade templates with `@extends('layouts.app')`
- Components in `resources/views/components/`
- No JavaScript - pure server-rendered HTML
