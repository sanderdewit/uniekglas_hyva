#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${1:-$PWD}"
ARTIFACT_DIR="${2:-$PROJECT_DIR/.artifacts}"
RELEASE_DIR="${ARTIFACT_DIR}/release"
ZIP_PATH="${ARTIFACT_DIR}/magento-appservice-release.zip"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Missing required command: $1" >&2
    exit 1
  }
}

need_cmd php
need_cmd composer
need_cmd rsync
need_cmd zip
need_cmd npm

cd "$PROJECT_DIR"

test -f composer.json
test -f bin/magento
test -f scripts/render-env.php
test -f scripts/nginx.default

rm -rf "$RELEASE_DIR"
mkdir -p "$RELEASE_DIR" "$ARTIFACT_DIR"

echo "Installing PHP dependencies for production..."
composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader

echo "Building Hyva assets..."
bash scripts/build_hyva.sh "$PROJECT_DIR"

echo "Compiling DI..."
php -d memory_limit=-1 bin/magento setup:di:compile

echo "Deploying static content..."
php -d memory_limit=-1 bin/magento setup:static-content:deploy -f en_US nl_NL

echo "Exporting static content to Azure Blob..."
php bin/magento azureblob:static-content-export

echo "Preparing release directory..."
rsync -a \
  --delete \
  --exclude '.git' \
  --exclude '.github' \
  --exclude '.idea' \
  --exclude '.vscode' \
  --exclude '.artifacts' \
  --exclude 'node_modules' \
  --exclude '.env' \
  --exclude 'auth.json' \
  --exclude 'var/cache/*' \
  --exclude 'var/page_cache/*' \
  --exclude 'var/log/*' \
  --exclude 'var/tmp/*' \
  --exclude 'pub/media/catalog/product/cache/*' \
  ./ "$RELEASE_DIR"/

rm -f "$RELEASE_DIR/app/etc/env.php"

echo "Creating ZIP artifact..."
(
  cd "$RELEASE_DIR"
  zip -qr "$ZIP_PATH" .
)

echo "Release package created:"
echo "  $ZIP_PATH"
