#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/home/site/wwwroot}"

cd "$APP_DIR"

echo "Running Magento post-deploy tasks..."

php -d memory_limit=-1 bin/magento setup:upgrade
php bin/magento cache:clean
php bin/magento cache:flush

echo "Post-deploy tasks completed."
