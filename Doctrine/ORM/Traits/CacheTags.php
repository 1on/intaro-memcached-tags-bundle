<?php

namespace Intaro\MemcachedTagsBundle\Doctrine\ORM\Traits;

/**
 * Трейт для работы с тегами кеша
 */
trait CacheTags
{
    protected $cacheTags = array();

    /**
     * Возвращает список тегов
     *
     * @return array
     */
    public function getCacheTags()
    {
        return $this->cacheTags;
    }

    /**
     * Добавляет тег
     *
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
     * Добавляет теги
     *
     * @param array $tags
     *
     * @return self
     */
    public function addCacheTags(array $tags)
    {
        foreach ($tags as $tag) {
            $this->addCacheTag($tag);
        }

        return $this;
    }

    /**
     * Очищает список тегов
     *
     * @return self
     */
    public function clearCacheTags()
    {
        $this->cacheTags = array();

        return $this;
    }

    /**
     * Удаляет тег
     *
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

    /**
     * Удаляет теги
     *
     * @param array $tags
     *
     * @return self
     */
    public function removeCacheTags(array $tags)
    {
        foreach ($tags as $tag)
            $this->removeCacheTag($tag);

        return $this;
    }
}