<?php

namespace Base\Module\Service\Options;

interface Tab
{
    public static function getId(): string;

    public static function getName(): string;

    public static function getSort(): int;
}
