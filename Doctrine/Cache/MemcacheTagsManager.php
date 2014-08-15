<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;

class MemcacheTagsManager
{
    protected $resultCache;
    protected $memcached;

    const CACHE_TAG_KEY = 'cache_tags';
    const CACHE_TIME_KEY = 'cache_time';

    private $tagPrefix = 'tags_caсhe';

    public function __construct(MemcachedCache $resultCache)
    {
        $this->resultCache = $resultCache;
        $this->memcached = $this->resultCache->getMemcached();
    }

    public function checkExpiredByTags(array $tags, $cacheTime)
    {
        foreach ($tags as $key => $tag) {
            $tags[$key] = $this->getTagCacheKey($tag);
        }

        $tagsTimes = $this->memcached->getMulti($tags);

        $tagsDifference = array_diff(array_keys($tagsTimes), $tags);
        if (!empty($tagsDifference)) {

            $tagsDifferenceTime = arrray();
            foreach ($tagsDifference as $tag) {
                $tagsDifferenceTime[$tag] = $cacheTime;
            }
            $this->memcached->setMulti($tagsDifferenceTime);
        }

        foreach ($tagsTimes as $value) {
            if ($cacheTime < $value)
                return true;
        }

        return false;
    }

    /**
     * Очистка данных по тегу
     *
     * @access public
     * @param mixed $tag
     * @return void
     */
    public function tagsClear($tags)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        $existingTags = $this->memcached->getMulti($tags);

        $tagsTime = array();
        foreach ($tags as $tag) {
            if (isset($existingTags[$this->getTagCacheKey($tag)])) {
                $tagsTime[$this->getTagCacheKey($tag)] = time();
            }
        }

        $this->memcached->setMulti($tagsTime);

        return true;
    }


    private function getTagCacheKey($tag)
    {
        return $this->tagPrefix . '.' . $tag;
    }
}

