<?php
// post-install.php

echo "Starting post-install script...\n";

// Определяем корневую директорию модуля
$moduleDir = dirname(__DIR__, 4);
echo "Module directory: $moduleDir\n";

// Читаем composer.json
$composerJsonPath = "$moduleDir/composer.json";
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

// Извлекаем имя модуля и формируем namespace
$moduleName = explode('/', $composerData['name'])[1] ?? throw new RuntimeException(
    "Could not determine module name from composer.json."
);
$namespacePrefix = str_replace('.', '\\', ucwords($moduleName, '.'));
echo "Module name: $moduleName, Namespace prefix: $namespacePrefix\n";

// Читаем service-redirect
$serviceRedirects = $composerData['extra']['service-redirect'] ?? [];
echo "Service redirects: " . json_encode($serviceRedirects, JSON_THROW_ON_ERROR) . "\n";

// Определяем директорию vendor/
$vendorDir = dirname(__DIR__, 3);
echo "Vendor directory: $vendorDir\n";

// Находим пакеты, зависящие от liventin/base.module
$packagesToProcess = ['liventin/base.module'];
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

        $packageName = "$vendorName/{$packageItem->getFilename()}";
        if ($packageName === 'liventin/base.module') {
            continue;
        }

        $packageComposerJson = "{$packageItem->getPathname()}/composer.json";
        if (!file_exists($packageComposerJson)) {
            continue;
        }

        try {
            $packageData = json_decode(file_get_contents($packageComposerJson), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo "Failed to parse composer.json for $packageName: " . $e->getMessage() . "\n";
            continue;
        }

        if (isset($packageData['require']['liventin/base.module'])) {
            echo "Found dependent package: $packageName\n";
            $packagesToProcess[] = $packageName;
        }
    }
}

// Настройки исключений и замен
$excludePathsBase = ['/scripts', '/composer.json', '/README.md', '/service_locator'];
$protectedPaths = [
    '.settings.php' => '.settings.php',
    'default_option.php' => 'default_option.php',
    'include.php' => 'include.php',
    'prolog.php' => 'prolog.php',
    'lang/ru/install/index.php' => 'index.php',
    'install/version.php' => 'version.php',
];
$replacements = [
    'base.module' => $moduleName,
    'Base\\Module' => $namespacePrefix,
    'base_module' => str_replace('.', '_', $moduleName),
    'BASE_MODULE' => strtoupper(str_replace('.', '_', $moduleName)),
];
$vendorPath = "$moduleDir/vendor/";

// Копируем .settings.php, если отсутствует
$rootSettingsPath = "$moduleDir/.settings.php";
if (!file_exists($rootSettingsPath)) {
    $baseModuleSettingsPath = "$vendorDir/liventin/base.module/.settings.php";
    if (file_exists($baseModuleSettingsPath)) {
        echo "Root .settings.php not found, copying from $baseModuleSettingsPath...\n";
        copy($baseModuleSettingsPath, $rootSettingsPath);
    } else {
        echo "No .settings.php found in base.module to copy\n";
    }
}

// Функция для обновления содержимого файла service_locator
function updateServiceLocatorFile(string $filePath, string $moduleName, string $redirectModule = null): void
{
    $content = file_get_contents($filePath);
    $returnPos = strpos($content, 'return ');
    if ($returnPos === false) {
        return;
    }


    $beforeReturn = substr($content, 0, $returnPos);
    $arrayContent = substr($content, $returnPos + 7, -2); // Убираем "return " и ";\n"
    $arrayContent = trim($arrayContent);

    // Заменяем ключ сервиса (base.module -> $moduleName)
    $keyStart = strpos($arrayContent, "'base.module.");
    if ($keyStart !== false) {
        $keyEnd = strpos($arrayContent, "' =>", $keyStart);
        if ($keyEnd !== false) {
            $oldKey = substr($arrayContent, $keyStart, $keyEnd - $keyStart + 1);
            $newKey = "'$moduleName." . substr($oldKey, strlen("'base.module."));
            $arrayContent = str_replace($oldKey, $newKey, $arrayContent);
        }
    }

    // Если есть перенаправление, обновляем className и constructorParams
    if ($redirectModule) {
        $redirectNamespacePrefix = str_replace('.', '\\', ucwords($redirectModule, '.'));

        // Обновляем className
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
                    $arrayContent = substr($arrayContent, 0, $classNameStart) . $newClassName . substr(
                            $arrayContent,
                            $classNameEnd
                        );
                }
            }
        }

        // Обновляем constructorParams
        $paramsStart = strpos($arrayContent, "'constructorParams' => [");
        if ($paramsStart !== false) {
            $paramsStart += 23; // Длина "'constructorParams' => ["
            $paramsEnd = strpos($arrayContent, ']', $paramsStart);
            if ($paramsEnd !== false) {
                $paramsContent = substr($arrayContent, $paramsStart, $paramsEnd - $paramsStart);
                $newParamsContent = str_replace('base.module', $moduleName, $paramsContent);
                $arrayContent = substr($arrayContent, 0, $paramsStart) . $newParamsContent . substr(
                        $arrayContent,
                        $paramsEnd
                    );
            }
        }
    }

    $newContent = $beforeReturn . "return " . $arrayContent . ";\n";
    file_put_contents($filePath, $newContent);
    echo "Updated $filePath with redirected content\n";
}

// Функция для удаления директории
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
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

// Функция для удаления пустых директорий
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
        if ($item->isDir() && !count(array_diff(scandir($item->getPathname()), ['.', '..']))) {
            echo "Removing empty directory: {$item->getPathname()}\n";
            rmdir($item->getPathname());
        }
    }
    if (!count(array_diff(scandir($dir), ['.', '..']))) {
        echo "Removing empty root directory: $dir\n";
        rmdir($dir);
    }
}


// Обрабатываем каждый пакет
foreach ($packagesToProcess as $package) {
    $packageDir = "$vendorDir/$package";
    echo "Processing package: $package\n";
    if (!is_dir($packageDir)) {
        echo "Could not find package directory at $packageDir.\n";
        continue;
    }

    // Проверяем перенаправление
    $redirectModule = $serviceRedirects[$package] ?? null;
    $hasRedirect = !empty($redirectModule);
    if ($hasRedirect) {
        echo "Service redirect for $package: using implementation from $redirectModule\n";
    }

    // Формируем пути исключения
    $excludePaths = array_map(fn($path) => rtrim("$packageDir$path", '/\\'), $excludePathsBase);
    if ($hasRedirect) {
        $excludePaths[] = rtrim("$packageDir/lib/Src", '/\\');
    }
    echo "Exclude paths for $package: " . json_encode($excludePaths, JSON_THROW_ON_ERROR) . "\n";

    // Перемещаем файлы (без service_locator, lib/Src копируется, если нет перенаправления)
    $movedFiles = [];
    echo "Moving files from $packageDir to $moduleDir...\n";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($packageDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $itemPath = $item->getPathname();
        $normalizedItemPath = str_replace('\\', '/', $itemPath);
        if (in_array(true, array_map(fn($path) => str_starts_with($normalizedItemPath, $path), $excludePaths), true)) {
            echo "Skipping path: $itemPath\n";
            continue;
        }

        $relativePath = substr($itemPath, strlen($packageDir) + 1);
        $targetPath = "$moduleDir/$relativePath";

        if ($item->isDir()) {
            if (
                !is_dir($targetPath) &&
                !mkdir($targetPath, 0755, true) &&
                !is_dir($targetPath)
            ) {
                throw new RuntimeException("Directory '$targetPath' was not created");
            }
            echo "Created directory: $targetPath\n";
        } else {
            $fileName = basename($itemPath);
            $isProtected = array_key_exists(
                    $relativePath,
                    $protectedPaths
                ) && $protectedPaths[$relativePath] === $fileName;

            if ($isProtected && file_exists($targetPath)) {
                echo "File $fileName at $relativePath already exists, removing from source: $itemPath\n";
                unlink($itemPath);
            } else {
                echo "Moving file: $itemPath to $targetPath\n";
                rename($itemPath, $targetPath);
                $movedFiles[] = $targetPath;
            }
        }
    }

    // Применяем замены в перенесённых файлах
    echo "Applying replacements in moved PHP files (excluding vendor/)...\n";
    foreach ($movedFiles as $filePath) {
        if (str_starts_with($filePath, $vendorPath)) {
            echo "Skipping file in vendor/: $filePath\n";
            continue;
        }

        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
            echo "Processing moved file: $filePath\n";
            file_put_contents(
                $filePath,
                str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    file_get_contents($filePath)
                )
            );
        }
    }

    // Обрабатываем service_locator
    $packageServiceLocatorDir = "$packageDir/service_locator";
    $targetServiceLocatorDir = "$moduleDir/service_locator";
    $shouldProcessServiceLocator = $hasRedirect || ($package === 'liventin/base.module' && file_exists(
                "$packageServiceLocatorDir/class.list.php"
            ));

    // Если нет перенаправления, удаляем файлы из service_locator модуля (кроме class.list.php)
    if (!$hasRedirect && is_dir($targetServiceLocatorDir)) {
        $iterator = new DirectoryIterator($targetServiceLocatorDir);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }
            if ($fileInfo->getFilename() === 'class.list.php') {
                continue;
            }


            $filePath = $fileInfo->getPathname();
            unlink($filePath);
            echo "Removed $filePath from module service_locator (no redirect)\n";
        }
        removeEmptyDirectories($targetServiceLocatorDir);
    }

    // Копируем файлы из service_locator пакета, если нужно
    if ($shouldProcessServiceLocator && is_dir($packageServiceLocatorDir)) {
        if (
            !is_dir($targetServiceLocatorDir) &&
            !mkdir($targetServiceLocatorDir, 0755, true) &&
            !is_dir($targetServiceLocatorDir)
        ) {
            throw new RuntimeException("Directory '$targetServiceLocatorDir' was not created");
        }

        $iterator = new DirectoryIterator($packageServiceLocatorDir);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }
            if (!$hasRedirect && !($package === 'liventin/base.module' && $fileInfo->getFilename(
                    ) === 'class.list.php')) {
                continue;
            }

            $sourceFile = $fileInfo->getPathname();
            $targetFile = "$targetServiceLocatorDir/{$fileInfo->getFilename()}";
            copy($sourceFile, $targetFile);
            echo "Copied $sourceFile to $targetFile\n";

            // Обновляем содержимое файла
            updateServiceLocatorFile($targetFile, $moduleName, $hasRedirect ? $redirectModule : null);
        }
    }

    // Удаляем service_locator из пакета, если нет перенаправления
    if (!$hasRedirect && is_dir($packageServiceLocatorDir)) {
        echo "Removing service_locator directory from $packageDir (no redirect)...\n";
        removeDirectory($packageServiceLocatorDir);
    }

    // Удаляем lib/Src из пакета, если есть перенаправление
    if ($hasRedirect) {
        $srcDir = "$packageDir/lib/Src";
        if (is_dir($srcDir)) {
            echo "Removing lib/Src directory from $packageDir due to service redirect...\n";
            removeDirectory($srcDir);
        }
    }

    // Удаляем пустые директории в пакете
    echo "Cleaning up empty directories in $packageDir...\n";
    removeEmptyDirectories($packageDir);
}

// Сбрасываем кэш для текущего модуля
$bitrixRoot = dirname($moduleDir, 2); // Предполагаем, что модуль находится в local/modules/
$cacheDir = "service_locator/{$moduleName}";
$cachePath = "$bitrixRoot/bitrix/cache/$cacheDir";
if (is_dir($cachePath)) {
    removeDirectory($cachePath);
    echo "Cleared service locator cache for module $moduleName at $cachePath\n";
} else {
    echo "No cache to clear for module $moduleName at $cachePath\n";
}

echo "Module namespace and variables updated for $moduleName\n";
error_log("Post-install script completed for $moduleName");
