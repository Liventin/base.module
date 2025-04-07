<?php

namespace Base\Module\Service\Tool;

interface ClassList
{
    public const SERVICE_CODE = 'base.module.class.list';

    public function setSubClassesFilter(array $subClassesFilter): self;

    public function getFromLib(string $relativePath): array;
    public function getModuleCode(): string;
}
