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
    public function clearEntityCache($id = null)
    {
        if (!($this->_em instanceof EntityManager))
            return;

        if (!is_null($id)) {
            return $this->_em->tagsClear($this->_class->getName() . ':' . $id, true);
        }

        return $this->_em->tagsClear($this->_class->getName(), true);
    }
}
