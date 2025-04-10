<?php

defined('B_PROLOG_INCLUDED') || die;

$moduleId = basename(__DIR__);
return [
    $moduleId . '.options.service' => [
        'className' => Base\Module\Src\Options\OptionsService::class,
        'constructorParams' => [$moduleId],
    ],
];
