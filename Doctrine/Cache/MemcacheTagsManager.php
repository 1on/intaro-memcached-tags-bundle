<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;

class MemcacheTagsManager
{
    protected static $loadedTags = array();
    protected static $isCoreTagsLoaded = false;

    protected $resultCache;
    protected $memcached;

    const CACHE_TAG_KEY = 'cache_tags';
    const CACHE_TIME_KEY = 'cache_time';

    private $coreTagsKey = 'core_tags';
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

        $tagsDifference = array_diff($tags, array_keys($tagsTimes));
        if (!empty($tagsDifference)) {

            $tagsDifferenceTime = array();
            foreach ($tagsDifference as $tag) {
                $tagsDifferenceTime[$tag] = $cacheTime;
            }
            $this->saveTags($tagsDifferenceTime);
        }

        foreach ($tagsTimes as $value) {
            if ($cacheTime < $value) {
                return true;
            }
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

        $existingTags = $this->loadTags($tags);

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
            } elseif (isset(self::$loadedTags['core'][$tag])) {
                $result[$tag] = self::$loadedTags['core'][$tag];
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

            if (!empty($tags) && !self::$isCoreTagsLoaded) {
                $tagsToRetrieve[] = $this->getTagCacheKey($this->coreTagsKey);
                self::$isCoreTagsLoaded = true;
            }

            $tagsRetrieved = $this->memcached->getMulti($tagsToRetrieve);

            if (isset($tagsRetrieved[$this->getTagCacheKey($this->coreTagsKey)])) {
                self::$loadedTags['core'] = $tagsRetrieved[$this->getTagCacheKey($this->coreTagsKey)];
                unset($tagsRetrieved[$this->getTagCacheKey($this->coreTagsKey)]);
            }

            foreach ($tagsRetrieved as $tag => $value) {
                self::$loadedTags[$tag] = $value;
                $result[$tag] = $value;
            }
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

            if (preg_match('/:\d+$/', $tag) === 1) {
                $dataToSave[$tag] = $value;
                self::$loadedTags[$tag] = $value;
                unset($tags[$tag]);
            } else {
                self::$loadedTags['core'][$tag] = $value;
            }
        }

        if (!empty($tags)) {
            $dataToSave[$this->getTagCacheKey($this->coreTagsKey)] = self::$loadedTags['core'];
        }

        $this->memcached->setMulti($dataToSave);

        return true;
    }


    private function getTagCacheKey($tag)
    {
        return $this->tagPrefix . '.' . $tag;
    }
}
