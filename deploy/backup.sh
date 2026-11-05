#!/usr/bin/env bash
# SOP §27.6 - nightly full MySQL dump + storage archive, 14-day retention.
# Reads DB_* from the app .env. Copy the output directory off-server separately (rsync/rclone).
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/sms/current}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/sms}"
STAMP="$(date +%F_%H%M)"

env_get() { grep -E "^$1=" "$APP_DIR/.env" | head -1 | cut -d= -f2- | tr -d '"'; }
DB_NAME="$(env_get DB_DATABASE)"; DB_USER="$(env_get DB_USERNAME)"; DB_PASS="$(env_get DB_PASSWORD)"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

MYSQL_PWD="$DB_PASS" mysqldump --single-transaction --routines -u "$DB_USER" "$DB_NAME" | gzip > "$BACKUP_DIR/db_$STAMP.sql.gz"
tar -czf "$BACKUP_DIR/storage_$STAMP.tar.gz" -C "$APP_DIR/storage/app" .

# Retention: 14 days
find "$BACKUP_DIR" -type f \( -name 'db_*.sql.gz' -o -name 'storage_*.tar.gz' \) -mtime +14 -delete

echo "Backup OK: $BACKUP_DIR ($STAMP)"
