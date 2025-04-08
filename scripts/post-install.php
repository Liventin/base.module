<?php

echo "Starting post-install script...\n";

$moduleDir = dirname(__DIR__, 4);
echo "Module directory: $moduleDir\n";

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

$moduleName = explode('/', $composerData['name'])[1] ?? null;
echo "Determined module name: $moduleName\n";
if (!$moduleName) {
    echo "Could not determine module name from composer.json.\n";
    exit(1);
}

$namespacePrefix = str_replace('.', '\\', ucwords($moduleName, '.'));
echo "Namespace prefix: $namespacePrefix\n";

$vendorDir = dirname(__DIR__, 3);
$packageDir = $vendorDir . '/liventin/base.module';
echo "Package directory: $packageDir\n";
if (!is_dir($packageDir)) {
    echo "Could not find package directory at $packageDir.\n";
    exit(1);
}

echo "Moving files from $packageDir to $moduleDir...\n";
$excludePaths = [
    $packageDir . '/scripts',
    $packageDir . '/composer.json',
    $packageDir . '/README.md'
];

$protectedPaths = [
    '.settings.php' => '.settings.php',
    'default_option.php' => 'default_option.php',
    'include.php' => 'include.php',
    'prolog.php' => 'prolog.php',
    'lang/ru/install/index.php' => 'index.php',
    'install/version.php' => 'version.php'
];

$movedFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($packageDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
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

$replacements = [
    'base.module' => $moduleName,
    'Base\\Module' => $namespacePrefix,
    'base_module' => str_replace('.', '_', $moduleName),
];
$replacements['BASE_MODULE'] = strtoupper($replacements['base_module']);

echo "Applying replacements in moved PHP files...\n";
foreach ($movedFiles as $filePath) {
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

echo "Cleaning up empty directories in $packageDir...\n";
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

removeEmptyDirectories($packageDir);

echo "Module namespace and variables updated for $moduleName\n";
error_log("Post-install script completed for $moduleName", 0);
