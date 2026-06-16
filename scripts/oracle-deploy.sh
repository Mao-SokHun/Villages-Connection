#!/usr/bin/env bash
# Deploy / update Village Connect on Oracle Cloud VM.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.prod.yml"

if [ ! -f .env ]; then
    echo "Missing .env — copy .env.example and configure production values."
    exit 1
fi

git pull --ff-only 2>/dev/null || true

$COMPOSE build
$COMPOSE up -d

echo "Running migrations..."
$COMPOSE exec -T app php database/migrate.php

echo "Deploy complete. Check: docker compose -f docker-compose.prod.yml ps"
