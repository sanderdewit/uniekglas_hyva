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

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function env(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }

    $trimmed = trim($value);
    return $trimmed === '' ? $default : $trimmed;
}

function resolveTemplatePath(string $projectRoot): string
{
    $appEnv = env('APP_ENV');
    $baseTemplate = $projectRoot . '/app/etc/env.php.template';

    if ($appEnv === null) {
        return $baseTemplate;
    }

    if (!preg_match('/^[a-z0-9_-]+$/i', $appEnv)) {
        fail(sprintf('Invalid APP_ENV value: %s', $appEnv));
    }

    $envSpecificTemplate = sprintf(
        '%s/app/etc/env.%s.php.template',
        $projectRoot,
        strtolower($appEnv)
    );

    if (is_file($envSpecificTemplate)) {
        return $envSpecificTemplate;
    }

    return $baseTemplate;
}

function loadTemplate(string $templatePath): string
{
    if (!is_file($templatePath)) {
        fail(sprintf('Template not found: %s', $templatePath));
    }

    $template = file_get_contents($templatePath);
    if ($template === false) {
        fail(sprintf('Failed to read template: %s', $templatePath));
    }

    return $template;
}

function collectRequiredValues(array $requiredVars): array
{
    $values = [];
    $missing = [];

    foreach ($requiredVars as $varName) {
        $value = getenv($varName);
        if ($value === false || trim($value) === '') {
            $missing[] = $varName;
            continue;
        }

        $values[$varName] = $value;
    }

    if ($missing !== []) {
        fail(
            "Missing required environment variables:\n - " .
            implode("\n - ", $missing)
        );
    }

    return $values;
}

function renderTemplate(string $template, array $values): string
{
    $rendered = $template;

    foreach ($values as $varName => $value) {
        $rendered = str_replace('{{' . $varName . '}}', $value, $rendered);
    }

    if (preg_match_all('/{{([A-Z0-9_]+)}}/', $rendered, $matches) === false) {
        fail('Failed while checking for unresolved placeholders.');
    }

    $unresolved = array_values(array_unique($matches[1]));
    if ($unresolved !== []) {
        fail(
            "Unresolved placeholders remain in rendered template:\n - " .
            implode("\n - ", $unresolved)
        );
    }

    return $rendered;
}

function writeOutput(string $outputPath, string $contents): void
{
    $outputDir = dirname($outputPath);

    if (!is_dir($outputDir)) {
        fail(sprintf('Output directory does not exist: %s', $outputDir));
    }

    $result = file_put_contents($outputPath, $contents, LOCK_EX);
    if ($result === false) {
        fail(sprintf('Failed to write output file: %s', $outputPath));
    }

    if (!chmod($outputPath, 0640)) {
        fail(sprintf('Failed to set permissions on output file: %s', $outputPath));
    }
}

$projectRoot = dirname(__DIR__);
$templatePath = resolveTemplatePath($projectRoot);
$outputPath = $projectRoot . '/app/etc/env.php';

$template = loadTemplate($templatePath);
$values = collectRequiredValues(REQUIRED_ENV_VARS);
$rendered = renderTemplate($template, $values);

writeOutput($outputPath, $rendered);

fwrite(
    STDOUT,
    sprintf(
        "Generated %s using template %s%s",
        $outputPath,
        $templatePath,
        PHP_EOL
    )
);
