#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

[ -f .env ] || cp .env.example .env
[ -f database/database.sqlite ] || touch database/database.sqlite

composer install
php artisan key:generate --force
php artisan migrate --seed --force
npm ci
npm run build

printf '\nSetup selesai. Jalankan: php artisan serve\n'
