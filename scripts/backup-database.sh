#!/usr/bin/env sh
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="$ROOT/storage/backups"
mkdir -p "$BACKUP_DIR"

if [ -f "$ROOT/.env" ]; then
    set -a
    # shellcheck disable=SC1091
    . "$ROOT/.env"
    set +a
fi

DB_NAME="${DB_DATABASE:-project_cms}"
DB_USER="${DB_USERNAME:-postgres}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_FILE="$BACKUP_DIR/db-${DB_NAME}-${STAMP}.sql.gz"

if [ "$DB_HOST" = "db" ]; then
    docker exec project_cms_db pg_dump -U "$DB_USER" -d "$DB_NAME" --no-owner --no-acl | gzip > "$OUT_FILE"
else
    PGPASSWORD="$DB_PASSWORD" pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" --no-owner --no-acl | gzip > "$OUT_FILE"
fi

echo "Backup saved: $OUT_FILE"
ls -1t "$BACKUP_DIR"/db-*.sql.gz 2>/dev/null | tail -n +15 | xargs -r rm -f
