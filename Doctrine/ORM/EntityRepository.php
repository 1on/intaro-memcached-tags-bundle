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
        if (!($this->_em instanceof EntityManager))
            return;

        return $this->_em->tagClear($this->_class->getName());
    }
}
