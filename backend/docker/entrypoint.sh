#!/usr/bin/env sh
set -eu

APP_DIR="/var/www/html"

fix_permissions() {
    mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/bootstrap/cache"

    if [ "$(id -u)" -eq 0 ]; then
        chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true
    fi

    # Keep directories group-writable; files writable by owner/group.
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \; || true
    find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \; || true

    touch "$APP_DIR/storage/logs/laravel.log" || true
    chmod 664 "$APP_DIR/storage/logs/laravel.log" || true
}

build_frontend_assets() {
    # Build Vite assets if package.json exists and public/build/manifest.json is missing.
    # This runs npm ci + npm run build once on first start (or after git pull wipes public/build).
    if [ -f "$APP_DIR/package.json" ] && [ ! -f "$APP_DIR/public/build/manifest.json" ]; then
        echo "[entrypoint] Building frontend assets with Vite..."
        (cd "$APP_DIR" && npm ci --prefer-offline --no-audit && npm run build) || true
        echo "[entrypoint] Vite build complete."
    fi
}

run_artisan_safe() {
    command="$1"

    if [ ! -f "$APP_DIR/artisan" ]; then
        return 0
    fi

    if [ "$(id -u)" -eq 0 ]; then
        su -s /bin/sh www-data -c "cd $APP_DIR && php artisan $command" || true
    else
        (cd "$APP_DIR" && php artisan "$command") || true
    fi
}

fix_permissions
build_frontend_assets
run_artisan_safe "storage:link"

if [ "${APP_ENV:-local}" != "production" ]; then
    run_artisan_safe "config:clear"
    run_artisan_safe "cache:clear"
fi

exec "$@"
