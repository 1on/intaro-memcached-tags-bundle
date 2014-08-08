<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder as BaseQueryBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    protected $cacheParams = array();

    public function setCacheParams(array $params)
    {
        if (!isset($params['lifetime']) || !is_numeric($params['lifetime']))
            $params['lifetime'] = null;

        $this->cacheParams['lifetime'] = $params['lifetime'];

        if (isset($params['tags']) && sizeof($params['tags']))
            $this->cacheParams['tags'] = $params['tags'];

        return $this;
    }

    public function getCacheParams()
    {
        return $this->cacheParams;
    }

    public function addCacheTag($tag)
    {
        if (!isset($this->cacheParams['tags']))
            $this->cacheParams['tags'] = [];

        if (!in_array($tag, $this->cacheParams['tags']))
            $this->cacheParams['tags'][] = $tag;

        return $this;
    }

    public function addCacheTags($tags)
    {
        foreach ($tags as $tag) {
            $this->addCacheTag($tag);
        }

        return $this;
    }

    /**
     * Возвращает объект Query с подставленными параметрами кеширования
     *
     * @access public
     * @return Query
     */
    public function getQuery()
    {
        $query = parent::getQuery();

        if (sizeof($this->cacheParams)) {

            $query->useCache(true);
            if (isset($this->cacheParams['lifetime'])) {
                $query->setResultCacheLifetime($this->cacheParams['lifetime']);
            }

            if (isset($this->cacheParams['tags']) && sizeof($this->cacheParams['tags'])) {
                $query->addCacheTags($this->cacheParams['tags']);
            }

        }

        return $query;
    }
}
