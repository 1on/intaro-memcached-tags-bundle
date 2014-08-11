<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Cache;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache as BaseMemcacheCache;

class MemcacheCache extends BaseMemcacheCache
{
    private $tag = array();
    private $tagPrefix = 'tags_caсhe';

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

            $tagIds[] = $this->prefix . $queryId;
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
                $this->memcached->delete($id);
            }
        }

        return true;
    }


    private function getTagCacheKey($tag)
    {
        return $this->prefix . $this->tagPrefix . '.' . $tag;
    }
}
