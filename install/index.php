<?php

/**
 * @noinspection PhpUnused
 * @noinspection DuplicatedCode
 */

use Bitrix\Main\Context;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\ObjectNotFoundException;
use Base\Module\Exception\ModuleException;
use Base\Module\Service\Container;
use Base\Module\Service\Tool\ClassList as IClassList;
use Base\Module\Install\Interface\Install;
use Base\Module\Install\Interface\UnInstall;
use Base\Module\Install\Interface\ReInstall;
use Psr\Container\NotFoundExceptionInterface;

Loc::loadMessages(__FILE__);

class base_module extends CModule
{
    public $MODULE_ID = 'base.module';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public $MODULE_GROUP_RIGHTS = 'Y';

    private const LANG_PREFIX = 'BASE_MODULE_';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '2025-03-21';

        $this->MODULE_NAME = Loc::getMessage(self::LANG_PREFIX . 'MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage(self::LANG_PREFIX . 'MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage(self::LANG_PREFIX . 'MODULE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage(self::LANG_PREFIX . 'MODULE_PARTNER_URI');
    }

    public function GetModuleRightList(): array
    {
        return [
            'reference_id' => ['D', 'R', 'W'],
            'reference' => [
                Loc::getMessage(self::LANG_PREFIX . 'MODULE_RIGHTS_DENIED'),
                Loc::getMessage(self::LANG_PREFIX . 'MODULE_RIGHTS_READ'),
                Loc::getMessage(self::LANG_PREFIX . 'MODULE_RIGHTS_FULL'),
            ],
        ];
    }

    public function DoInstall(): void
    {
        global $APPLICATION;

        $exception = new CAdminException([], 'install');
        try {
            ModuleManager::registerModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);

            $installedClasses = $this->executeClasses(
                Install::class,
                'install',
                'getInstallSort',
                UnInstall::class,
                'unInstall',
                'getUnInstallSort'
            );

            if ($installedClasses === false) {
                throw new ModuleException('Installation failed, rollback performed');
            }
        } catch (Throwable $e) {
            $exception->AddMessage(['text' => $e->getMessage()]);
            ModuleManager::unRegisterModule($this->MODULE_ID);
        }

        $APPLICATION->ThrowException($exception);
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage(self::LANG_PREFIX . 'MODULE_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    public function DoUninstall(): void
    {
        global $APPLICATION;

        $exception = new CAdminException([], 'preUnInstall');
        try {
            Loader::includeModule($this->MODULE_ID);
            $request = Context::getCurrent()?->getRequest();
            if ($request === null) {
                return;
            }
            if ((int)$request['step'] >= 2) {
                $exception = new CAdminException([], 'unInstall');
                $this->executeClasses(
                    UnInstall::class,
                    'unInstall',
                    'getUnInstallSort',
                    null,
                    null,
                    null,
                    $request['savedata'] === 'Y'
                );
                if (empty($exception->GetMessages())) {
                    ModuleManager::unRegisterModule($this->MODULE_ID);
                }
            }
        } catch (Throwable $e) {
            $exception->AddMessage(['text' => $e->getMessage()]);
        }

        $APPLICATION->ThrowException($exception);
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage(self::LANG_PREFIX . 'MODULE_UNINSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    /**
     * @param bool $isManual
     * @return void
     * @throws ModuleException
     */
    public function ReInstall(bool $isManual = false): void
    {
        global $APPLICATION;

        $exception = new CAdminException([], 'reInstall');
        try {
            Loader::includeModule($this->MODULE_ID);
            $this->executeClasses(ReInstall::class, 'reInstall', 'getReInstallSort');
        } catch (Throwable $e) {
            $exception->AddMessage(['text' => $e->getMessage()]);
        }

        if ($isManual) {
            $APPLICATION->ThrowException($exception);
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage(self::LANG_PREFIX . 'MODULE_REINSTALL_TITLE'),
                __DIR__ . '/step.php'
            );
        } elseif ($exception->GetMessages()) {
            throw new ModuleException($exception->GetString());
        }
    }

    /**
     * @param string $interface
     * @param string $method
     * @param string $sortMethod
     * @param string|null $rollbackInterface
     * @param string|null $rollbackMethod
     * @param string|null $rollbackSortMethod
     * @param bool|null $param
     * @return array|false
     * @throws ModuleException
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    private function executeClasses(
        string $interface,
        string $method,
        string $sortMethod,
        ?string $rollbackInterface = null,
        ?string $rollbackMethod = null,
        ?string $rollbackSortMethod = null,
        ?bool $param = null
    ): bool|array {
        $classList = Container::get(IClassList::SERVICE_CODE);
        $classes = $classList->setSubClassesFilter([$interface])->getFromLib('Install');

        if (empty($classes)) {
            return [];
        }

        $sorted = array_map(
            static fn($class) => ['SORT' => (new $class())->$sortMethod(), 'CLASS' => $class],
            $classes
        );
        usort($sorted, static fn($a, $b) => $a['SORT'] <=> $b['SORT']);

        $exception = new CAdminException([], 'execute');
        $installed = [];

        foreach ($sorted as $item) {
            try {
                $instance = new $item['CLASS']();
                $param === null ? $instance->$method() : $instance->$method($param);
                $installed[] = $item['CLASS'];
            } catch (Throwable $e) {
                $exception->AddMessage(['text' => $e->getMessage()]);
                if ($rollbackInterface && $rollbackMethod && $rollbackSortMethod) {
                    $this->rollback($installed, $rollbackInterface, $rollbackMethod, $rollbackSortMethod);
                }
                return false;
            }
        }

        if ($exception->GetMessages()) {
            throw new ModuleException('Execution failed: ' . $exception->GetString());
        }

        return $installed;
    }

    /**
     * @param array $installedClasses
     * @param string $interface
     * @param string $method
     * @param string $sortMethod
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    private function rollback(array $installedClasses, string $interface, string $method, string $sortMethod): void
    {
        $classList = Container::get(IClassList::SERVICE_CODE);
        $classes = $classList->setSubClassesFilter([$interface])->getFromLib('Install');
        $classesToRollback = array_intersect($installedClasses, $classes);

        if (empty($classesToRollback)) {
            return;
        }

        $sorted = array_map(
            static fn($class) => ['SORT' => (new $class())->$sortMethod(), 'CLASS' => $class],
            $classesToRollback
        );
        usort($sorted, static fn($a, $b) => $b['SORT'] <=> $a['SORT']);

        foreach ($sorted as $item) {
            try {
                $instance = new $item['CLASS']();
                $instance->$method(false);
            } catch (Throwable) {
                //ignore errors
            }
        }
    }
}
