<?php

defined('B_PROLOG_INCLUDED') || die;

$moduleId = basename(__DIR__);

return [
    'services' => [
        'value' => [
            $moduleId . '.class.list' => [
                'className' => Base\Module\Src\Tool\ClassList::class,
                'constructorParams' => [
                    $moduleId,
                ],
            ],
        ],
        'readonly' => true,
    ],
];