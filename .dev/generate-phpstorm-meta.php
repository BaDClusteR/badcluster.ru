<?php

/**
 * Generates .phpstorm.meta.php from YAML service configs.
 *
 * Usage: php generate-phpstorm-meta.php
 */

$projectRoot = dirname(__DIR__);

$yamlDirs = [
    'vendor/bad_cluster/runway/config',
    'config',
    'vendor/bad_cluster/runway-console-app/config',
    ...array_map(
        fn(string $path) => str_replace($projectRoot . '/', '', $path),
        glob($projectRoot . '/modules/*/config', GLOB_ONLYDIR) ?: []
    )
];

$services = [];

foreach ($yamlDirs as $dir) {
    $fullDir = $projectRoot . '/' . $dir;

    if (!is_dir($fullDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'yaml') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $parsed = yaml_parse($content);

        if (!is_array($parsed) || empty($parsed['services'])) {
            continue;
        }

        $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());

        foreach ($parsed['services'] as $serviceName => $serviceConfig) {
            if ($serviceConfig === null) {
                // Service name equals class name (shorthand notation: ServiceName: ~)
                $services[$relativePath][$serviceName] = $serviceName;
            } elseif (is_array($serviceConfig) && !empty($serviceConfig['class'])) {
                $services[$relativePath][$serviceName] = $serviceConfig['class'];
            }
        }
    }
}

// Build the map entries
$mapEntries = '';

foreach ($services as $file => $fileServices) {
    $mapEntries .= "        // $file\n";

    foreach ($fileServices as $serviceName => $className) {
        $mapEntries .= "        '$serviceName' => \\$className::class,\n";
    }

    $mapEntries .= "\n";
}

$mapEntries = rtrim($mapEntries);

$output = <<<PHP
<?php

namespace PHPSTORM_META
{
    override(\Runway\Singleton\Container::getService(0), map([
$mapEntries
    ]));

    override(\Runway\Singleton\Container::tryGetService(0), map([
$mapEntries
    ]));
}
PHP;

$metaPath = $projectRoot . '/.phpstorm.meta.php';
file_put_contents($metaPath, $output . "\n");

$serviceCount = array_sum(array_map('count', $services));
$fileCount = count($services);

echo ".phpstorm.meta.php generated: $serviceCount services from $fileCount files.\n";