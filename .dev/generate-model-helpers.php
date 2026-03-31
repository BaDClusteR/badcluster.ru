<?php

/**
 * Writes @method annotations directly into model class files.
 *
 * Scans src/Model and modules/[star]/src/Model for classes extending AEntity,
 * reads their DS\Column, DS\Id, and DS\Reference attributes,
 * and writes PHPDoc @method tags into each model file.
 *
 * Replaces the entire PHPDoc block before the class on each run.
 * Usage: php generate-model-helpers.php
 */

declare(strict_types=1);

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

$projectRoot = dirname(__DIR__);

require_once $projectRoot . '/vendor/autoload.php';

$modelDirs = [
    'src/Model',
    ...glob($projectRoot . '/modules/*/src/Model', GLOB_ONLYDIR) ?: []
];

$modelFiles = [];

foreach ($modelDirs as $dir) {
    $fullDir = str_starts_with($dir, '/') ? $dir : $projectRoot . '/' . $dir;

    if (!is_dir($fullDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());

        if (!preg_match('/namespace\s+(.+?)\s*;/', $content, $nsMatch)) {
            continue;
        }

        if (!preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            continue;
        }

        $fqn = $nsMatch[1] . '\\' . $classMatch[1];

        if (!class_exists($fqn)) {
            continue;
        }

        $ref = new ReflectionClass($fqn);

        if (!$ref->isSubclassOf(AEntity::class) || $ref->isAbstract()) {
            continue;
        }

        $modelFiles[] = [
            'path' => $file->getPathname(),
            'ref'  => $ref,
        ];
    }
}

if (empty($modelFiles)) {
    echo "No model classes found.\n";
    exit(0);
}

$updatedCount = 0;

foreach ($modelFiles as $model) {
    $methods = collectMethods($model['ref']);
    $filePath = $model['path'];
    $content = file_get_contents($filePath);

    $newContent = updateFileContent($content, $methods);

    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent);
        $updatedCount++;
        $relative = str_replace($projectRoot . '/', '', $filePath);
        echo "  Updated: $relative\n";
    }
}

echo "$updatedCount model(s) updated.\n";

function updateFileContent(string $content, array $methods): string
{
    // Pattern: find an existing PHPDoc block right before the class declaration (or its attributes)
    // The class line may be preceded by #[...] attributes
    $classPattern = '/^(\/\*\*.*?\*\/\s*?)?((?:\s*#\[.+?\]\s*)*)(class\s+\w+)/ms';

    if (!preg_match($classPattern, $content, $match, PREG_OFFSET_CAPTURE)) {
        return $content;
    }

    $existingDocBlock = $match[1][0] ?? '';
    $attributes = $match[2][0];
    $classKeyword = $match[3][0];
    $fullMatchOffset = $match[0][1];
    $fullMatchLength = strlen($match[0][0]);

    if (empty($methods)) {
        // No methods — remove existing doc block if present
        if (!empty(trim($existingDocBlock))) {
            $replacement = $attributes . $classKeyword;
            return substr_replace($content, $replacement, $fullMatchOffset, $fullMatchLength);
        }

        return $content;
    }

    $docBlock = "/**\n";
    foreach ($methods as $method) {
        $docBlock .= " * $method\n";
    }
    $docBlock .= " */\n";

    if (empty(trim($existingDocBlock))) {
        // No existing doc block — add a blank line before the new one
        $replacement = "\n" . $docBlock . $attributes . $classKeyword;
    } else {
        // Replacing existing doc block — keep the same whitespace before attributes
        $replacement = $docBlock . ltrim($attributes, "\n") . $classKeyword;
    }

    return substr_replace($content, $replacement, $fullMatchOffset, $fullMatchLength);
}

/**
 * @return string[]
 */
function collectMethods(ReflectionClass $ref): array
{
    $methods = [];

    foreach ($ref->getProperties() as $prop) {
        if ($prop->getDeclaringClass()->getName() !== $ref->getName()) {
            continue;
        }

        $isId = !empty($prop->getAttributes(DS\Id::class));
        $isColumn = !empty($prop->getAttributes(DS\Column::class));
        $isRef = !empty($prop->getAttributes(DS\Reference::class));
        $hasNoGetter = !empty($prop->getAttributes(DS\NoGetter::class));
        $hasNoSetter = !empty($prop->getAttributes(DS\NoSetter::class));

        if (!$isId && !$isColumn && !$isRef) {
            continue;
        }

        $propName = $prop->getName();
        $capitalizedName = ucfirst($propName);

        if ($isId || $isColumn) {
            $typeStr = resolvePropertyType($prop);

            if (!$hasNoGetter) {
                $methods[] = "@method $typeStr get$capitalizedName()";
            }

            if (!$hasNoSetter) {
                $methods[] = "@method self set$capitalizedName($typeStr \$$propName)";
            }
        }

        if ($isRef) {
            $refAttr = $prop->getAttributes(DS\Reference::class)[0]->newInstance();

            if (!$hasNoGetter) {
                $refShort = '\\' . $refAttr->refModel;
                $methods[] = "@method {$refShort}[] get$capitalizedName()";
            }
        }
    }

    return $methods;
}

function resolvePropertyType(ReflectionProperty $prop): string
{
    $type = $prop->getType();

    if ($type === null) {
        return 'mixed';
    }

    if ($type instanceof ReflectionNamedType) {
        return formatNamedType($type);
    }

    if ($type instanceof ReflectionUnionType) {
        $parts = array_map(fn(ReflectionNamedType $t) => formatNamedType($t), $type->getTypes());
        return implode('|', $parts);
    }

    if ($type instanceof ReflectionIntersectionType) {
        $parts = array_map(fn(ReflectionNamedType $t) => formatNamedType($t), $type->getTypes());
        return implode('&', $parts);
    }

    return 'mixed';
}

function formatNamedType(ReflectionNamedType $type): string
{
    $name = $type->getName();

    if (!$type->isBuiltin()) {
        $name = '\\' . $name;
    }

    if ($type->allowsNull() && $name !== 'mixed' && $name !== 'null') {
        return $name . '|null';
    }

    return $name;
}
