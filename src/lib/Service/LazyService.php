<?php

namespace Base\Module\Service;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class LazyService
{
    public const MODULE_ID = 'base.module';

    public function __construct(public readonly string $serviceCode, public readonly array $constructorParams)
    {
    }
}