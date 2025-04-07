<?php

namespace Base\Module\Service\Options;

interface OptionsService
{
    public const SERVICE_CODE = 'base.module.options.service';

    public function setTabs(array $tabClasses): self;

    public function setOptions(array $optionClasses): self;

    public function render(): void;

    public function getProvider(string $type): mixed;
}
