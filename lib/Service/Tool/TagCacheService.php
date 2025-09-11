<?php

namespace Base\Module\Service\Tool;


interface TagCacheService
{
    public const SERVICE_CODE = 'base.module.tag.cache.service';
    public function getFromCache(int $ttl, string $cacheDir): ?array;
    public function saveInCache(array $var): void;
    public function clearCache(): void;
}