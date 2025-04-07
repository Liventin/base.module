<?php
// scripts/post-install.php

echo "Starting post-install script...\n";

// Определяем корневую директорию модуля (где находится composer.json для установки)
$moduleDir = dirname(__DIR__, 3);
echo "Module directory: $moduleDir\n";

// Находим composer.json в корне модуля
$composerJsonPath = $moduleDir . '/composer.json';
echo "Looking for composer.json at: $composerJsonPath\n";
if (!file_exists($composerJsonPath)) {
    echo "Could not find composer.json at $composerJsonPath.\n";
    exit(1);
}

$composerData = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Failed to parse composer.json: " . json_last_error_msg() . "\n";
    exit(1);
}

// Получаем имя модуля из поля name (берем вторую часть после слэша)
$moduleName = explode('/', $composerData['name'])[1] ?? null;
echo "Determined module name: $moduleName\n";
if (!$moduleName) {
    echo "Could not determine module name from composer.json.\n";
    exit(1);
}

// Формируем namespace на основе имени модуля
$namespacePrefix = str_replace('.', '\\', ucwords($moduleName, '.'));
echo "Namespace prefix: $namespacePrefix\n";

// Определяем директорию пакета в vendor
$vendorDir = dirname(__DIR__, 3) . '/';
$packageDir = $vendorDir . '/liventin/base.module';
echo "Package directory: $packageDir\n";
if (!is_dir($packageDir)) {
    echo "Could not find package directory at $packageDir.\n";
    exit(1);
}

// Перемещаем файлы из vendor/liventin/base.module/ в корень модуля, исключая scripts/ и composer.json
echo "Moving files from $packageDir to $moduleDir...\n";
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($packageDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$excludePaths = [
    $packageDir . '/scripts',
    $packageDir . '/composer.json'
];

foreach ($iterator as $item) {
    // Пропускаем исключённые пути
    $itemPath = $item->getPathname();
    $shouldSkip = false;
    foreach ($excludePaths as $excludePath) {
        if (str_starts_with($itemPath, $excludePath)) {
            $shouldSkip = true;
            break;
        }
    }
    if ($shouldSkip) {
        continue;
    }

    // Определяем целевой путь
    $relativePath = substr($itemPath, strlen($packageDir) + 1);
    $targetPath = $moduleDir . '/' . $relativePath;

    if ($item->isDir()) {
        if (!is_dir($targetPath)) {
            if (!mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $targetPath));
            }
            echo "Created directory: $targetPath\n";
        }
    } else {
        echo "Moving file: $itemPath to $targetPath\n";
        rename($itemPath, $targetPath);
    }
}

// Применяем замены namespace и других переменных
$replacements = [
    'base.module' => $moduleName,
    'Base\\Module' => $namespacePrefix,
    'base_module' => str_replace('.', '_', $moduleName),
];

echo "Applying replacements in PHP files...\n";
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleDir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        echo "Processing file: " . $file->getPathname() . "\n";
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
