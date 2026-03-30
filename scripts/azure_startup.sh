#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/site/wwwroot"
NGINX_SOURCE="${APP_DIR}/scripts/nginx.default"
NGINX_TARGET="/etc/nginx/sites-available/default"

required_vars=(
  MAGENTO_DB_HOST
  MAGENTO_DB_NAME
  MAGENTO_DB_USER
  MAGENTO_DB_PASSWORD
  MAGENTO_CRYPT_KEY
)

for var_name in "${required_vars[@]}"; do
  if [ -z "${!var_name:-}" ]; then
    echo "Missing required environment variable: ${var_name}" >&2
    exit 1
  fi
done

cd "$APP_DIR"

if [ -f "$NGINX_SOURCE" ]; then
  cp "$NGINX_SOURCE" "$NGINX_TARGET"
  nginx -s reload || true
else
  echo "Missing nginx config at $NGINX_SOURCE" >&2
  exit 1
fi

mkdir -p var/cache var/page_cache var/log var/tmp generated pub/static

php scripts/render-env.php
php -l app/etc/env.php

echo "Azure startup preparation completed."
