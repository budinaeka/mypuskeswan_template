#!/bin/bash
# Backup database + folder uploads dari volume container.
#   ./backup.sh                 → simpan ke ./backups/
#   BACKUP_DIR=/mnt/x ./backup.sh
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-./backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "→ backup database…"
docker compose exec -T db mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" \
  --single-transaction --routines --triggers \
  "${DB_NAME:-mypuskeswan_db}" > "$BACKUP_DIR/db-$STAMP.sql"

echo "→ backup uploads…"
docker run --rm -v mypuskeswan_uploads:/data:ro -v "$PWD/$BACKUP_DIR:/out" \
  alpine tar -czf "/out/uploads-$STAMP.tar.gz" -C /data .

echo "✅ tersimpan:"
ls -lh "$BACKUP_DIR/db-$STAMP.sql" "$BACKUP_DIR/uploads-$STAMP.tar.gz"
