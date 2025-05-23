<?php

use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\Loader;
use Base\Module\Service\Container;
use Base\Module\Service\Tool\ClassList as IClassList;
use Base\Module\Service\Options\OptionsService as IOptionsService;
use Base\Module\Service\Options\Tab as ITab;
use Base\Module\Service\Options\Option as IOption;

$moduleId = basename(__DIR__);

try {
    Loader::requireModule($moduleId);

    $taggedCache = new TaggedCache();
    $taggedCache->clearByTag("service_locator_$moduleId");

    $classList = Container::get(IClassList::SERVICE_CODE);

    $tabClasses = $classList->setSubClassesFilter([ITab::class])->getFromLib("Options");
    $optionClasses = $classList->setSubClassesFilter([IOption::class])->getFromLib("Options");

    /** @var IOptionsService $optionsService */
    $optionsService = Container::get(IOptionsService::SERVICE_CODE);
    $optionsService->setTabs($tabClasses)
        ->setOptions($optionClasses)
        ->render();
} catch (Throwable $exception) {
    ShowError($exception->getMessage());
    ShowError($exception->getTraceAsString());
}
