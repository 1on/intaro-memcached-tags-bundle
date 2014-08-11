<?php

namespace Intaro\MemcachedTagsBundle\Doctrine;

use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Cache\QueryCacheProfile;

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

        if (is_callable([$qcp, 'getCacheTags']) && !empty($qcp->getCacheTags())) {

            list($cacheKey, $realKey) = $qcp->generateCacheKeys($query, $params, $types);
            $resultCacheDriver = $qcp->getResultCacheDriver()
            $resultCacheDriver->tagAdd($cacheTags, $cacheKey);

        }

        return $result;
    }
}
