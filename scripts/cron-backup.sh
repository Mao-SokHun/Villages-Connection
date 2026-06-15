#!/usr/bin/env sh
# Daily database backup — add to crontab (runs at 02:00):
# 0 2 * * * /path/to/Viilages_Connection/scripts/cron-backup.sh >> /path/to/storage/logs/backup.log 2>&1
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
exec sh "$ROOT/scripts/backup-database.sh"
