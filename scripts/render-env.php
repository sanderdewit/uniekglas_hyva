<?php

declare(strict_types=1);

const REQUIRED_ENV_VARS = [
    'BACKEND_FRONTNAME',
    'CRYPT_KEY',
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASSWORD',
    'MAGE_MODE',
    'REDIS_HOST',
    'REDIS_PORT',
    'REDIS_PASSWORD',
    'DOWNLOADABLE_DOMAIN',
    'INSTALL_DATE',
    'CACHE_ID_PREFIX',
    'GRAPHQL_ID_SALT',
];

$projectRoot = dirname(__DIR__);
$templatePath = $projectRoot . '/app/etc/env.php.template';
$outputPath = $projectRoot . '/app/etc/env.php';

if (!is_file($templatePath)) {
    fwrite(STDERR, "Template not found: {$templatePath}\n");
    exit(1);
}

$template = file_get_contents($templatePath);
if ($template === false) {
    fwrite(STDERR, "Failed to read template: {$templatePath}\n");
    exit(1);
}

$values = [];
$missing = [];

foreach (REQUIRED_ENV_VARS as $varName) {
    $value = getenv($varName);
    if ($value === false || $value === '') {
        $missing[] = $varName;
        continue;
    }
    $values[$varName] = $value;
}

if ($missing !== []) {
    fwrite(
        STDERR,
        "Missing required environment variables:\n - " . implode("\n - ", $missing) . "\n"
    );
    exit(1);
}

$rendered = $template;

foreach ($values as $varName => $value) {
    $rendered = str_replace('{{' . $varName . '}}', $value, $rendered);
}

if (!is_dir(dirname($outputPath))) {
    fwrite(STDERR, "Output directory does not exist: " . dirname($outputPath) . "\n");
    exit(1);
}

$result = file_put_contents($outputPath, $rendered);
if ($result === false) {
    fwrite(STDERR, "Failed to write output file: {$outputPath}\n");
    exit(1);
}

chmod($outputPath, 0640);

fwrite(STDOUT, "Generated {$outputPath}\n");
