<?php

namespace Intaro\MemcachedTagsBundle\Doctrine;

use Doctrine\DBAL\Cache\ResultCacheStatement as BaseResultCacheStatement;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\Common\Cache\Cache;
use PDO;

use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheTagsManager;

/**
 * {@inheritdoc}
 */
class ResultCacheStatement extends BaseResultCacheStatement
{
    /**
     * @var array
     */
    private $cacheTags = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(Statement $stmt, Cache $resultCache, $cacheKey, $realKey, $lifetime, array $cacheTags = array())
    {
        parent::__construct($stmt, $resultCache, $cacheKey, $realKey, $lifetime);
        $this->cacheTags = $cacheTags
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        if ($this->emptied && $this->data !== null && !empty($this->cacheTags)) {
            $this->data[MemcacheTagsManager::CACHE_TAG_KEY] = $cacheTags;
            $this->data[MemcacheTagsManager::CACHE_TIME_KEY] = time();
        }

        parent::closeCursor();
    }
}
