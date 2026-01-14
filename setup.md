cat << 'EOF' > install_final.sh
#!/bin/bash

# --- CONFIGURATION ---
DB_NAME="kabus_market"
DB_USER="kabus_admin"
DB_PASS="SafeMarketPass2025" # Secure, alphanumeric password
SITE_NAME="hecate"           # Prefix for vanity onion addresses
# ---------------------

# Stop on errors
set -e

echo "=================================================="
echo "   STARTING KABUS MARKETPLACE DEPLOYMENT (FINAL)  "
echo "=================================================="

# 1. System Preparation & Dependencies
echo ">>> [1/12] Updating System & Installing Software..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y software-properties-common unzip git curl screen mysql-server nginx tor \
    build-essential libsodium-dev autoconf
add-apt-repository ppa:ondrej/php -y
apt-get update -q
# Install PHP 8.3 and critical extensions
apt-get install -y php8.3-fpm php8.3-mysql php8.3-curl php8.3-gd php8.3-mbstring \
php8.3-xml php8.3-zip php8.3-bcmath php8.3-gnupg php8.3-intl php8.3-readline \
php8.3-common php8.3-cli php8.3-gmp

# 2. Composer Installation
echo ">>> [2/12] Installing Composer..."
if ! command -v composer &> /dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# 3. Database Setup (Fresh Install)
echo ">>> [3/12] Configuring Database..."
mysql -e "DROP DATABASE IF EXISTS ${DB_NAME};"
mysql -e "CREATE DATABASE ${DB_NAME};"
mysql -e "DROP USER IF EXISTS '${DB_USER}'@'localhost';"
mysql -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# 4. Clone Repository
echo ">>> [4/12] Cloning Repository..."
cd /var/www
rm -rf kabus # Remove previous install
git clone https://github.com/nebotaismael/kabus.git
cd kabus

# 5. Environment Configuration
echo ">>> [5/12] Configuring Environment (.env)..."
cp .env.example .env

# Detect Public IP
SERVER_IP=$(curl -s ifconfig.me)

# PRECISE REPLACEMENTS based on your .env.example file
sed -i "s/DB_DATABASE=your_database_name/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=your_user/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=Y0ur_P@ssw0rd!23/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s|APP_URL=http://localhost|APP_URL=http://${SERVER_IP}|" .env

# 6. Install PHP Dependencies
echo ">>> [6/12] Installing Dependencies..."
composer install --ignore-platform-reqs --no-interaction --optimize-autoloader

# 7. Application Setup & Key Generation
echo ">>> [7/12] Generating Keys & Seeding Database..."
php artisan key:generate
php artisan config:clear

# Migrate and Seed (Forces fresh DB structure)
php artisan migrate:fresh --seed --force

# 8. Force Admin Password Reset
echo ">>> [8/12] Resetting Admin Password..."
php artisan tinker --execute="
\$user = \App\Models\User::where('username', 'user1')->first();
if (\$user) {
    \$user->password = \Illuminate\Support\Facades\Hash::make('password');
    \$user->save();
    echo 'SUCCESS: Admin password reset.';
}
"

# 9. File Permissions
echo ">>> [9/12] Setting Permissions..."
chown -R www-data:www-data /var/www/kabus
find /var/www/kabus -type f -exec chmod 644 {} \;
find /var/www/kabus -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/kabus/storage
chmod -R 775 /var/www/kabus/bootstrap/cache

# 10. Nginx Configuration (Public Access)
echo ">>> [10/12] Configuring Nginx..."
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

# 11. Install mkp224o for Vanity Onion Address Generation
echo ">>> [11/12] Installing mkp224o (Vanity Onion Generator)..."
cd /tmp
rm -rf mkp224o
git clone https://github.com/cathugger/mkp224o.git
cd mkp224o
./autogen.sh
./configure
make
cp mkp224o /usr/local/bin/

# 12. Generate 3 Vanity Onion Addresses & Configure Tor
echo ">>> [12/12] Generating 3 Vanity Onion Addresses..."
echo "This may take several minutes depending on prefix length..."

ONION_DIR="/var/lib/tor"
mkdir -p ${ONION_DIR}

# Generate 3 vanity addresses with site name prefix
# Using 4-5 character prefix is reasonable (minutes), 6+ chars takes hours/days
cd /tmp
rm -rf onion_keys
mkdir onion_keys
cd onion_keys

echo "Generating vanity addresses starting with '${SITE_NAME}'..."
echo "(Shorter prefixes = faster generation)"

# Generate 3 addresses - mkp224o outputs directories with keys
mkp224o -n 3 -d . ${SITE_NAME} 2>/dev/null || {
    echo "Vanity generation taking too long, falling back to random addresses..."
    # Fallback: generate random addresses
    for i in 1 2 3; do
        mkdir -p "random_${i}"
        # Tor will generate keys when started
    done
}

# Setup the 3 hidden services
systemctl stop tor

# Clean old hidden services
rm -rf ${ONION_DIR}/hecate_service_1
rm -rf ${ONION_DIR}/hecate_service_2  
rm -rf ${ONION_DIR}/hecate_service_3

# Copy generated keys to Tor directories
COUNTER=1
for dir in */; do
    if [ -f "${dir}hs_ed25519_secret_key" ]; then
        SERVICE_DIR="${ONION_DIR}/hecate_service_${COUNTER}"
        mkdir -p "${SERVICE_DIR}"
        cp "${dir}hs_ed25519_secret_key" "${SERVICE_DIR}/"
        cp "${dir}hs_ed25519_public_key" "${SERVICE_DIR}/"
        cp "${dir}hostname" "${SERVICE_DIR}/"
        chmod 700 "${SERVICE_DIR}"
        chmod 600 "${SERVICE_DIR}"/*
        chown -R debian-tor:debian-tor "${SERVICE_DIR}"
        ((COUNTER++))
        if [ $COUNTER -gt 3 ]; then break; fi
    fi
done

# If vanity generation failed, create empty dirs for Tor to populate
for i in 1 2 3; do
    SERVICE_DIR="${ONION_DIR}/hecate_service_${i}"
    if [ ! -d "${SERVICE_DIR}" ]; then
        mkdir -p "${SERVICE_DIR}"
        chmod 700 "${SERVICE_DIR}"
        chown debian-tor:debian-tor "${SERVICE_DIR}"
    fi
done

# Configure Tor with 3 hidden services
cat > /etc/tor/torrc <<TORCONF
SocksPort 9050
RunAsDaemon 1

# Hidden Service 1 - Primary
HiddenServiceDir /var/lib/tor/hecate_service_1/
HiddenServicePort 80 127.0.0.1:80

# Hidden Service 2 - Backup/Mirror
HiddenServiceDir /var/lib/tor/hecate_service_2/
HiddenServicePort 80 127.0.0.1:80

# Hidden Service 3 - Backup/Mirror
HiddenServiceDir /var/lib/tor/hecate_service_3/
HiddenServicePort 80 127.0.0.1:80
TORCONF

# Fix permissions
chown -R debian-tor:debian-tor /var/lib/tor
chmod 700 /var/lib/tor

systemctl start tor

# Wait for all addresses to generate
echo "Waiting for Tor to generate addresses..."
sleep 10

echo "=================================================="
echo "   DEPLOYMENT SUCCESSFUL!"
echo "=================================================="
echo "1. Public Web Access (Clearweb):"
echo "   http://${SERVER_IP}"
echo ""
echo "2. Darknet Access (Tor) - 3 Onion Addresses:"
echo "   Primary:  $(cat ${ONION_DIR}/hecate_service_1/hostname 2>/dev/null || echo 'Generating...')"
echo "   Mirror 1: $(cat ${ONION_DIR}/hecate_service_2/hostname 2>/dev/null || echo 'Generating...')"
echo "   Mirror 2: $(cat ${ONION_DIR}/hecate_service_3/hostname 2>/dev/null || echo 'Generating...')"
echo "--------------------------------------------------"
echo "LOGIN CREDENTIALS:"
echo "Username: user1"
echo "Password: password"
echo "=================================================="
echo ""
echo "To view onion addresses later:"
echo "  cat /var/lib/tor/hecate_service_1/hostname"
echo "  cat /var/lib/tor/hecate_service_2/hostname"
echo "  cat /var/lib/tor/hecate_service_3/hostname"
EOF

# Run the script
chmod +x install_final.sh
./install_final.sh
