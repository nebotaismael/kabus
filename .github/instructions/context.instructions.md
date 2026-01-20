---
applyTo: '**'
---
# Product Overview

Hecate Market (formerly Kabus) is a privacy-focused Monero marketplace built with Laravel 11 and PHP 8.3.

## Core Purpose

Anonymous e-commerce platform enabling vendors to sell products (digital, cargo, dead-drop) with cryptocurrency payments via NowPayments.io gateway.

## Key Features

- **Walletless Escrow:** No user wallets; payments are per-order and escrowed until resolution
- **Vendor System:** Registration fees, product management, sales tracking, payouts
- **Order Flow:** Cart → Checkout → Payment → Fulfillment → Review/Dispute
- **Security:** PGP-based 2FA, mnemonic recovery, no JavaScript
- **Admin Panel:** User management, disputes, categories, statistics, support tickets

## User Roles

- **Buyer:** Browse products, purchase, manage orders, open disputes
- **Vendor:** List products, manage sales, receive payouts, handle disputes
- **Admin:** Full platform control, dispute resolution, user moderation

## Payment Flow

All payments (orders, vendor fees, advertisements) use NowPayments.io API. Vendor payouts and refunds also go through NowPayments Payout API.


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


# Tech Stack

## Backend
- **PHP 8.3** with **Laravel 11**
- **MySQL** database
- **Blade** templating (no JavaScript frontend)

## Key Dependencies

| Package | Purpose |
|---------|---------|
| `laravel/framework` | Core framework |
| `intervention/image-laravel` | Image processing |
| `endroid/qr-code` | QR code generation |
| `ezyang/htmlpurifier` | HTML sanitization |
| `furqansiddiqui/bip39-mnemonic-php` | Mnemonic phrase generation |
| `mobicms/captcha` | CAPTCHA generation |
| `monero-integrations/monerophp` | Monero address validation (NOT for payments) |
| `guzzlehttp/guzzle` | HTTP client for API calls |

## Frontend Build
- **Vite** for asset bundling
- Plain CSS (no Tailwind/Bootstrap)
- No JavaScript in production views

## Common Commands

```bash
# Install dependencies
composer install
npm install

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Cache management
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Generate app key
php artisan key:generate

# Run development server
php artisan serve

# Build frontend assets
npm run build
npm run dev

# Run tests
php artisan test
./vendor/bin/phpunit

# Tinker (REPL)
php artisan tinker
```

## Environment

Key `.env` variables:
- `APP_KEY` - Encryption key (generate with artisan)
- `DB_*` - Database connection
- `NOWPAYMENTS_*` - Payment gateway config
- `MARKETPLACE_*` - Platform settings (commission, reference codes)
