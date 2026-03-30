<?php
// Load Magento DB config
$env = include __DIR__ . '/app/etc/env.php';
$db = $env['db']['connection']['default'];

$mediaBase = __DIR__ . '/pub/media/catalog/product';
$outputFile = __DIR__ . '/product_images.csv';

try {
    $dbh = new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8mb4",
        $db['username'],
        $db['password']
    );

    $sql = "
        SELECT 
            e.sku,
            name.value AS product_name,
            img.value AS image_path
        FROM catalog_product_entity e
        LEFT JOIN catalog_product_entity_varchar name
            ON e.entity_id = name.entity_id
            AND name.attribute_id = (
                SELECT attribute_id FROM eav_attribute 
                WHERE attribute_code = 'name' AND entity_type_id = (
                    SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_product'
                )
            )
        LEFT JOIN catalog_product_entity_varchar img
            ON e.entity_id = img.entity_id
            AND img.attribute_id IN (
                SELECT attribute_id FROM eav_attribute 
                WHERE attribute_code IN ('image', 'small_image', 'thumbnail')
                AND entity_type_id = (
                    SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_product'
                )
            )
        WHERE img.value IS NOT NULL AND img.value != 'no_selection'
        GROUP BY e.entity_id, img.value
    ";

    $stmt = $dbh->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fp = fopen($outputFile, 'w');
    fputcsv($fp, ['Product Name', 'SKU', 'Image Path', 'Width', 'Height', 'Exists']);

    foreach ($rows as $row) {
        $relativePath = ltrim($row['image_path'], '/');
        $file = $mediaBase . '/' . $relativePath;

        if (file_exists($file)) {
            [$width, $height] = getimagesize($file);
            fputcsv($fp, [$row['product_name'], $row['sku'], $row['image_path'], $width, $height, 'Yes']);
        } else {
            fputcsv($fp, [$row['product_name'], $row['sku'], $row['image_path'], '', '', 'No']);
        }
    }

    fclose($fp);
    echo "Export complete: {$outputFile}\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

