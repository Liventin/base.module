<?php

/** @noinspection PhpUnused */

namespace Base\Module\Src\Tool;


use Base\Module\Service\LazyService;
use Base\Module\Service\Tool\TagCacheService as ITagCacheService;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;

#[LazyService(serviceCode: ITagCacheService::SERVICE_CODE, constructorParams: [LazyService::MODULE_ID])]
class TagCacheService implements ITagCacheService
{
    private string $cacheId;
    private ?Cache $cacheService = null;
    private ?TaggedCache $tagCacheService = null;
    private string $cacheDir = '';
    private int $ttl = 0;

    public function __construct(private readonly string $moduleId)
    {
        $this->cacheId = 'cache.' . $moduleId;
    }

    private function prepareCacheServices(): void
    {
        if ($this->cacheService !== null) {
            return;
        }
        $this->cacheService = Application::getInstance()->getCache();
    }

    private function prepareTagCacheServices(): void
    {
        if ($this->tagCacheService !== null) {
            return;
        }
        $this->tagCacheService = Application::getInstance()->getTaggedCache();
    }

    public function getFromCache(int $ttl, string $cacheDir): ?array
    {
        $this->prepareCacheServices();

        $this->cacheDir = '/' . $this->moduleId . '/' . $cacheDir . '/';
        $this->ttl = $ttl;

        $this->cacheService->initCache($ttl, $this->cacheId, $this->cacheDir);
        $vars = $this->cacheService->getVars();

        return is_array($vars) ? $vars : null;
    }

    public function saveInCache(array $var): void
    {
        $this->prepareCacheServices();
        $this->prepareTagCacheServices();

        $this->cacheService->startDataCache($this->ttl, $this->cacheId, $this->cacheDir);
        $this->tagCacheService->startTagCache($this->cacheDir);
        $this->tagCacheService->registerTag($this->cacheId);
        $this->tagCacheService->endTagCache();
        $this->cacheService->endDataCache($var);
    }

    public function clearCache(): void
    {
        $this->prepareTagCacheServices();
        $this->tagCacheService->clearByTag($this->cacheId);
    }
}