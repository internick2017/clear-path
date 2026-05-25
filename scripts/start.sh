#!/bin/bash
set -e

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding demo data..."
php artisan db:seed --force

echo "==> Linking storage..."
php artisan storage:link --quiet 2>/dev/null || true

echo "==> Starting server on port ${PORT:-8000}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
