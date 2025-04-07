<?php

namespace Base\Module\Src\Tool;

use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use ReflectionClass;
use SplFileInfo;
use CallbackFilterIterator;

class ClassList
{
    private string $moduleCode;
    private array $subClassesFilter = [];
    private string $basePath;
    private string $namespacePrefix;

    public function __construct(string $moduleCode)
    {
        $this->moduleCode = $moduleCode;
        $this->basePath = $this->getLibPath();
        $this->namespacePrefix = str_replace('.', '\\', ucwords($this->moduleCode, '.'));
    }

    public function setSubClassesFilter(array $subClassesFilter): self
    {
        $this->subClassesFilter = array_filter($subClassesFilter);
        return $this;
    }

    public function getModuleCode(): string
    {
        return $this->moduleCode;
    }

    public function getFromLib(string $relativePath): array
    {
        $classList = [];
        $fullPath = $this->basePath . '/' . trim($relativePath, '/');

        if (!is_dir($fullPath)) {
            return $classList;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $filteredIterator = new CallbackFilterIterator($iterator, function (SplFileInfo $file) {
            return $file->isFile() && $file->getExtension() === 'php';
        });

        foreach ($filteredIterator as $file) {
            $className = $this->getClassNameFromFile(new File($file->getPathname()));
            if ($className && class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if ($reflection->isInterface() || $reflection->isAbstract()) {
                    continue;
                }
                if ($this->passesFilter($className)) {
                    $classList[] = $className;
                }
            }
        }

        return $classList;
    }

    private function getClassNameFromFile(File $file): ?string
    {
        $relativePath = str_replace([$this->basePath . '/', '.php'], '', $file->getPath());
        $namespaceParts = array_filter(explode('/', $relativePath));

        return $this->namespacePrefix . '\\' . implode('\\', $namespaceParts);
    }

    private function passesFilter(string $className): bool
    {
        if (empty($this->subClassesFilter)) {
            return true;
        }

        foreach ($this->subClassesFilter as $filter) {
            if (is_subclass_of($className, $filter) || $className === $filter) {
                return true;
            }
        }

        return false;
    }

    private function getLibPath(): string
    {
        return Loader::getLocal("modules/$this->moduleCode/lib");
    }
}
