#!/bin/bash

set -e

echo "🔎 Starting Magento Orphaned Images Cleaner..."

# Magento 2 root directory
MAGENTO_ROOT=$(pwd)

# Reading DB credentials from env.php
echo "🔄 Reading database credentials from app/etc/env.php..."

DB_USER=$(php -r "\$env = include 'app/etc/env.php'; echo \$env['db']['connection']['default']['username'];")
DB_PASS=$(php -r "\$env = include 'app/etc/env.php'; echo \$env['db']['connection']['default']['password'];")
DB_NAME=$(php -r "\$env = include 'app/etc/env.php'; echo \$env['db']['connection']['default']['dbname'];")

# Temp files
DB_IMAGES_FILE="db_images.txt"
FS_IMAGES_FILE="fs_images.txt"
ORPHAN_IMAGES_FILE="orphan_images.txt"

# Step 1: Export image filenames from DB
echo "📦 Exporting image paths from database..."
mysql --user="$DB_USER" --password="$DB_PASS" "$DB_NAME" -Bse "
SELECT mg.value
FROM catalog_product_entity_media_gallery mg
JOIN catalog_product_entity_media_gallery_value_to_entity mgvte
  ON mg.value_id = mgvte.value_id
JOIN catalog_product_entity p
  ON mgvte.entity_id = p.entity_id
WHERE p.type_id IN ('simple', 'virtual', 'configurable');
" | sort > "$DB_IMAGES_FILE"

# Step 2: Export filesystem image filenames
echo "📁 Scanning filesystem for images..."
find pub/media/catalog/product -type f \
    \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" -o -iname "*.gif" \) \
    ! -path "pub/media/catalog/product/cache/*" \
    | sed 's/^pub\/media\/catalog\/product/''/' | sort > "$FS_IMAGES_FILE"

# Step 3: Find orphaned files
echo "🔍 Comparing filesystem and database..."
comm -23 "$FS_IMAGES_FILE" "$DB_IMAGES_FILE" > "$ORPHAN_IMAGES_FILE"

# Count
ORPHAN_COUNT=$(wc -l < "$ORPHAN_IMAGES_FILE")
echo "✅ Found $ORPHAN_COUNT orphaned images."

# Step 4: Ask to export full list
read -p "📄 Do you want to export the orphaned files list? (yes/no): " export_answer
if [[ "$export_answer" == "yes" ]]; then
    EXPORT_FILE="orphaned_images_export_$(date +%Y%m%d%H%M%S).txt"
    cp "$ORPHAN_IMAGES_FILE" "$EXPORT_FILE"
    echo "📝 Exported orphaned file list to: $EXPORT_FILE"
fi

# Step 5: Ask to move or delete
read -p "🛑 Do you want to move the orphaned images to pub/media/catalog/product/_orphaned/? (yes/no): " move_answer
if [[ "$move_answer" == "yes" ]]; then
    mkdir -p pub/media/catalog/product/_orphaned/
    while IFS= read -r file; do
        if [ -f "pub/media/catalog/product/$file" ]; then
            mkdir -p "$(dirname "pub/media/catalog/product/_orphaned/$file")"
            mv "pub/media/catalog/product/$file" "pub/media/catalog/product/_orphaned/$file"
        fi
    done < "$ORPHAN_IMAGES_FILE"
    echo "✅ Moved orphaned images to _orphaned/ folder."
else
    echo "ℹ️ No files were moved."
fi

# Cleanup temp files
rm -f "$DB_IMAGES_FILE" "$FS_IMAGES_FILE" "$ORPHAN_IMAGES_FILE"

echo "🎉 Done!"

