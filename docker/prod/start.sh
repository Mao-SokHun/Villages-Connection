#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-8000}"

rm -f /etc/nginx/sites-enabled/default

sed "s/__PORT__/${PORT}/g" /etc/nginx/templates/default.conf > /etc/nginx/conf.d/default.conf
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
