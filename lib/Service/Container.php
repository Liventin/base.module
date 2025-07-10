<?php

namespace Base\Module\Service;

use Base\Module\Exception\ModuleException;
use Bitrix\Main\DI\ServiceLocator;
use Base\Module\Service\Tool\ClassList as IClassList;
use ReflectionClass;
use Throwable;

class Container
{
    private static ServiceLocator $serviceLocator;
    private static bool $lazyServicesLoaded = false;

    /**
     * @param string $serviceCode
     * @return mixed
     * @throws ModuleException
     */
    public static function get(string $serviceCode): mixed
    {
        try {
            if (!isset(self::$serviceLocator)) {
                self::$serviceLocator = ServiceLocator::getInstance();
            }

            if (!self::$lazyServicesLoaded) {
                self::addLazyServices();
                self::$lazyServicesLoaded = true;
            }
            $service = self::$serviceLocator->get($serviceCode);

        } catch (Throwable $t) {
            throw new ModuleException($t->getMessage(), $t->getCode());
        }

        return $service;
    }

    /**
     * @return void
     * @throws ModuleException
     */
    private static function addLazyServices(): void
    {
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
        } catch (Throwable $t) {
            throw new ModuleException($t->getMessage(), $t->getCode());
        }
    }
}
