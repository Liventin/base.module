<?php

namespace Base\Module\Service;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Base\Module\Service\Tool\ClassList as IClassList;
use ReflectionClass;

class Container
{
    private static ServiceLocator $serviceLocator;
    private static bool $lazyServicesLoaded = false;

    /**
     * @param string $serviceCode
     * @return mixed
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    public static function get(string $serviceCode): mixed
    {
        if (!isset(self::$serviceLocator)) {
            self::$serviceLocator = ServiceLocator::getInstance();
        }

        if (!self::$lazyServicesLoaded) {
            self::addLazyServices();
            self::$lazyServicesLoaded = true;
        }

        return self::$serviceLocator->get($serviceCode);
    }

    /**
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     * @throws SystemException
     */
    private static function addLazyServices(): void
    {
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
    }
}
