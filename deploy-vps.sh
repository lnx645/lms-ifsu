#!/bin/bash
# Deploy CBT SMK Informatika Sumedang (branch redesign-dashboard) ke VPS Ubuntu 24.04
# Cara pakai: sudo bash deploy-vps.sh
# Asumsi: Ubuntu 24.04 fresh, dijalankan sebagai root, ada akses internet.
set -e

APP_DIR="/var/www/cbt-smk"
BRANCH="redesign-dashboard"
REPO="https://github.com/lnx645/cbt-smk-informatika-sumedang.git"
PHP_VER="8.4"
DB_NAME="cbt_db"
DB_USER="cbt_user"

echo "=== 1/7: Update sistem & install dependensi dasar ==="
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
  software-properties-common curl git unzip nginx \
  postgresql postgresql-contrib 2>&1 | tail -1

echo "=== 2/7: Install PHP ${PHP_VER} ==="
add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
  php${PHP_VER}-cli php${PHP_VER}-fpm php${PHP_VER}-mbstring php${PHP_VER}-xml \
  php${PHP_VER}-pgsql php${PHP_VER}-curl php${PHP_VER}-zip php${PHP_VER}-gd \
  php${PHP_VER}-intl php${PHP_VER}-bcmath 2>&1 | tail -1

echo "=== 3/7: Install Composer & Node ==="
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet
curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null 2>&1
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq nodejs 2>&1 | tail -1
npm install -g bun 2>&1 | tail -1 || true

echo "=== 4/7: Setup database PostgreSQL ==="
DB_PASS="$(openssl rand -base64 16 | tr -d '/+=' | cut -c1-20)"
sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';" >/dev/null
sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};" >/dev/null
echo "DB password tersimpan di ${APP_DIR}/.dbpass (hanya root bisa baca)"

echo "=== 5/7: Clone & install aplikasi ==="
rm -rf "${APP_DIR}"
git clone --branch "${BRANCH}" --depth 1 "${REPO}" "${APP_DIR}"
cd "${APP_DIR}"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader 2>&1 | tail -2
bun install 2>&1 | tail -1 || npm install 2>&1 | tail -1

echo "=== 6/7: Konfigurasi environment ==="
cp .env.example .env
php artisan key:generate --force
sed -i "s/^APP_DEBUG=.*/APP_DEBUG=false/" .env
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
echo "${DB_PASS}" > "${APP_DIR}/.dbpass"
chmod 600 "${APP_DIR}/.dbpass"
php artisan migrate --force 2>&1 | tail -2
php artisan db:seed --force 2>&1 | tail -2 || true
bun run build 2>&1 | tail -1
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo "=== 7/7: Konfigurasi Nginx ==="
cat > /etc/nginx/sites-available/cbt <<NGINX
server {
    listen 80;
    server_name _;
    root ${APP_DIR}/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VER}-fpm.sock;
    }

    location ~ /\.ht { deny all; }
    client_max_body_size 20M;
}
NGINX
ln -sf /etc/nginx/sites-available/cbt /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
systemctl enable --now php${PHP_VER}-fpm postgresql nginx >/dev/null 2>&1 || true

echo ""
echo "==================================================="
echo " SELESAI! Aplikasi bisa diakses via http://<IP-VPS>/"
echo " Login admin default: admin@smkifsu.sch.id / password"
echo " (segera ganti password setelah login!)"
echo "==================================================="
