#!/bin/sh
# ---------------------------------------------------------------
# myPuskeswan — container entrypoint
# Menunggu database siap, menyiapkan skema, lalu menjalankan CMD.
# ---------------------------------------------------------------
set -eu

APP_DIR="/var/www/html"
MAX_WAIT="${DB_WAIT_TIMEOUT:-60}"

log() { printf '[entrypoint] %s\n' "$*"; }

# --- 1. Pastikan folder upload ada & writable -------------------
mkdir -p "${APP_DIR}/uploads"
chown -R www-data:www-data "${APP_DIR}/uploads" 2>/dev/null || true
chmod 775 "${APP_DIR}/uploads" 2>/dev/null || true

# --- 2. Tunggu database ----------------------------------------
if [ "${SKIP_DB_WAIT:-0}" != "1" ]; then
    log "menunggu database ${DB_HOST:-db}:${DB_PORT:-3306} (maks ${MAX_WAIT}s)"
    waited=0
    while ! php "${APP_DIR}/scripts/init-db.php" --check >/dev/null 2>&1; do
        waited=$((waited + 2))
        if [ "$waited" -ge "$MAX_WAIT" ]; then
            log "PERINGATAN: database belum siap setelah ${MAX_WAIT}s, lanjut start (HTTP 503 sampai DB up)"
            break
        fi
        sleep 2
    done
fi

# --- 3. Migrasi / seed (idempotent, aman dijalankan tiap start) -
if [ "${SKIP_DB_INIT:-0}" != "1" ]; then
    log "menyiapkan skema database"
    php "${APP_DIR}/scripts/init-db.php" || log "init-db gagal, aplikasi tetap dijalankan"
fi

log "mulai: $*"
exec "$@"
