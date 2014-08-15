<?php

namespace Intaro\MemcachedTagsBundle\Doctrine;

use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Cache\ArrayStatement;

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
        $resultCache = $qcp->getResultCacheDriver() ?: $this->_config->getResultCacheImpl();
        if ( ! $resultCache) {
            throw CacheException::noResultDriverConfigured();
        }

        list($cacheKey, $realKey) = $qcp->generateCacheKeys($query, $params, $types);

        // fetch the row pointers entry
        if ($data = $resultCache->fetch($cacheKey)) {
            // is the real key part of this row pointers map or is the cache only pointing to other cache keys?
            if (isset($data[$realKey])) {

                if (!empty($cacheTags)) {

                    if (!isset($data[$realKey][MemcacheTagsManager::CACHE_TAG_KEY])) {
                        $isDeprecated = true;
                    } else {

                        $cacheTagsManager = new MemcacheTagsManager($resultCache);

                        $isDeprecated = $cacheTagsManager->checkDeprecatedByTags(
                            $data[$realKey][MemcacheTagsManager::CACHE_TAG_KEY],
                            $data[$realKey][MemcacheTagsManager::CACHE_TIME_KEY]
                        );
                        unset($data[$realKey][MemcacheTagsManager::CACHE_TAG_KEY]);
                        unset($data[$realKey][MemcacheTagsManager::CACHE_TIME_KEY]);

                    }

                    if (!$isDeprecated) {
                        $stmt = new ArrayStatement($data[$realKey]);
                    }

                } else {
                    $stmt = new ArrayStatement($data[$realKey]);
                }

            } else if (array_key_exists($realKey, $data)) {
                $stmt = new ArrayStatement(array());
            }
        }

        if (!isset($stmt)) {

            $cacheTags = array();
            if (is_callable([$qcp, 'getCacheTags']) && !empty($qcp->getCacheTags())) {
                $cacheTags = $qcp->getCacheTags();
            }

            $stmt = new ResultCacheStatement($this->executeQuery($query, $params, $types), $resultCache, $cacheKey, $realKey, $qcp->getLifetime(), $cacheTags);
        }

        $stmt->setFetchMode($this->defaultFetchMode);

        return $stmt;
    }
}
