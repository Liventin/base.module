<?php

/** @noinspection DuplicatedCode */

use Bitrix\Main\Application;

defined('B_PROLOG_INCLUDED') || die;

$settings = [
    'services' => [
        'value' => [],
        'readonly' => true,
    ],
];

$moduleId = basename(__DIR__);
$cacheId = "cache.$moduleId";
$ttl = 86400;
$cacheDir = "/$moduleId/service_locator/";

$cache = Application::getInstance()->getCache();
$cache->initCache($ttl, $cacheId, $cacheDir);
$cachedVars = $cache->getVars();

if ($cachedVars !== false) {
    $locatorServices = $cachedVars;
} else {
    $locatorServices = [];

    $serviceLocatorDir = __DIR__ . '/service_locator';
    if (is_dir($serviceLocatorDir)) {
        $iterator = new DirectoryIterator($serviceLocatorDir);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $filePath = $fileInfo->getPathname();
            $serviceDefinition = include $filePath;

            if (is_array($serviceDefinition) && !empty($serviceDefinition)) {
                foreach ($serviceDefinition as $key => $params) {
                    $locatorServices[$key] = $params;
                }
            }
        }
    }

    $taggedCache = Application::getInstance()->getTaggedCache();
    $cache->startDataCache($ttl, $cacheId, $cacheDir);
    $taggedCache->startTagCache($cacheDir);
    $taggedCache->registerTag($cacheId);
    $taggedCache->endTagCache();
    $cache->endDataCache($locatorServices);
}

$settings['services']['value'] = array_merge($locatorServices, $settings['services']['value']);

return $settings;
