cat << 'EOF' > install_final.sh
#!/bin/bash

# --- CONFIGURATION ---
DB_NAME="kabus_market"
DB_USER="kabus_admin"
DB_PASS="SafeMarketPass2025" # Secure, alphanumeric password
SITE_NAME="hecate"           # Prefix for vanity onion addresses (REQUIRED)
DOMAIN_NAME="hecatefuture.com"  # Your domain name (Hostinger)
ADMIN_EMAIL="admin@hecatefuture.com"  # Email for SSL certificate notifications
SERVER_IP_OVERRIDE="193.149.129.237"  # Your VPS IP (BitLaunch Amsterdam)
# ---------------------

# Domain DNS configured at Hostinger:
# - A Record: @ -> 193.149.129.237
# - CNAME: www -> hecatefuture.com

# Stop on errors
set -e

echo "=================================================="
echo "   STARTING HECATE MARKETPLACE DEPLOYMENT         "
echo "=================================================="

# 1. System Preparation & Dependencies
echo ">>> [1/15] Updating System & Installing Software..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y software-properties-common unzip git curl screen mysql-server nginx tor \
    build-essential libsodium-dev autoconf certbot python3-certbot-nginx dnsutils
add-apt-repository ppa:ondrej/php -y
apt-get update -q
# Install PHP 8.3 and critical extensions
apt-get install -y php8.3-fpm php8.3-mysql php8.3-curl php8.3-gd php8.3-mbstring \
php8.3-xml php8.3-zip php8.3-bcmath php8.3-gnupg php8.3-intl php8.3-readline \
php8.3-common php8.3-cli php8.3-gmp

# 2. Composer Installation
echo ">>> [2/15] Installing Composer..."
if ! command -v composer &> /dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# 3. Database Setup (Fresh Install)
echo ">>> [3/15] Configuring Database..."
mysql -e "DROP DATABASE IF EXISTS ${DB_NAME};"
mysql -e "CREATE DATABASE ${DB_NAME};"
mysql -e "DROP USER IF EXISTS '${DB_USER}'@'localhost';"
mysql -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# 4. Clone Repository
echo ">>> [4/15] Cloning Repository..."
cd /var/www
rm -rf kabus # Remove previous install
git clone https://github.com/nebotaismael/kabus.git
cd kabus

# 5. Environment Configuration
echo ">>> [5/15] Configuring Environment (.env)..."
cp .env.example .env

# Use override IP if set, otherwise detect
if [ -n "${SERVER_IP_OVERRIDE}" ]; then
    SERVER_IP="${SERVER_IP_OVERRIDE}"
else
    SERVER_IP=$(curl -s ifconfig.me)
fi

echo "Server IP: ${SERVER_IP}"

# PRECISE REPLACEMENTS based on your .env.example file
sed -i "s/DB_DATABASE=your_database_name/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=your_user/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=Y0ur_P@ssw0rd!23/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s|APP_URL=http://localhost|APP_URL=http://${SERVER_IP}|" .env

# 6. Install PHP Dependencies
echo ">>> [6/15] Installing Dependencies..."
composer install --ignore-platform-reqs --no-interaction --optimize-autoloader

# 7. Application Setup & Key Generation
echo ">>> [7/15] Generating Keys & Seeding Database..."
php artisan key:generate
php artisan config:clear

# Migrate and Seed (Forces fresh DB structure)
php artisan migrate:fresh --seed --force

# 8. Force Admin Password Reset
echo ">>> [8/15] Resetting Admin Password..."
php artisan tinker --execute="
\$user = \App\Models\User::where('username', 'user1')->first();
if (\$user) {
    \$user->password = \Illuminate\Support\Facades\Hash::make('password');
    \$user->save();
    echo 'SUCCESS: Admin password reset.';
}
"

# 9. File Permissions
echo ">>> [9/15] Setting Permissions..."
chown -R www-data:www-data /var/www/kabus
find /var/www/kabus -type f -exec chmod 644 {} \;
find /var/www/kabus -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/kabus/storage
chmod -R 775 /var/www/kabus/bootstrap/cache

# 10. Nginx Configuration (HTTP - Initial Setup)
echo ">>> [10/15] Configuring Nginx..."
rm -f /etc/nginx/sites-enabled/default

# Determine server name for Nginx
if [ -n "${DOMAIN_NAME}" ]; then
    NGINX_SERVER_NAME="${DOMAIN_NAME} www.${DOMAIN_NAME}"
else
    NGINX_SERVER_NAME="_"
fi

cat > /etc/nginx/sites-available/kabus <<ENDCONF
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name ${NGINX_SERVER_NAME};
    root /var/www/kabus/public;
    index index.php;
    error_page 503 /maintenance.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\.git {
        deny all;
    }
}
ENDCONF

ln -sf /etc/nginx/sites-available/kabus /etc/nginx/sites-enabled/
nginx -t && systemctl restart nginx

# 11. SSL Configuration with Certbot (if domain is configured)
echo ">>> [11/15] Configuring SSL..."
if [ -n "${DOMAIN_NAME}" ] && [ -n "${ADMIN_EMAIL}" ]; then
    echo "Domain configured: ${DOMAIN_NAME}"
    echo "Checking DNS resolution..."
    
    # Check if domain resolves to this server
    DOMAIN_IP=$(dig +short ${DOMAIN_NAME} | head -1)
    
    if [ "${DOMAIN_IP}" = "${SERVER_IP}" ]; then
        echo "DNS is properly configured. Installing SSL certificate..."
        
        # Obtain SSL certificate
        certbot --nginx -d ${DOMAIN_NAME} -d www.${DOMAIN_NAME} \
            --non-interactive --agree-tos --email ${ADMIN_EMAIL} \
            --redirect
        
        # Update APP_URL to use HTTPS
        sed -i "s|APP_URL=http://${SERVER_IP}|APP_URL=https://${DOMAIN_NAME}|" /var/www/kabus/.env
        
        echo "SSL certificate installed successfully!"
        
        # Setup auto-renewal cron job
        (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -
        
    else
        echo "WARNING: DNS not yet pointing to this server (${SERVER_IP})"
        echo "Domain ${DOMAIN_NAME} resolves to: ${DOMAIN_IP:-NOT FOUND}"
        echo ""
        echo "Please configure your domain's nameservers at your registrar:"
        echo "  Nameserver 1: ns1.asurahosting.com"
        echo "  Nameserver 2: ns2.asurahosting.com"
        echo "  Nameserver 3: ns1.my-control-panel.com"
        echo "  Nameserver 4: ns2.my-control-panel.com"
        echo ""
        echo "Then create an A record pointing to: ${SERVER_IP}"
        echo ""
        echo "After DNS propagates, run: certbot --nginx -d ${DOMAIN_NAME} -d www.${DOMAIN_NAME}"
    fi
else
    echo "No domain configured. Skipping SSL setup."
    echo "To add SSL later, set DOMAIN_NAME and ADMIN_EMAIL, then run:"
    echo "  certbot --nginx -d yourdomain.com -d www.yourdomain.com"
fi

# 12. Install mkp224o for Vanity Onion Address Generation
echo ">>> [12/15] Installing mkp224o (Vanity Onion Generator)..."
cd /tmp
rm -rf mkp224o
git clone https://github.com/cathugger/mkp224o.git
cd mkp224o
./autogen.sh
./configure
make
cp mkp224o /usr/local/bin/

# 13. Generate 3 Vanity Onion Addresses with "hecate" prefix
echo ">>> [13/15] Generating 3 Vanity Onion Addresses..."
echo "Generating addresses starting with '${SITE_NAME}' - this may take time..."
echo "A 6-character prefix like 'hecate' typically takes 5-30 minutes per address."

ONION_DIR="/var/lib/tor"
mkdir -p ${ONION_DIR}

# Generate vanity addresses with site name prefix
cd /tmp
rm -rf onion_keys
mkdir onion_keys
cd onion_keys

echo ""
echo "=============================================="
echo "  GENERATING VANITY ONION ADDRESSES          "
echo "  Prefix: ${SITE_NAME}                       "
echo "=============================================="
echo ""

# Function to validate onion address format
validate_onion_address() {
    local addr="$1"
    # Valid v3 onion: 56 characters + .onion (total 62 chars)
    # Must contain only lowercase a-z and 2-7, end with .onion
    if [[ ${#addr} -eq 62 ]] && [[ "$addr" =~ ^[a-z2-7]{56}\.onion$ ]]; then
        return 0
    else
        return 1
    fi
}

# Use threads based on CPU cores for speed
CORES=$(nproc)
GENERATED=0
VANITY_SUCCESS=true

# Try to generate vanity addresses with timeout
echo "Attempting vanity address generation (timeout: 60 minutes)..."
timeout 3600 mkp224o -n 3 -d . -t ${CORES} ${SITE_NAME} 2>&1 | tail -10 || {
    echo "WARNING: Vanity generation timed out or failed."
    VANITY_SUCCESS=false
}

# Count valid generated addresses
GENERATED=$(find . -name "hostname" -type f 2>/dev/null | wc -l)
echo "Generated ${GENERATED} addresses."

# If vanity generation failed or incomplete, use fallback
if [ ${GENERATED} -lt 3 ]; then
    echo ""
    echo "WARNING: Only ${GENERATED} vanity addresses generated."
    echo "Falling back to Tor-generated random addresses for remaining services."
    VANITY_SUCCESS=false
fi

# 14. Configure Tor with Generated Addresses
echo ">>> [14/15] Configuring Tor Hidden Services..."

systemctl stop tor

# Clean old hidden services
rm -rf ${ONION_DIR}/hecate_service_1
rm -rf ${ONION_DIR}/hecate_service_2  
rm -rf ${ONION_DIR}/hecate_service_3

# Copy generated vanity keys to Tor directories
cd /tmp/onion_keys
COUNTER=1
VALID_COUNT=0

for dir in ${SITE_NAME}*/; do
    if [ -f "${dir}hs_ed25519_secret_key" ] && [ -f "${dir}hostname" ]; then
        ONION_ADDR=$(cat "${dir}hostname" 2>/dev/null | tr -d '[:space:]')
        
        # Validate the onion address
        if validate_onion_address "${ONION_ADDR}"; then
            SERVICE_DIR="${ONION_DIR}/hecate_service_${COUNTER}"
            mkdir -p "${SERVICE_DIR}"
            cp "${dir}hs_ed25519_secret_key" "${SERVICE_DIR}/"
            cp "${dir}hs_ed25519_public_key" "${SERVICE_DIR}/"
            echo "${ONION_ADDR}" > "${SERVICE_DIR}/hostname"
            chmod 700 "${SERVICE_DIR}"
            chmod 600 "${SERVICE_DIR}"/*
            chown -R debian-tor:debian-tor "${SERVICE_DIR}"
            
            echo "Service ${COUNTER}: ${ONION_ADDR} [VALID]"
            ((VALID_COUNT++))
            ((COUNTER++))
            if [ $COUNTER -gt 3 ]; then break; fi
        else
            echo "Skipping invalid address: ${ONION_ADDR}"
        fi
    fi
done

echo "Configured ${VALID_COUNT} valid vanity addresses."

# Create remaining services as empty directories (Tor will generate random addresses)
for i in 1 2 3; do
    SERVICE_DIR="${ONION_DIR}/hecate_service_${i}"
    if [ ! -d "${SERVICE_DIR}" ] || [ ! -f "${SERVICE_DIR}/hs_ed25519_secret_key" ]; then
        echo "Service ${i}: Will be generated by Tor (random address)"
        rm -rf "${SERVICE_DIR}"
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

# Wait for Tor to start and generate any missing addresses
echo "Waiting for Tor to initialize..."
sleep 20

# Verify all addresses are now available and valid
echo "Verifying onion addresses..."
ALL_VALID=true
for i in 1 2 3; do
    SERVICE_DIR="${ONION_DIR}/hecate_service_${i}"
    if [ -f "${SERVICE_DIR}/hostname" ]; then
        ADDR=$(cat "${SERVICE_DIR}/hostname" 2>/dev/null | tr -d '[:space:]')
        if validate_onion_address "${ADDR}"; then
            echo "  Service ${i}: ${ADDR} [VALID]"
        else
            echo "  Service ${i}: ${ADDR} [INVALID FORMAT]"
            ALL_VALID=false
        fi
    else
        echo "  Service ${i}: NOT GENERATED YET"
        ALL_VALID=false
    fi
done

if [ "$ALL_VALID" = false ]; then
    echo ""
    echo "WARNING: Some onion addresses may not be ready yet."
    echo "Waiting additional 30 seconds for Tor..."
    sleep 30
fi

# 15. Update Application Config with Onion Addresses
echo ">>> [15/15] Updating Application Configuration..."

# Read addresses (with fallback to empty)
ONION_1=$(cat ${ONION_DIR}/hecate_service_1/hostname 2>/dev/null | tr -d '[:space:]')
ONION_2=$(cat ${ONION_DIR}/hecate_service_2/hostname 2>/dev/null | tr -d '[:space:]')
ONION_3=$(cat ${ONION_DIR}/hecate_service_3/hostname 2>/dev/null | tr -d '[:space:]')

# Update .env with onion addresses (only if valid)
if validate_onion_address "${ONION_1}"; then
    sed -i "s/ANTIPHISHING_ONION_ADDRESS_1=.*/ANTIPHISHING_ONION_ADDRESS_1=${ONION_1}/" /var/www/kabus/.env
    echo "Updated .env with ONION_ADDRESS_1"
fi
if validate_onion_address "${ONION_2}"; then
    sed -i "s/ANTIPHISHING_ONION_ADDRESS_2=.*/ANTIPHISHING_ONION_ADDRESS_2=${ONION_2}/" /var/www/kabus/.env
    echo "Updated .env with ONION_ADDRESS_2"
fi
if validate_onion_address "${ONION_3}"; then
    sed -i "s/ANTIPHISHING_ONION_ADDRESS_3=.*/ANTIPHISHING_ONION_ADDRESS_3=${ONION_3}/" /var/www/kabus/.env
    echo "Updated .env with ONION_ADDRESS_3"
fi

# Clear Laravel cache after config changes
cd /var/www/kabus
php artisan config:clear
php artisan cache:clear

echo "=================================================="
echo "   DEPLOYMENT SUCCESSFUL!"
echo "=================================================="
echo ""
echo "1. CLEARWEB ACCESS:"
if [ -n "${DOMAIN_NAME}" ]; then
    echo "   Domain: https://${DOMAIN_NAME}"
else
    echo "   IP Address: http://${SERVER_IP}"
fi
echo ""
echo "2. DARKNET ACCESS (Tor) - 3 Onion Addresses:"
if validate_onion_address "${ONION_1:-}"; then
    if [[ "${ONION_1}" == ${SITE_NAME}* ]]; then
        echo "   Primary:  ${ONION_1} [VANITY]"
    else
        echo "   Primary:  ${ONION_1} [RANDOM]"
    fi
else
    echo "   Primary:  Pending... (check later)"
fi
if validate_onion_address "${ONION_2:-}"; then
    if [[ "${ONION_2}" == ${SITE_NAME}* ]]; then
        echo "   Mirror 1: ${ONION_2} [VANITY]"
    else
        echo "   Mirror 1: ${ONION_2} [RANDOM]"
    fi
else
    echo "   Mirror 1: Pending... (check later)"
fi
if validate_onion_address "${ONION_3:-}"; then
    if [[ "${ONION_3}" == ${SITE_NAME}* ]]; then
        echo "   Mirror 2: ${ONION_3} [VANITY]"
    else
        echo "   Mirror 2: ${ONION_3} [RANDOM]"
    fi
else
    echo "   Mirror 2: Pending... (check later)"
fi
echo ""
echo "=================================================="
echo "LOGIN CREDENTIALS:"
echo "Username: user1"
echo "Password: password"
echo "=================================================="
echo ""
if [ -n "${DOMAIN_NAME}" ]; then
    echo "DOMAIN CONFIGURATION:"
    echo "Configure these nameservers at your domain registrar:"
    echo "  ns1.asurahosting.com"
    echo "  ns2.asurahosting.com"
    echo "  ns1.my-control-panel.com"
    echo "  ns2.my-control-panel.com"
    echo ""
    echo "Then create an A record for ${DOMAIN_NAME} pointing to: ${SERVER_IP}"
    echo ""
fi
echo "SSL CERTIFICATE:"
if [ -n "${DOMAIN_NAME}" ]; then
    echo "  SSL should be configured automatically if DNS was ready."
    echo "  If not, run after DNS propagates:"
    echo "    certbot --nginx -d ${DOMAIN_NAME} -d www.${DOMAIN_NAME}"
else
    echo "  No domain configured. To add SSL later:"
    echo "    1. Set DOMAIN_NAME and ADMIN_EMAIL in this script"
    echo "    2. Configure DNS as shown above"
    echo "    3. Run: certbot --nginx -d yourdomain.com"
fi
echo ""
echo "TO VIEW ONION ADDRESSES:"
echo "  cat /var/lib/tor/hecate_service_1/hostname"
echo "  cat /var/lib/tor/hecate_service_2/hostname"
echo "  cat /var/lib/tor/hecate_service_3/hostname"
echo ""
echo "TO REGENERATE VANITY ADDRESSES:"
echo "  mkp224o -n 3 -d /tmp/new_keys hecate"
echo ""
# Count vanity vs random addresses
VANITY_COUNT=0
for addr in "${ONION_1}" "${ONION_2}" "${ONION_3}"; do
    if [[ "${addr}" == ${SITE_NAME}* ]]; then
        ((VANITY_COUNT++))
    fi
done
if [ ${VANITY_COUNT} -lt 3 ]; then
    echo "NOTE: ${VANITY_COUNT}/3 addresses have '${SITE_NAME}' prefix."
    echo "To generate more vanity addresses manually:"
    echo "  1. Run: mkp224o -n 1 -d /tmp/vanity_keys ${SITE_NAME}"
    echo "  2. Copy keys to /var/lib/tor/hecate_service_X/"
    echo "  3. Restart Tor: systemctl restart tor"
fi
echo "=================================================="
EOF

# Run the script
chmod +x install_final.sh
./install_final.sh
