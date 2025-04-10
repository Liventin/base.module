<?php

use Bitrix\Main\Data\Cache;

defined('B_PROLOG_INCLUDED') || die;

$settings = [
    'services' => [
        'value' => [],
        'readonly' => true,
    ],
];

$moduleId = basename(__DIR__);
$cache = Cache::createInstance();
$locatorServices = [];
if ($cache->initCache(86400, "service_locator_$moduleId", "service_locator/$moduleId")) {
    $locatorServices = $cache->getVars();
} else {
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
    $cache->startDataCache();
    $cache->endDataCache($locatorServices);
}

$settings['services']['value'] = array_merge($locatorServices, $settings['services']['value']);

return $settings;
