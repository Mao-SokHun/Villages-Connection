#!/usr/bin/env bash
# Issue Let's Encrypt certificate and switch nginx to HTTPS config.
# Usage: bash scripts/oracle-enable-https.sh yourdomain.com admin@yourdomain.com
set -euo pipefail

DOMAIN="${1:-}"
EMAIL="${2:-}"

if [ -z "$DOMAIN" ] || [ -z "$EMAIL" ]; then
    echo "Usage: bash scripts/oracle-enable-https.sh <domain> <email>"
    exit 1
fi

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.prod.yml"

# Ensure HTTP bootstrap nginx is active
export NGINX_CONF=production-init.conf
$COMPOSE up -d web

echo "Requesting certificate for $DOMAIN ..."
$COMPOSE --profile ssl run --rm certbot certonly \
    --webroot -w /var/www/certbot \
    --email "$EMAIL" \
    --agree-tos --no-eff-email \
    -d "$DOMAIN" -d "www.$DOMAIN"

# Render nginx config with domain
sed "s/\${DOMAIN}/$DOMAIN/g" docker/nginx/production.conf.template > docker/nginx/production.conf

# Update .env
if grep -q '^DOMAIN=' .env 2>/dev/null; then
    sed -i "s/^DOMAIN=.*/DOMAIN=$DOMAIN/" .env
else
    echo "DOMAIN=$DOMAIN" >> .env
fi
if grep -q '^NGINX_CONF=' .env 2>/dev/null; then
    sed -i 's/^NGINX_CONF=.*/NGINX_CONF=production.conf/' .env
else
    echo "NGINX_CONF=production.conf" >> .env
fi
if grep -q '^APP_URL=' .env 2>/dev/null; then
    sed -i "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" .env
else
    echo "APP_URL=https://$DOMAIN" >> .env
fi

export NGINX_CONF=production.conf
$COMPOSE up -d --force-recreate web
$COMPOSE --profile ssl up -d certbot

echo "HTTPS enabled for https://$DOMAIN"
