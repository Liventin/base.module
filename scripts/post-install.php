<?php
// post-install.php

echo "Starting post-install script...\n";

// Определяем корневую директорию модуля (где находится composer.json для установки)
$moduleDir = dirname(__DIR__, 4);
echo "Module directory: $moduleDir\n";

// Находим composer.json в корне модуля
$composerJsonPath = $moduleDir . '/composer.json';
echo "Looking for composer.json at: $composerJsonPath\n";
if (!file_exists($composerJsonPath)) {
    echo "Could not find composer.json at $composerJsonPath.\n";
    exit(1);
}

try {
    $composerData = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    echo "Failed to parse composer.json: " . $e->getMessage() . "\n";
    exit(1);
}
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

// Читаем параметр service-redirect из composer.json
$serviceRedirects = $composerData['extra']['service-redirect'] ?? [];
echo "Service redirects: " . json_encode($serviceRedirects, JSON_THROW_ON_ERROR) . "\n";

// Определяем директорию vendor/
$vendorDir = dirname(__DIR__, 3);
echo "Vendor directory: $vendorDir\n";

// Список пакетов для обработки (начинаем с liventin/base.module)
$packagesToProcess = ['liventin/base.module'];

// Ищем другие пакеты в vendor/, которые зависят от liventin/base.module
$vendorIterator = new DirectoryIterator($vendorDir);
foreach ($vendorIterator as $vendorItem) {
    if (!$vendorItem->isDir() || $vendorItem->isDot()) {
        continue;
    }

    $vendorName = $vendorItem->getFilename();
    $packageIterator = new DirectoryIterator($vendorItem->getPathname());
    foreach ($packageIterator as $packageItem) {
        if (!$packageItem->isDir() || $packageItem->isDot()) {
            continue;
        }

        $packageName = $vendorName . '/' . $packageItem->getFilename();
        if ($packageName === 'liventin/base.module') {
            continue; // Уже добавлен в список
        }

        $packageComposerJson = $packageItem->getPathname() . '/composer.json';
        if (!file_exists($packageComposerJson)) {
            continue;
        }

        try {
            $packageData = json_decode(file_get_contents($packageComposerJson), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo "Failed to parse composer.json for $packageName: " . $e->getMessage() . "\n";
            continue;
        }

        // Проверяем, зависит ли пакет от liventin/base.module
        $requiresBaseModule = isset($packageData['require']['liventin/base.module']);
        if ($requiresBaseModule) {
            echo "Found dependent package: $packageName\n";
            $packagesToProcess[] = $packageName;
        }
    }
}

// Общие настройки для всех пакетов
$excludePathsBase = [
    '/scripts',
    '/composer.json',
    '/README.md'
];

$protectedPaths = [
    '.settings.php' => '.settings.php',
    'default_option.php' => 'default_option.php',
    'include.php' => 'include.php',
    'prolog.php' => 'prolog.php',
    'lang/ru/install/index.php' => 'index.php',
    'install/version.php' => 'version.php'
];

$replacements = [
    'base.module' => $moduleName,
    'Base\\Module' => $namespacePrefix,
    'base_module' => str_replace('.', '_', $moduleName),
];
$replacements['BASE_MODULE'] = strtoupper($replacements['base_module']);

// Определяем путь к папке vendor/, которую нужно исключить из замены контента
$vendorPath = $moduleDir . '/vendor/';


// Проверяем наличие .settings.php в корне модуля и копируем, если отсутствует
$rootSettingsPath = $moduleDir . '/.settings.php';
if (!file_exists($rootSettingsPath)) {
    $baseModuleSettingsPath = $vendorDir . '/liventin/base.module/.settings.php';
    if (file_exists($baseModuleSettingsPath)) {
        echo "Root .settings.php not found, copying from $baseModuleSettingsPath...\n";
        copy($baseModuleSettingsPath, $rootSettingsPath);
    } else {
        echo "No .settings.php found in base.module to copy\n";
    }
}

// Обрабатываем каждый пакет
foreach ($packagesToProcess as $package) {
    $packageDir = $vendorDir . '/' . $package;
    echo "Processing package: $package\n";
    echo "Package directory: $packageDir\n";
    if (!is_dir($packageDir)) {
        echo "Could not find package directory at $packageDir.\n";
        continue;
    }

    // Проверяем, есть ли перенаправление для этого пакета
    $redirectModule = $serviceRedirects[$package] ?? null;
    $hasRedirect = !empty($redirectModule);
    if ($hasRedirect) {
        echo "Service redirect for $package: using implementation from $redirectModule\n";
    }

    // Формируем excludePaths для текущего пакета
    $excludePaths = array_map(static function ($path) use ($packageDir) {
        return rtrim($packageDir . $path, '/\\');
    }, $excludePathsBase);

    // Если есть перенаправление, исключаем папку lib/Src/
    if ($hasRedirect) {
        $excludePaths[] = rtrim($packageDir . '/lib/Src', '/\\');
    }

    // Отладочный вывод excludePaths
    echo "Exclude paths for $package: " . json_encode($excludePaths, JSON_THROW_ON_ERROR) . "\n";

    // Теперь перемещаем файлы
    $movedFiles = [];
    echo "Moving files from $packageDir to $moduleDir...\n";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($packageDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $itemPath = $item->getPathname();
        $normalizedItemPath = rtrim(str_replace('\\', '/', $itemPath), '/');
        $shouldSkip = false;
        foreach ($excludePaths as $excludePath) {
            $normalizedExcludePath = rtrim(str_replace('\\', '/', $excludePath), '/');
            if (stripos($normalizedItemPath, $normalizedExcludePath) === 0) {
                $shouldSkip = true;
                echo "Skipping path: $itemPath (matches $excludePath)\n";
                break;
            }
        }
        if ($shouldSkip) {
            continue;
        }

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
            $isProtected = false;
            $fileName = basename($itemPath);
            foreach ($protectedPaths as $protectedPath => $protectedFileName) {
                if ($relativePath === $protectedPath && $fileName === $protectedFileName) {
                    $isProtected = true;
                    break;
                }
            }

            if ($isProtected) {
                if (file_exists($targetPath)) {
                    echo "File $fileName at $relativePath already exists at $targetPath, removing from source: $itemPath\n";
                    unlink($itemPath);
                } else {
                    echo "Moving protected file: $itemPath to $targetPath\n";
                    rename($itemPath, $targetPath);
                    $movedFiles[] = $targetPath;
                }
            } else {
                echo "Moving file: $itemPath to $targetPath\n";
                rename($itemPath, $targetPath);
                $movedFiles[] = $targetPath;
            }
        }
    }


    // Применяем замены namespace и других переменных только для перенесённых файлов, исключая папку vendor/
    echo "Applying replacements in moved PHP files (excluding vendor/) for $package...\n";
    foreach ($movedFiles as $filePath) {
        // Пропускаем файлы, которые находятся в $moduleDir/vendor/
        if (str_starts_with($filePath, $vendorPath)) {
            echo "Skipping file in vendor/: $filePath\n";
            continue;
        }

        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
            echo "Processing moved file: $filePath\n";
            $content = file_get_contents($filePath);
            $newContent = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $content
            );
            file_put_contents($filePath, $newContent);
        }
    }

    // Если есть перенаправление, обновляем файлы в service_locator
    if ($hasRedirect) {
        $serviceLocatorDir = $moduleDir . '/service_locator';
        if (is_dir($serviceLocatorDir)) {
            $redirectNamespacePrefix = str_replace('.', '\\', ucwords($redirectModule, '.'));
            echo "Updating service_locator files with redirect to $redirectNamespacePrefix...\n";

            $iterator = new DirectoryIterator($serviceLocatorDir);
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDot() || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $filePath = $fileInfo->getPathname();
                $content = file_get_contents($filePath);

                // Парсим содержимое файла
                $returnPos = strpos($content, 'return ');
                if ($returnPos !== false) {
                    $beforeReturn = substr($content, 0, $returnPos);
                    $arrayContent = substr($content, $returnPos + 7, -2); // Убираем "return " и ";\n"
                    $arrayContent = trim($arrayContent);

                    // Извлекаем className
                    $classNameStart = strpos($arrayContent, "'className' => ");
                    if ($classNameStart !== false) {
                        $classNameStart += 14; // Длина "'className' => "
                        $classNameEnd = strpos($arrayContent, ',', $classNameStart);
                        if ($classNameEnd !== false) {
                            $className = substr($arrayContent, $classNameStart, $classNameEnd - $classNameStart);
                            $className = trim($className, " \t\n\r\0\x0B'\"");
                            $className = str_replace('::class', '', $className);

                            $lastSlashPos = strrpos($className, '\\');
                            if ($lastSlashPos !== false) {
                                $classNamespace = substr($className, 0, $lastSlashPos);
                                $classOnly = substr($className, $lastSlashPos + 1);
                                $updatedNamespace = str_replace('Base\\Module', $redirectNamespacePrefix, $classNamespace);
                                $newClassName = $updatedNamespace . '\\' . $classOnly . '::class';

                                // Заменяем className в содержимом
                                $newArrayContent = substr($arrayContent, 0, $classNameStart) . $newClassName . substr($arrayContent, $classNameEnd);

                                // Собираем новый контент файла
                                $newContent = $beforeReturn . "return " . $newArrayContent . ";\n";
                                file_put_contents($filePath, $newContent);
                                echo "Updated $filePath with redirected className: $newClassName\n";
                            }
                        }
                    }
                }
            }
        }
    }


    // Если есть перенаправление, удаляем папку lib/Src из vendor
    if ($hasRedirect) {
        $srcDir = $packageDir . '/lib/Src';
        if (is_dir($srcDir)) {
            echo "Removing lib/Src directory from $packageDir due to service redirect...\n";
            removeDirectory($srcDir);
        }
    }

    // Удаляем пустые директории в $packageDir
    echo "Cleaning up empty directories in $packageDir...\n";
    removeEmptyDirectories($packageDir);
}

function removeEmptyDirectories(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            $subDir = $item->getPathname();
            $files = array_diff(scandir($subDir), ['.', '..']);
            if (empty($files)) {
                echo "Removing empty directory: $subDir\n";
                rmdir($subDir);
            }
        }
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    if (empty($files)) {
        echo "Removing empty root directory: $dir\n";
        rmdir($dir);
    }
}

function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}

echo "Module namespace and variables updated for $moduleName\n";
error_log("Post-install script completed for $moduleName");
