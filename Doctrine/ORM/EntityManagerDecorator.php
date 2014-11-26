<?php
/* Not used */
namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\ORM\UnitOfWork as BaseUnitOfWork;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Decorator\EntityManagerDecorator as BaseEntityManagerDecorator;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\QueryCacheProfile;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheTagsManager;

class EntityManagerDecorator extends BaseEntityManagerDecorator
{
    private $memcachedTagsManager;

    public function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        $entityManager = DoctrineEntityManager::create($conn, $config, $conn->getEventManager());
        parent::__construct($entityManager);

        $resultCache = $this->getConfiguration()->getResultCacheImpl();
        if (!($resultCache instanceof MemcachedCache)) {
            return;
        }
        $this->memcachedTagsManager = new MemcacheTagsManager($resultCache);

        $unitOfWorkSetter = function(BaseUnitOfWork $uow) {
            $this->unitOfWork = $uow;
        };

        $setter = \Closure::bind($unitOfWorkSetter, $this->wrapped, 'Doctrine\ORM\EntityManager');
        $setter(new UnitOfWork($this->wrapped));
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = "", $cacheLifeTime = null, array $cacheTags = array())
    {
        $query = parent::createQuery($dql);

        $resultCacheProfile = new QueryCacheProfile();

        if (!empty($cacheTags) || !is_null($cacheLifeTime)) {

            $query->useResultCache(true);
            if (!is_null($cacheLifeTime)) {
                $query->setResultCacheLifetime($cacheLifeTime);
            }

            if (!empty($cacheTags)) {
                $resultCacheProfile = $resultCacheProfile->setCacheTags($cacheTags);
            }

            $query->setResultCacheProfile($resultCacheProfile);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm, $cacheLifeTime = null, array $cacheTags = array())
    {
        $query = parent::createNativeQuery($sql, $rsm);

        $resultCacheProfile = new QueryCacheProfile();

        if (!empty($cacheTags) || !is_null($cacheLifeTime)) {

            $query->useResultCache(true);
            if (!is_null($cacheLifeTime)) {
                $query->setResultCacheLifetime($cacheLifeTime);
            }

            if (!empty($cacheTags)) {
                $resultCacheProfile = $resultCacheProfile->setCacheTags($cacheTags);
            }

            $query->setResultCacheProfile($resultCacheProfile);
        }

        return $query;
    }

    /**
     * Clears cache for tags
     *
     * @param string|array $tags single tag or array of tags
     *
     * @return bool
     */
    public function tagsClear($tags, $force = false)
    {
        if (is_null($this->memcachedTagsManager)) {
            return false;
        }

        return $this->memcachedTagsManager->tagsClear($tags, $force);
    }

    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        return new EntityManager($conn, $config, $conn->getEventManager());
    }
}
