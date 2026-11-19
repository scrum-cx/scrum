#!/usr/bin/env bash
# Prepares a fresh clone of Scrum on Linux / cloud sessions.
# Run from the repo root after scripts/setup-machine.sh:  bash scripts/setup-project.sh
# After pulling changes that alter existing tables, rebuild the database (deletes all data):
#   bash scripts/setup-project.sh --fresh
set -euo pipefail
cd "$(dirname "$0")/.."

DB_NAME="${DB_NAME:-scrum}"
FRESH=0; [ "${1:-}" = "--fresh" ] && FRESH=1

echo "==> Checking tools"
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' || { echo "PHP 8.2+ required"; exit 1; }
for t in composer node npm mariadb; do command -v "$t" >/dev/null || { echo "Missing: $t (run scripts/setup-machine.sh)"; exit 1; }; done

echo "==> Creating database '$DB_NAME' if missing"
mariadb -h127.0.0.1 -uroot -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> Installing PHP packages"
composer install --no-interaction
# In cloud sessions GitHub zip downloads are blocked, so Composer falls back to full
# git clones. Drop their history (and Composer's git cache) to save several GB.
find vendor -mindepth 3 -maxdepth 3 -name .git -type d -prune -exec rm -rf {} +
rm -rf "$(composer config cache-vcs-dir 2>/dev/null || echo /nonexistent)"

echo "==> Installing JS packages"
npm ci

if [ ! -f .env ]; then
  echo "==> Creating .env from .env.example"
  cp .env.example .env
  php artisan key:generate
fi

if [ "$FRESH" = 1 ]; then
  echo "==> Rebuilding the database from scratch (all data is deleted) and seeding"
  php artisan migrate:fresh --seed --force
else
  echo "==> Migrating and seeding"
  if ! php artisan migrate --seed --force; then
    echo
    echo "Migrations failed (see the error above)."
    echo "If it says a table \"already exists\", the table structure changed since your last setup."
    echo "Rebuild the database (this deletes its data) with:  bash scripts/setup-project.sh --fresh"
    exit 1
  fi
fi
php artisan storage:link 2>/dev/null || true

echo "==> Building front-end assets"
npm run build

echo
echo "Done. Start the app with:  php artisan serve   (then open http://127.0.0.1:8000)"
