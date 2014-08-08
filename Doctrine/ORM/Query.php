<?php

namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Intaro\MemcachedTagsBundle\Doctrine\ORM\Traits\CacheTags;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheCache;

class Query extends BaseQuery
{
    use CacheTags;

    protected $useCache = false;

    public function useCache($flag)
    {
        $this->useCache = $flag;

        return $this;
    }

    protected function _doExecute()
    {
        $queryId = $this->_getQueryCacheId();
        $queryCacheDriver = $this->getResultCacheDriver();

        if ($this->useCache && $queryCacheDriver && $queryCacheDriver instanceof MemcacheCache) {
            $this->useResultCache(true, null, $queryId);
        }

        $result = parent::_doExecute();

        if ($this->useCache && $queryCacheDriver && $queryCacheDriver instanceof MemcacheCache) {
            if ($cacheTags = $this->getCacheTags()) {
                $queryCacheDriver->tagAdd($cacheTags, $queryId);
            }
        }

        return $result;
    }
}
