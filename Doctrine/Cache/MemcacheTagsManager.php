<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;

class MemcacheTagsManager
{
    protected static $loadedTags = array();

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

    /**
     * Check by tags if cache is expired
     *
     * @param array   $tags
     * @param integer $cacheTime
     *
     * @return bool
     */
    public function checkExpiredByTags(array $tags, $cacheTime)
    {
        foreach ($tags as $key => $tag) {
            $tags[$key] = $this->getTagCacheKey($tag);
        }

        $tagsTimes = $this->loadTags($tags);

        $tagsDifference = array_diff(array_keys($tagsTimes), $tags);
        if (!empty($tagsDifference)) {

            $tagsDifferenceTime = array();
            foreach ($tagsDifference as $tag) {
                $tagsDifferenceTime[$tag] = $cacheTime;
            }
            $this->saveTags($tagsDifferenceTime);
        }

        foreach ($tagsTimes as $value) {
            if ($cacheTime < $value)
                return true;
        }

        return false;
    }

    /**
     * Clears cache by tags
     *
     * @access public
     * @param mixed $tag
     * @return void
     */
    public function tagsClear($tags, $force = false)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        foreach ($tags as $key => $tag) {
            $tags[$key] = $this->getTagCacheKey($tag);
        }

        if (!$force) {
            $existingTags = $this->loadTags($tags);
        }

        $tagsTime = array();
        foreach ($tags as $tag) {
            if ($force || isset($existingTags[$this->getTagCacheKey($tag)])) {
                $tagsTime[$this->getTagCacheKey($tag)] = time();
            }
        }

        $this->saveTags($tagsTime);

        return true;
    }

    protected function loadTags(array $tags)
    {
        $result = array();

        foreach ($tags as $key => $tag) {
            if (isset(self::$loadedTags[$tag])) {
                $result[$tag] = self::$loadedTags[$tag];
                unset($tags[$key]);
            }
        }

        if (!empty($tags)) {
            $newTags = $this->memcached->getMulti($tags);
            foreach ($newTags as $tag => $value) {
                self::$loadedTags[$tag] = $value;
            }
            $result = array_merge($result, $newTags);
        }

        return $result;
    }

    protected function saveTags(array $tags)
    {
        foreach ($tags as $tag => $value) {
            self::$loadedTags[$tag] = $value;
        }

        $this->memcached->setMulti($tags);

        return true;
    }


    private function getTagCacheKey($tag)
    {
        return $this->tagPrefix . '.' . $tag;
    }
}
