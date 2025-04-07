<?php

namespace Base\Module\Service\Options;

interface Option
{
    public static function getId(): string;

    public static function getName(): string;

    public static function getType(): string;

    public static function getTabId(): string;

    public static function getSort(): int;

    public static function getParams(): array;
}
