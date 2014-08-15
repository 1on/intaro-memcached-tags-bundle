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

        return $this->_em->tagsClear($this->_class->getName());
    }


    /**
     * Clear tagged cache for entity id
     *
     * @param integer $id
     *
     * @return null
     */
    public function clearEntityIdCache($id)
    {
        if (!($this->_em instanceof EntityManager))
            return;

        return $this->_em->tagsClear($this->_class->getName() . ':' . $id);
    }
}
