<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder as BaseQueryBuilder;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\QueryCacheProfile;

/**
 * {@inheritDoc}
 */
class QueryBuilder extends BaseQueryBuilder
{
    protected $cacheTags = array();
    protected $cacheLifeTime = null;

    /**
     * {@inheritDoc}
     */
    public function getQuery()
    {
        $query = parent::getQuery();

        if (!empty($this->cacheTags) || !is_null($this->cacheLifeTime)) {

            $resultCacheProfile = new QueryCacheProfile();
            $query->setResultCacheProfile($resultCacheProfile);
            $query->useResultCache(true);
            if (!is_null($this->cacheLifeTime)) {
                $query->setResultCacheLifetime($this->cacheLifeTime);
            }

            if (!empty($this->cacheTags)) {
                $resultCacheProfile->setCacheTags($this->cacheTags);
            }
        }

        return $query;
    }

    public function useResultCache($bool, $lifeTime = 0)
    {
        if ($bool) {
            $this->cacheLifeTime = $lifeTime;
        } else {
            $this->cacheLifeTime = null;
        }

        return $this;
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
        $this->cacheTags = $tags;

        return $this;
    }

    /**
     * @param string $tag
     *
     * @return self
     */
    public function addCacheTag($tag)
    {
        if (!in_array($tag, $this->cacheTags)) {
            $this->cacheTags[] = $tag;
        }

        return $this;
    }

    /**
     * @return self
     */
    public function clearCacheTags()
    {
        $this->cacheTags = array();

        return $this;
    }

    /**
     * @param string $tags
     *
     * @return self
     */
    public function removeCacheTag($tag)
    {
        $position = array_search($tag, $this->cacheTags);

        if ($position !== false) {
            array_splice($this->cacheTags, $position, 1);
        }

        return $this;
    }
}
