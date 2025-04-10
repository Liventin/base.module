<?php

defined('B_PROLOG_INCLUDED') || die;

$settings = [
    'services' => [
        'value' => [],
        'readonly' => true,
    ],
];

$serviceLocatorDir = __DIR__ . '/service_locator';
if (is_dir($serviceLocatorDir)) {
    $iterator = new DirectoryIterator($serviceLocatorDir);
    $locatorServices = [];
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

    $settings['services']['value'] = array_merge($locatorServices, $settings['services']['value']);
}

return $settings;
