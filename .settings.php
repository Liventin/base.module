<?php

defined('B_PROLOG_INCLUDED') || die;

use Base\Module\Src\Tool;

$moduleId = basename(__DIR__);

return [
    'services' => [
        'value' => [
            $moduleId . '.class.list' => [
                'className' => Tool\ClassList::class,
                'constructorParams' => [
                    $moduleId,
                ],
            ],
        ],
        'readonly' => true,
    ],
];