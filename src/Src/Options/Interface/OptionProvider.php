<?php

namespace Base\Module\Src\Options\Interface;

interface OptionProvider
{
    public function getType(): string;

    public function render(array $option, string $moduleId): string;

    public function save(array $option, string $moduleId, mixed $value): void;

    public function getParamsToArray(): array;
}
