#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${1:-$HOME/sites/uniekglas_hyva}"
ENV_FILE="${PROJECT_DIR}/.env"
COMPOSER_AUTH_FILE="${HOME}/.config/composer/auth.json"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Missing required command: $1" >&2
    exit 1
  }
}

need_file() {
  [ -f "$1" ] || {
    echo "Missing required file: $1" >&2
    exit 1
  }
}

need_cmd php
need_cmd composer

cd "$PROJECT_DIR"

need_file composer.json
need_file scripts/render-env.php
need_file app/etc/env.php.template

if [ ! -f "$COMPOSER_AUTH_FILE" ]; then
  echo "Composer auth not found at $COMPOSER_AUTH_FILE"
  echo "Paste auth.json from KeePass now, then press Enter."
  mkdir -p "$(dirname "$COMPOSER_AUTH_FILE")"
  read -r
fi

need_file "$COMPOSER_AUTH_FILE"
chmod 600 "$COMPOSER_AUTH_FILE"

if [ ! -f "$ENV_FILE" ]; then
  echo ".env not found at $ENV_FILE"
  echo "Paste .env from KeePass now, then press Enter."
  read -r
fi

need_file "$ENV_FILE"

echo "Running composer install..."
composer install

echo "Loading .env..."
set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

echo "Rendering env.php..."
php scripts/render-env.php

echo "Validating env.php..."
test -f app/etc/env.php
php -l app/etc/env.php

echo "Magento sanity checks..."
php bin/magento --version
php bin/magento module:status || true

echo "Local install/bootstrap completed."
