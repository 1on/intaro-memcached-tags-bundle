<?php

namespace Intaro\MemcachedTagsBundle\Doctrine;

use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Cache\QueryCacheProfile;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheTagsManager;

/**
 * {@inheritDoc}
 */
class Connection extends BaseConnection
{
    /**
     * {@inheritDoc}
     */
    public function executeCacheQuery($query, $params, $types, QueryCacheProfile $qcp)
    {
        $result = parent::executeCacheQuery($query, $params, $types, $qcp);

        $resultCacheDriver = $qcp->getResultCacheDriver();
        if (
            is_callable([$qcp, 'getCacheTags'])
            && !empty($qcp->getCacheTags())
            && $resultCacheDriver instanceof MemcachedCache
        ) {
            list($cacheKey, $realKey) = $qcp->generateCacheKeys($query, $params, $types);

            $cacheTagsManager = new MemcacheTagsManager($resultCacheDriver);
            $cacheTagsManager->tagAdd($qcp->getCacheTags(), $cacheKey);
        }

        return $result;
    }
}
