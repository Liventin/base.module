<?php

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

try {
    Loader::requireModule(basename(__DIR__));

    $base_module_default_option = [
    ];
} catch (LoaderException $e) {
    //ignore default
}
