<?php

defined('B_PROLOG_INCLUDED') || die;

return [
    'base.module.options.service' => [
        'className' => Base\Module\Src\Options\OptionsService::class,
        'constructorParams' => [
            'base.module',
        ],
    ],
];
