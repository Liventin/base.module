<?php
// scripts/post-install.php

$composerJsonPath = __DIR__ . '/../composer.json';
if (!file_exists($composerJsonPath)) {
    echo "Could not find composer.json at $composerJsonPath.\n";
    exit(1);
}

$composerData = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Failed to parse composer.json.\n";
    exit(1);
}

// Получаем имя модуля из поля name (берем вторую часть после слэша)
$moduleName = explode('/', $composerData['name'])[1] ?? null;
if (!$moduleName) {
    echo "Could not determine module name from composer.json.\n";
    exit(1);
}

$namespacePrefix = str_replace('.', '\\', ucwords($moduleName, '.'));

$replacements = [
    'base.module' => $moduleName,
    'Base\\Module' => $namespacePrefix,
    'base_module' => str_replace('.', '_', $moduleName),
];

$moduleDir = __DIR__ . '/..';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleDir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
        file_put_contents($file->getPathname(), $newContent);
    }
}

echo "Module namespace and variables updated for $moduleName\n";
