<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheTagsManager;

class EntityRepository extends BaseEntityRepository
{
    /**
     * Clear tagged cache for entity
     *
     * @return null
     */
    public function clearEntityCache()
    {
        $resultCache = $this->_em->getConfiguration()->getResultCacheImpl();
        if (!($resultCache instanceof MemcachedCache))
            return;
        $cacheTagsManager = new MemcacheTagsManager($resultCache);
        $cacheTagsManager->tagClear($this->_class->getName());
    }
}
