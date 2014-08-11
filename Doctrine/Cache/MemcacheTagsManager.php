<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;

class MemcacheTagsManager
{
    protected $resultCache;
    protected $memcached;

    private $tagPrefix = 'tags_caсhe';

    public function __construct(MemcachedCache $resultCache)
    {
        $this->resultCache = $resultCache;
        $this->memcached = $this->resultCache->getMemcached();
    }

    /**
     * Добавляет тег(-и) для сохраняемых данных
     *
     * @access public
     * @param mixed $tag
     * @param string $queryId
     * @return void
     */
    public function tagAdd($tags, $queryId = '')
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        foreach ($tags as $tag) {

            $tagIds = $this->memcached->get($this->getTagCacheKey($tag));

            if (!$tagIds) {
                $tagIds = [];
            } else {
                $tagIds = unserialize($tagIds);
                if (in_array($queryId, $tagIds)) {
                    continue;
                }
            }

            $tagIds[] = $queryId;
            $this->memcached->set($this->getTagCacheKey($tag), serialize($tagIds), 0);
        }

        return $this;
    }


    /**
     * Очистка данных по тегу
     *
     * @access public
     * @param mixed $tag
     * @return void
     */
    public function tagClear($tags)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        foreach ($tags as $tag){

            $tagIds = $this->memcached->get($this->getTagCacheKey($tag));
            if (!$tagIds) {
                continue;
            }

            $tagIds = unserialize($tagIds);
            foreach ($tagIds as $id) {
                $this->resultCache->delete($id);
            }
        }

        return true;
    }


    private function getTagCacheKey($tag)
    {
        return $this->tagPrefix . '.' . $tag;
    }
}
