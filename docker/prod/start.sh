#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-8000}"

# Strip Debian "Welcome to nginx" site — only serve our PHP app.
rm -rf /etc/nginx/sites-enabled /etc/nginx/sites-available
mkdir -p /etc/nginx/sites-enabled /etc/nginx/sites-available
rm -f /etc/nginx/conf.d/default.conf /etc/nginx/conf.d/default
rm -f /var/www/html/index.nginx-debian.html /usr/share/nginx/html/index.html 2>/dev/null || true

sed "s/__PORT__/${PORT}/g" /etc/nginx/templates/app.conf > /etc/nginx/conf.d/00-villages.conf
echo "nginx listening on port ${PORT}"
nginx -t

mkdir -p \
    /var/www/html/storage/cache \
    /var/www/html/storage/backups \
    /var/www/html/public/uploads/avatars \
    /var/www/html/public/uploads/videos

chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads 2>/dev/null || true

echo "Running database migrations..."
php /var/www/html/database/migrate.php || echo "Migration skipped or failed — check DB env vars."

php-fpm -D
exec nginx -g 'daemon off;'
