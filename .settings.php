<?php

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;

defined('B_PROLOG_INCLUDED') || die;

$settings = [
    'services' => [
        'value' => [],
        'readonly' => true,
    ],
];

$moduleId = basename(__DIR__);
$cache = Cache::createInstance();
$taggedCache = new TaggedCache();
$cacheId = "service_locator_$moduleId";
$cacheDir = "/$moduleId/";

$locatorServices = [];
if ($cache->startDataCache(86400, $cacheId, $cacheDir)) {
    $taggedCache->startTagCache($cacheDir);
    $taggedCache->registerTag($cacheId);

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

    $cache->endDataCache($locatorServices);
    $taggedCache->endTagCache();
} else {
    $locatorServices = $cache->getVars();
}

$settings['services']['value'] = array_merge($locatorServices, $settings['services']['value']);

return $settings;
