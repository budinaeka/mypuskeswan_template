#!/bin/bash
# myPuskeswan — one-liner installer untuk VPS baru (Ubuntu/Debian/CentOS/OpenCloudOS).
#
#   curl -fsSL https://raw.githubusercontent.com/budinaeka/mypuskeswan_template/main/install.sh | bash
#
# Opsi via environment:
#   APP_DIR=/opt/mypuskeswan APP_PORT=8080 ADMIN_PASSWORD=rahasia bash install.sh
#   SKIP_DOCKER=1  → lewati instalasi Docker (sudah ada)
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/mypuskeswan}"
APP_PORT="${APP_PORT:-8080}"
REPO="https://github.com/budinaeka/mypuskeswan_template.git"

log() { printf '\033[1;32m[install]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[install]\033[0m %s\n' "$*"; }
die() { printf '\033[1;31m[install]\033[0m %s\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "jalankan sebagai root (sudo bash install.sh)"

# --- 1. Docker ---------------------------------------------------------------
if [ "${SKIP_DOCKER:-0}" != "1" ] && ! command -v docker >/dev/null 2>&1; then
  log "menginstal Docker…"
  curl -fsSL https://get.docker.com | sh
  systemctl enable --now docker
else
  log "Docker sudah ada, dilewati"
fi
docker compose version >/dev/null 2>&1 || die "docker compose plugin tidak ditemukan"

# --- 2. Ambil / update source -------------------------------------------------
if [ -d "$APP_DIR/.git" ]; then
  log "update repo di $APP_DIR…"
  git -C "$APP_DIR" pull --ff-only
else
  log "clone repo ke $APP_DIR…"
  git clone --depth 1 "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"

# --- 3. .env ------------------------------------------------------------------
if [ ! -f .env ]; then
  cp .env.example .env
  # generate password acak bila tidak disediakan
  rand() { tr -dc 'A-Za-z0-9' </dev/urandom | head -c 20; }
  DB_PW="${DB_PASSWORD:-$(rand)}"
  ROOT_PW="${MYSQL_ROOT_PASSWORD:-$(rand)}"
  ADMIN_PW="${ADMIN_PASSWORD:-$(rand)}"
  sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PW}/" .env
  sed -i "s/^MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=${ROOT_PW}/" .env
  sed -i "s/^ADMIN_PASSWORD=.*/ADMIN_PASSWORD=${ADMIN_PW}/" .env
  sed -i "s/^APP_PORT=.*/APP_PORT=${APP_PORT}/" .env
  log "file .env dibuat (password acak, tersimpan di $APP_DIR/.env)"
  echo "  ADMIN_PASSWORD=${ADMIN_PW}"
else
  warn ".env sudah ada, tidak ditimpa (cek manual bila perlu)"
  sed -i "s/^APP_PORT=.*/APP_PORT=${APP_PORT}/" .env || true
fi

# --- 4. Jalankan ---------------------------------------------------------------
log "build + start container…"
docker compose up -d --build

log "menunggu init-db selesai…"
for i in $(seq 1 30); do
  if docker compose logs app 2>&1 | grep -q '\[init-db\] selesai'; then
    break
  fi
  sleep 2
done

IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
log "selesai ✅  buka: http://${IP:-IP-VPS}:${APP_PORT}"
log "cek status: cd $APP_DIR && docker compose ps && docker compose logs -f app"
