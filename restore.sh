#!/bin/bash
# Restore database + uploads dari hasil backup.sh
#   ./restore.sh backups/db-20260101-120000.sql backups/uploads-20260101-120000.tar.gz
set -euo pipefail

DB_FILE="${1:?pakai: ./restore.sh <db.sql> <uploads.tar.gz>}"
UP_FILE="${2:?pakai: ./restore.sh <db.sql> <uploads.tar.gz>}"

echo "→ restore database dari $DB_FILE…"
docker compose exec -T db mysql -u root -p"$MYSQL_ROOT_PASSWORD" \
  "${DB_NAME:-mypuskeswan_db}" < "$DB_FILE"

echo "→ restore uploads dari $UP_FILE…"
docker run --rm -v mypuskeswan_uploads:/data -v "$PWD:/in:ro" \
  alpine sh -c "rm -rf /data/* && tar -xzf /in/$UP_FILE -C /data && chown -R 33:33 /data"

echo "✅ restore selesai"
