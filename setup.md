cat << 'EOF' > install_final.sh
#!/bin/bash

# --- CONFIGURATION ---
DB_NAME="kabus_market"
DB_USER="kabus_admin"
DB_PASS="SafeMarketPass2025" # Secure, alphanumeric password
# ---------------------

# Stop on errors
set -e

echo "=================================================="
echo "   STARTING KABUS MARKETPLACE DEPLOYMENT (FINAL)  "
echo "=================================================="

# 1. System Preparation & Dependencies
echo ">>> [1/11] Updating System & Installing Software..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y software-properties-common unzip git curl screen mysql-server nginx tor
add-apt-repository ppa:ondrej/php -y
apt-get update -q
# Install PHP 8.3 and critical extensions
apt-get install -y php8.3-fpm php8.3-mysql php8.3-curl php8.3-gd php8.3-mbstring \
php8.3-xml php8.3-zip php8.3-bcmath php8.3-gnupg php8.3-intl php8.3-readline \
php8.3-common php8.3-cli php8.3-gmp

# 2. Composer Installation
echo ">>> [2/11] Installing Composer..."
if ! command -v composer &> /dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# 3. Database Setup (Fresh Install)
echo ">>> [3/11] Configuring Database..."
mysql -e "DROP DATABASE IF EXISTS ${DB_NAME};"
mysql -e "CREATE DATABASE ${DB_NAME};"
mysql -e "DROP USER IF EXISTS '${DB_USER}'@'localhost';"
mysql -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# 4. Clone Repository
echo ">>> [4/11] Cloning Repository..."
cd /var/www
rm -rf kabus # Remove previous install
git clone https://github.com/nebotaismael/kabus.git
cd kabus

# 5. Environment Configuration
echo ">>> [5/11] Configuring Environment (.env)..."
cp .env.example .env

# Detect Public IP
SERVER_IP=$(curl -s ifconfig.me)

# PRECISE REPLACEMENTS based on your .env.example file
# We replace the specific placeholders "your_database_name" and "your_user"
sed -i "s/DB_DATABASE=your_database_name/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=your_user/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=Y0ur_P@ssw0rd!23/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s|APP_URL=http://localhost|APP_URL=http://${SERVER_IP}|" .env

# 6. Install PHP Dependencies
echo ">>> [6/11] Installing Dependencies..."
composer install --ignore-platform-reqs --no-interaction --optimize-autoloader

# 7. Application Setup & Key Generation
echo ">>> [7/11] Generating Keys & Seeding Database..."
php artisan key:generate
php artisan config:clear

# Migrate and Seed (Forces fresh DB structure)
php artisan migrate:fresh --seed --force

# 8. Force Admin Password Reset
echo ">>> [8/11] Resetting Admin Password..."
# The seeder creates random passwords. We force user1 to 'password' so you can login.
php artisan tinker --execute="
\$user = \App\Models\User::where('username', 'user1')->first();
if (\$user) {
    \$user->password = \Illuminate\Support\Facades\Hash::make('password');
    \$user->save();
    echo 'SUCCESS: Admin password reset.';
}
"

# 9. File Permissions
echo ">>> [9/11] Setting Permissions..."
chown -R www-data:www-data /var/www/kabus
find /var/www/kabus -type f -exec chmod 644 {} \;
find /var/www/kabus -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/kabus/storage
chmod -R 775 /var/www/kabus/bootstrap/cache

# 10. Nginx Configuration (Public Access)
echo ">>> [10/11] Configuring Nginx..."
# Remove default site to prevent conflicts
rm -f /etc/nginx/sites-enabled/default

cat > /etc/nginx/sites-available/kabus <<ENDCONF
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root /var/www/kabus/public;
    index index.php;
    error_page 503 /maintenance.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
ENDCONF

ln -sf /etc/nginx/sites-available/kabus /etc/nginx/sites-enabled/
systemctl restart nginx

# 11. Tor Configuration (Hidden Service)
echo ">>> [11/11] Configuring Tor..."
systemctl stop tor
# Clean config
cat > /etc/tor/torrc <<TORCONF
SocksPort 9050
RunAsDaemon 1
HiddenServiceDir /var/lib/tor/kabus_hidden_service/
HiddenServicePort 80 127.0.0.1:80
TORCONF

# Fix permissions which cause "No such file" errors
if [ -d "/var/lib/tor" ]; then
    chown -R debian-tor:debian-tor /var/lib/tor
    chmod 700 /var/lib/tor
fi
rm -rf /var/lib/tor/kabus_hidden_service/ # Force fresh generation
systemctl start tor

# Wait for key generation
echo "Generating Onion Address (Please wait)..."
COUNT=0
while [ ! -f "/var/lib/tor/kabus_hidden_service/hostname" ]; do
    sleep 2
    ((++COUNT))
    if [ $COUNT -ge 30 ]; then break; fi
done

echo "=================================================="
echo "   DEPLOYMENT SUCCESSFUL!"
echo "=================================================="
echo "1. Public Web Access (Clearweb):"
echo "   http://${SERVER_IP}"
echo ""
echo "2. Darknet Access (Tor):"
cat /var/lib/tor/kabus_hidden_service/hostname 2>/dev/null || echo "   (Tor is starting, check /var/lib/tor/kabus_hidden_service/hostname later)"
echo "--------------------------------------------------"
echo "LOGIN CREDENTIALS:"
echo "Username: user1"
echo "Password: password"
echo "=================================================="
EOF

# Run the script
chmod +x install_final.sh
./install_final.sh