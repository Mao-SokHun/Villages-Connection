#!/usr/bin/env bash
# Deploy / update on Oracle Cloud VM (or any Linux server with Docker).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [ ! -f .env ]; then
    echo "Missing .env — copy .env.example and set Supabase DB_*, APP_URL, MAIL_*, etc."
    exit 1
fi

git pull --ff-only 2>/dev/null || true

docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d

echo "Deploy complete."
echo "Site: http://$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')"
echo "Logs: docker compose -f docker-compose.prod.yml logs -f --tail 50"
