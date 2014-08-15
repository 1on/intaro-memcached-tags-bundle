<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Configuration;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;

use Lsw\MemcacheBundle\Doctrine\Cache\MemcachedCache;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\QueryCacheProfile;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheTagsManager;

class EntityManager extends DoctrineEntityManager
{
    private $memcachedTagsManager;

    protected function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        parent::__construct($conn, $config, $eventManager);

        $resultCache = $this->getConfiguration()->getResultCacheImpl();
        if (!($resultCache instanceof MemcachedCache)) {
            return;
        }
        $this->memcachedTagsManager = new MemcacheTagsManager($resultCache);
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
        $query = new Query($this);

        $resultCacheProfile = new QueryCacheProfile();
        $query->setResultCacheProfile($resultCacheProfile);

        if (!empty($cacheTags) || !is_null($cacheLifeTime)) {

            $query->useResultCache(true);
            if (!is_null($cacheLifeTime)) {
                $query->setResultCacheLifetime($cacheLifeTime);
            }

            if (!empty($cacheTags)) {
                $resultCacheProfile->setCacheTags($cacheTags);
            }

        }

        if ( ! empty($dql)) {
            $query->setDql($dql);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm, $cacheLifeTime = null, array $cacheTags = array())
    {
        $query = new NativeQuery($this);

        $resultCacheProfile = new QueryCacheProfile();
        $query->setResultCacheProfile($resultCacheProfile);

        if (!empty($cacheTags) || !is_null($cacheLifeTime)) {

            $query->useResultCache(true);
            if (!is_null($cacheLifeTime)) {
                $query->setResultCacheLifetime($cacheLifeTime);
            }

            if (!empty($cacheTags)) {
                $resultCacheProfile = $resultCacheProfile->setCacheTags($cacheTags);
                $query->setResultCacheProfile($resultCacheProfile);
            }

        }

        $query->setSql($sql);
        $query->setResultSetMapping($rsm);

        return $query;
    }

    /**
     * Clears cache for tags
     *
     * @param string|array $tags single tag or array of tags
     *
     * @return bool
     */
    public function tagsClear($tags)
    {
        if (is_null($this->memcachedTagsManager)) {
            return false;
        }

        return $this->memcachedTagsManager->tagsClear($tags, false);
    }

    /**
     * {@inheritDoc}
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if ( ! $config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        switch (true) {
            case (is_array($conn)):
                $conn = \Doctrine\DBAL\DriverManager::getConnection(
                    $conn, $config, ($eventManager ?: new EventManager())
                );
                break;

            case ($conn instanceof Connection):
                if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                     throw ORMException::mismatchedEventManager();
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new EntityManager($conn, $config, $conn->getEventManager());
    }
}
