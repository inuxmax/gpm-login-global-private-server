#!/bin/sh
# Container init: bootstrap .env, APP_KEY, storage dirs, perms, cache.
# Runs before Apache so named-volume mounts (empty storage/) are handled.
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

log() { echo "[entrypoint] $*"; }

# ---- 1. Ensure .env exists (public image: first boot has no secrets) -------
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        log ".env missing → copying from .env.example"
        cp .env.example .env
    else
        log "WARNING: no .env.example found, Laravel will likely fail"
    fi
fi

# ---- 2. Generate APP_KEY if empty / placeholder ---------------------------
if [ -f .env ]; then
    if ! grep -qE '^APP_KEY=.+' .env || grep -qE '^APP_KEY=[[:space:]]*$' .env; then
        log "Generating APP_KEY"
        php artisan key:generate --force || log "key:generate failed (non-fatal)"
    fi
fi

# ---- 3. Seed Laravel storage tree (named volumes may start empty) ---------
mkdir -p \
    storage/app/public/profiles \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# ---- 4. Fix ownership/permissions (volume mounts often come root-owned) ---
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# ---- 5. Public storage symlink (refresh if it's a dead/missing link) ------
if [ ! -L public/storage ]; then
    rm -rf public/storage
    php artisan storage:link || log "storage:link failed (non-fatal)"
fi

# ---- 6. Clear cached config/routes/views so new .env takes effect ---------
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear  >/dev/null 2>&1 || true
php artisan view:clear   >/dev/null 2>&1 || true

log "Starting: $*"
exec "$@"
