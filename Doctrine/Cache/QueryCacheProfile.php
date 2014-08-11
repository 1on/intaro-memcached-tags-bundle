<?php

namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Doctrine\DBAL\Cache\QueryCacheProfile as BaseQueryCacheProfile;

/**
 * {@inheritDoc}
 */
class QueryCacheProfile extends BaseQueryCacheProfile
{
    protected $cacheTags = array();

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
}
