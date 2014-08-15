<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;

class MemcacheTagsManager
{
    protected static $loadedTags = array();
    protected static $generalTagsLoaded = false;

    protected $resultCache;
    protected $memcached;

    const CACHE_TAG_KEY = 'cache_tags';
    const CACHE_TIME_KEY = 'cache_time';

    private $generalTagsKey = 'general_tags';
    private $tagPrefix = 'tags_caсhe';

    public function __construct(MemcachedCache $resultCache)
    {
        $this->resultCache = $resultCache;
        $this->memcached = $this->resultCache->getMemcached();
    }

    /**
     * Check by tags if cache is deprecated
     *
     * @param array   $tags
     * @param integer $cacheTime
     *
     * @return bool
     */
    public function checkDeprecatedByTags(array $tags, $cacheTime)
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
     * Очистка данных по тегу
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
                $tagsTime[$tag] = time();
            }
        }

        $this->saveTags($tagsTime);

        return true;
    }

    /**
     * Get tags from memcached and store them in static variable
     *
     * @param  array  $tags
     *
     * @return array
     */
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

            $tagsToRetrieve = array();
            foreach ($tags as $key => $tag) {
                if (preg_match('/:\d+$/', $tag) === 1) {
                    $tagsToRetrieve[] = $tag;
                    unset($tags[$key]);
                }
            }

            if (!empty($tags) && !self::$generalTagsLoaded) {
                $tagsToRetrieve[] = $this->getTagCacheKey($this->generalTagsKey);
                self::$generalTagsLoaded = true;
            }

            $gettedTags = $this->memcached->getMulti($tagsToRetrieve);
            $tagsRetrieved = array();

            if (isset($gettedTags[$this->getTagCacheKey($this->generalTagsKey)])) {
                $tagsRetrieved = $gettedTags[$this->getTagCacheKey($this->generalTagsKey)];
                unset($gettedTags[$this->getTagCacheKey($this->generalTagsKey)]);
            }

            foreach ($gettedTags as $tag => $value) {
                $tagsRetrieved[$tag] = $value;
            }

            foreach ($tagsRetrieved as $tag => $value) {
                self::$loadedTags[$tag] = $value;
            }
            $result = array_merge($result, $tagsRetrieved);
        }

        return $result;
    }

    /**
     * Update deprecation time for tagged cache
     *
     * @param array $tags
     *
     * @return bool
     */
    protected function saveTags(array $tags)
    {
        $dataToSave = array();
        foreach ($tags as $tag => $value) {
            self::$loadedTags[$tag] = $value;

            if (preg_match('/:\d+$/', $tag) === 1) {
                $dataToSave[$tag] = $value;
                unset($tags[$tag]);
            }
        }
        $dataToSave[$this->getTagCacheKey($this->generalTagsKey)] = $tags;
        $this->memcached->setMulti($dataToSave);

        return true;
    }


    private function getTagCacheKey($tag)
    {
        return $this->tagPrefix . '.' . $tag;
    }
}
