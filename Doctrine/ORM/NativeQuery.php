<?php

namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\ORM\NativeQuery as BaseNativeQuery;

use Intaro\MemcachedTagsBundle\Doctrine\ORM\Traits\CacheTags;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheCache;

class NativeQuery extends BaseNativeQuery
{
    use CacheTags;

    protected function _doExecute()
    {
        $queryId = $this->_getQueryCacheId();
        $queryCacheDriver = $this->getResultCacheDriver();

        if ($queryCacheDriver && $queryCacheDriver instanceof MemcacheCache)
            $this->useResultCache(true, null, $queryId);

        $result = parent::_doExecute();

        if ($queryCacheDriver && $queryCacheDriver instanceof MemcacheCache) {
            $this->setResultCacheId($queryId);

            if ($cacheTags = $this->getCacheTags()) {
                $this->getResultCacheDriver()->tagAdd($cacheTags, $queryId);
            }
        }

        return $result;
    }

    /**
     * Получаем идентификатор запроса для кэша
     *
     */
    protected function _getQueryCacheId()
    {
        return md5($this->getSql() . 'DOCTRINE_QUERY_CACHE_SALT');
    }

}