<?php

namespace Base\Module\Src\Options;

use Base\Module\Service\Tool\ClassList;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\HttpRequest;
use CAdminTabControl;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Base\Module\Service\Container;
use Base\Module\Service\LazyService;
use Base\Module\Src\Options\Interface\OptionProvider as IOptionProvider;
use Base\Module\Service\Options\OptionsService as IOptionsService;


#[LazyService(serviceCode: IOptionsService::SERVICE_CODE, constructorParams: ['moduleId' => LazyService::MODULE_ID])]
class OptionsService
{
    private array $tabs = [];
    private array $options = [];
    private ?array $providers = null;
    private string $moduleId;

    /**
     * @param string $moduleId
     */
    public function __construct(string $moduleId)
    {
        $this->moduleId = $moduleId;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function render(): void
    {
        global $APPLICATION;

        if (!CurrentUser::get()->isAdmin()) {
            return;
        }

        $request = Context::getCurrent()?->getRequest();
        if ($request === null) {
            return;
        }

        if ($request->isPost()) {
            if ($request->getPost("reinstall") !== null) {
                $moduleClass = str_replace('.', '_', $this->moduleId);
                if (class_exists($moduleClass)) {
                    $module = new $moduleClass();
                    if (method_exists($module, 'ReInstall')) {
                        $module->ReInstall(true);
                    }
                }
            } else {
                $this->saveOptions($request);
            }
            LocalRedirect(
                $APPLICATION->GetCurPage() . "?mid=" . htmlspecialcharsbx($this->moduleId) . "&lang=" . LANGUAGE_ID
            );
        }

        $tabControl = new CAdminTabControl("tabControl", $this->tabs);
        $tabControl->Begin();
        echo '<form method="post" action="' . $APPLICATION->GetCurPage() . '?mid=' .
            htmlspecialcharsbx($this->moduleId) . '&lang=' . LANGUAGE_ID . '">';
        foreach ($this->tabs as $tab) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $tabControl->BeginNextTab();
            foreach ($this->options as $option) {
                if ($option["tabId"] === $tab["DIV"]) {
                    echo $this->renderOption($option);
                }
            }
        }
        echo bitrix_sessid_post();
        $tabControl->Buttons();
        if (!empty($this->tabs)) {
            echo '<input type="submit" name="apply" value="' .
                Loc::getMessage('BASE_MODULE_MODULE_OPTIONS_APPLY') .
                '" class="adm-btn-save">';
            echo '<input type="reset" name="reset" value="' . Loc::getMessage(
                    'BASE_MODULE_MODULE_OPTIONS_RESET'
                ) . '">';
        }
        echo '<input type="submit" name="reinstall" value="' . Loc::getMessage(
                'BASE_MODULE_MODULE_OPTIONS_REINSTALL'
            ) . '">';
        echo '</form>';
        $tabControl->End();
    }

    public function setTabs(array $tabClasses): self
    {
        $this->loadTabs($tabClasses);
        return $this;
    }

    public function setOptions(array $optionClasses): self
    {
        $this->loadOptions($optionClasses);
        return $this;
    }

    private function loadTabs(array $tabClasses): void
    {
        $tabs = [];
        foreach ($tabClasses as $className) {
            if (!method_exists($className, 'getId') || !method_exists($className, 'getName') || !method_exists(
                    $className,
                    'getSort'
                )) {
                continue;
            }
            $instance = new $className();
            $tabs[$instance->getSort()] = [
                "DIV" => $instance->getId(),
                "TAB" => $instance->getName(),
                "TITLE" => $instance->getName(),
            ];
        }
        ksort($tabs);
        $this->tabs = array_values($tabs);
    }


    private function loadOptions(array $optionClasses): void
    {
        $options = [];
        foreach ($optionClasses as $className) {
            if (!method_exists($className, 'getId') || !method_exists($className, 'getName') ||
                !method_exists($className, 'getType') || !method_exists($className, 'getTabId') ||
                !method_exists($className, 'getSort') || !method_exists($className, 'getParams')) {
                continue;
            }
            $instance = new $className();
            $options[$instance->getSort()] = [
                "id" => $instance->getId(),
                "name" => $instance->getName(),
                "type" => $instance->getType(),
                "tabId" => $instance->getTabId(),
                "params" => $instance->getParams(),
            ];
        }
        ksort($options);
        $this->options = array_values($options);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    private function registerProviders(): void
    {
        if ($this->providers !== null) {
            return;
        }

        $this->providers = [];

        /** @var ClassList $classList */
        $classList = Container::get(ClassList::SERVICE_CODE);

        $moduleRoot = Loader::getLocal('modules/'.$classList->getModuleCode().'/lib');
        $relativePath = str_replace($moduleRoot, '', __DIR__ . '/Providers');

        $providerClasses = $classList->setSubClassesFilter([IOptionProvider::class])->getFromLib($relativePath);

        foreach ($providerClasses as $className) {
            $provider = new $className();
            $this->providers[$provider->getType()] = $provider;
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function getProvider(string $type): ?IOptionProvider
    {
        $this->registerProviders();
        return $this->providers[$type] ?? null;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    private function renderOption(array $option): string
    {
        $provider = $this->getProvider($option["type"]);
        if (!$provider) {
            return "<tr><td colspan='2'>Unsupported option type: " . htmlspecialcharsbx($option["type"]) . "</td></tr>";
        }
        return $provider->render($option, $this->moduleId);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    private function saveOptions(HttpRequest $request): void
    {
        if (!check_bitrix_sessid()) {
            return;
        }

        foreach ($this->options as $option) {
            $value = $request->getPost($option["id"]);
            $provider = $this->getProvider($option["type"]);
            $provider?->save($option, $this->moduleId, $value);
        }
    }
}
