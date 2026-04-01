#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/home/site/wwwroot}"

cd "$APP_DIR"

echo "Running Magento post-deploy tasks..."

php scripts/render-env.php
php -l app/etc/env.php

php bin/magento setup:upgrade
php bin/magento cache:clean
php bin/magento cache:flush
php bin/magento azureblob:static-content-export

echo "Post-deploy tasks completed."
