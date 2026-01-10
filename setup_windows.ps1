# ================================================
# KABUS MARKETPLACE - WINDOWS/WAMP SETUP SCRIPT
# ================================================
# Run as Administrator in PowerShell:
# Set-ExecutionPolicy Bypass -Scope Process -Force; .\setup_windows.ps1
# ================================================

# --- CONFIGURATION ---
$DB_NAME = "kabus_market"
$DB_USER = "kabus_admin"
$DB_PASS = "SafeMarketPass2025"
$PROJECT_PATH = "C:\wamp64\www\kabus"
# ---------------------

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "   KABUS MARKETPLACE SETUP" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

Set-Location $PROJECT_PATH

# 1. Database Setup
Write-Host "`n>>> [1/5] Setting Up Database..." -ForegroundColor Green

# Find MySQL in WAMP
$mysqlPath = Get-ChildItem "C:\wamp64\bin\mysql" -Directory | Sort-Object Name -Descending | Select-Object -First 1
$mysql = Join-Path $mysqlPath.FullName "bin\mysql.exe"

$sqlCommands = @"
DROP DATABASE IF EXISTS $DB_NAME;
CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP USER IF EXISTS '$DB_USER'@'localhost';
CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
"@

$sqlCommands | & $mysql -u root
Write-Host "Database '$DB_NAME' created!" -ForegroundColor Green

# 2. Configure .env
Write-Host "`n>>> [2/5] Configuring .env..." -ForegroundColor Green

Copy-Item ".env.example" ".env" -Force
$env = Get-Content ".env" -Raw
$env = $env -replace "DB_DATABASE=your_database_name", "DB_DATABASE=$DB_NAME"
$env = $env -replace "DB_USERNAME=your_user", "DB_USERNAME=$DB_USER"
$env = $env -replace "DB_PASSWORD=Y0ur_P@ssw0rd!23", "DB_PASSWORD=$DB_PASS"
$env = $env -replace "APP_URL=http://localhost/", "APP_URL=http://kabus.local/"
Set-Content ".env" $env -NoNewline
Write-Host ".env configured!" -ForegroundColor Green

# 3. Laravel Setup
Write-Host "`n>>> [3/5] Laravel Setup..." -ForegroundColor Green
php artisan key:generate --force
php artisan config:clear

# 4. Migrate & Seed
Write-Host "`n>>> [4/5] Running Migrations..." -ForegroundColor Green
php artisan migrate:fresh --seed --force

# 5. Set Admin Password
Write-Host "`n>>> [5/5] Setting Admin Password..." -ForegroundColor Green
php artisan tinker --execute="`$u = \App\Models\User::where('username','user1')->first(); `$u->password = \Illuminate\Support\Facades\Hash::make('password'); `$u->save(); echo 'Done!';"

php artisan storage:link 2>$null

Write-Host "`n==================================================" -ForegroundColor Cyan
Write-Host "   SETUP COMPLETE!" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "`nTest now with: php artisan serve" -ForegroundColor Yellow
Write-Host "Then visit: http://localhost:8000" -ForegroundColor Cyan
Write-Host "`nLogin: user1 / password" -ForegroundColor White
Write-Host "==================================================" -ForegroundColor Cyan
