<?php

namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Doctrine\DBAL\Cache\QueryCacheProfile as BaseQueryCacheProfile;
use Doctrine\Common\Cache\Cache;

/**
 * {@inheritDoc}
 */
class QueryCacheProfile extends BaseQueryCacheProfile
{
    protected $cacheTags = array();

    public function __construct($lifetime = 0, $cacheKey = null, Cache $resultCache = null, array $cacheTags = array())
    {
        parent::__construct($lifetime, $cacheKey, $resultCache);

        $this->cacheTags = $cacheTags;
    }

    /**
     * @return array
     */
    public function getCacheTags()
    {
        return $this->cacheTags;
    }

    /**
     * @param array $tags
     *
     * @return self
     */
    public function setCacheTags(array $tags)
    {
        foreach ($tags as $tag) {
            if (is_scalar($tag)) {
                $this->cacheTags[] = $tag;
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setResultCacheDriver(Cache $cache)
    {
        $cacheKeys = null;
        try {
            $cacheKeys = $this->getCacheKey();
        } catch (\Doctrine\DBAL\Cache\CacheException $e) {
        }

        return new QueryCacheProfile($this->getLifetime(), $cacheKeys, $cache, $this->cacheTags);
    }

    /**
     * {@inheritDoc}
     */
    public function setCacheKey($cacheKey)
    {
        return new QueryCacheProfile($this->getLifetime(), $cacheKey, $this->getResultCacheDriver(), $this->cacheTags);
    }

    /**
     * {@inheritDoc}
     */
    public function setLifetime($lifetime)
    {
        $cacheKeys = null;
        try {
            $cacheKeys = $this->getCacheKey();
        } catch (\Doctrine\DBAL\Cache\CacheException $e) {
        }

        return new QueryCacheProfile($lifetime, $cacheKeys, $this->getResultCacheDriver(), $this->cacheTags);
    }
}
