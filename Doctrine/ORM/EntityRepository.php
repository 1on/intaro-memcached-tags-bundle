<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;

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

        if (method_exists($resultCache, 'tagDelete')) {
            $resultCache->tagDelete($this->_class->getName());
        }
    }
}
