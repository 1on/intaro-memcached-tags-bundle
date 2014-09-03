<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheTagsManager;

class EntityRepository extends BaseEntityRepository
{
    /**
     * Clear tagged cache for entity
     *
     * @return null
     */
    public function clearEntityCache($value = null, $field = 'id')
    {
        if (!($this->_em instanceof EntityManager))
            return;

        if (!is_null($value)) {
            return $this->_em->tagsClear(
                MemcacheTagsManager::formatTag($this->_class->getName(), array($field => $value)),
                true
            );
        }

        return $this->_em->tagsClear($this->_class->getName(), true);
    }
}
