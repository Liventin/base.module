<?php

namespace Base\Module\Service;

use Base\Module\Exception\ModuleException;
use Bitrix\Main\DI\ServiceLocator;
use Base\Module\Service\Tool\ClassList as IClassList;
use ReflectionClass;
use Throwable;

class Container
{
    private static ?ServiceLocator $serviceLocator = null;
    private static bool $lazyServicesLoaded = false;

    /**
     * @param string $serviceCode
     * @return mixed
     * @throws ModuleException
     */
    public static function get(string $serviceCode): mixed
    {
        self::prepareServiceLocator();
        self::prepareLazyService();

        try {
            $service = self::$serviceLocator->get($serviceCode);
        } catch (Throwable $t) {
            throw new ModuleException($t->getMessage(), $t->getCode());
        }

        return $service;
    }

    /**
     * @throws ModuleException
     */
    public static function has(string $serviceCode): bool
    {
        self::prepareServiceLocator();
        self::prepareLazyService();

        return self::$serviceLocator->has($serviceCode);
    }

    private static function prepareServiceLocator(): void
    {
        if (self::$serviceLocator !== null) {
            return;
        }

        self::$serviceLocator = ServiceLocator::getInstance();
    }

    /**
     * @return void
     * @throws ModuleException
     */
    private static function prepareLazyService(): void
    {
        if (self::$lazyServicesLoaded) {
            return;
        }

        try {
            $classListService = self::$serviceLocator->get(IClassList::SERVICE_CODE);
            if (!is_object($classListService) ||
                !method_exists($classListService, 'getFromLib') ||
                !method_exists($classListService, 'setSubClassesFilter')) {
                return;
            }

            $classes = $classListService->getFromLib("Src");
            foreach ($classes as $className) {
                $reflection = new ReflectionClass($className);
                $attributes = $reflection->getAttributes(LazyService::class);

                if (!empty($attributes)) {
                    $lazyService = $attributes[0]->newInstance();
                    $serviceCode = $lazyService->serviceCode;

                    if (!self::$serviceLocator->has($serviceCode)) {
                        self::$serviceLocator->addInstanceLazy($serviceCode, [
                            "className" => $className,
                            "constructorParams" => $lazyService->constructorParams,
                        ]);
                    }
                }
            }
            self::$lazyServicesLoaded = true;
        } catch (Throwable $t) {
            throw new ModuleException($t->getMessage(), $t->getCode());
        }
    }
}
