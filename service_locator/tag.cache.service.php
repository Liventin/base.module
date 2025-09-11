<?php

defined('B_PROLOG_INCLUDED') || die;

return [
    'base.module.tag.cache.service' => [
        'className' => Base\Module\Src\Tool\TagCacheService::class,
        'constructorParams' => [
            'base.module',
        ],
    ],
];
