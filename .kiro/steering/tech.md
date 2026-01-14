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
