#!/usr/bin/env bash
#
# Deploy proto-folio on the VPS.
# Called by GitHub Actions over SSH:  ssh deploy@host 'bash -s' < deploy.sh
# Also safe to run directly on the server:  ./deploy.sh
#
# Override the app location with DEPLOY_PATH (defaults to /var/www/proto-folio).

set -euo pipefail

APP_DIR="${DEPLOY_PATH:-/var/www/proto-folio}"
BRANCH="${DEPLOY_BRANCH:-main}"

if [ ! -f "$APP_DIR/artisan" ]; then
    echo "No Laravel application found at $APP_DIR" >&2
    exit 1
fi

cd "$APP_DIR"

echo "==> Deploying origin/$BRANCH into $APP_DIR"

php artisan down --retry=15 || true
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

git fetch --prune origin "$BRANCH"
git reset --hard "origin/$BRANCH"

composer install --no-dev --optimize-autoloader --no-interaction

npm ci
npm run build

php artisan migrate --force
php artisan optimize

php artisan up
trap - EXIT

echo "==> Deployed $(git rev-parse --short HEAD) at $(date -u +%FT%TZ)"
