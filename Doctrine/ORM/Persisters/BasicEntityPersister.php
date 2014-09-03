<?php

namespace Intaro\MemcachedTagsBundle\Doctrine\ORM\Persisters;

use Doctrine\ORM\Query;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Persisters\BasicEntityPersister as BaseBasicEntityPersister;

use Intaro\MemcachedTagsBundle\Doctrine\Cache\QueryCacheProfile;
use Intaro\MemcachedTagsBundle\Doctrine\Cache\MemcacheTagsManager;

/**
 * {@inheritDoc}
 */
class BasicEntityPersister extends BaseBasicEntityPersister
{
    /**
     * {@inheritDoc}
     */
    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = 0, $limit = null, array $orderBy = null)
    {
        $sql = $this->getSelectSQL($criteria, $assoc, $lockMode, $limit, null, $orderBy);

        $reader = new AnnotationReader();

        $assosiationCache = null;
        $assosiationCache = $reader->getClassAnnotation(
            $this->class->getReflectionClass(),
            'Intaro\\MemcachedTagsBundle\\Doctrine\\Annotation\\AssociationCache'
        );

        $resultCacheProfile = null;
        $resultCache = $this->em->getConfiguration()->getResultCacheImpl();
        if (!is_null($assosiationCache) && $resultCache) {

            $resultCacheProfile = new QueryCacheProfile(0, null, $resultCache);

            if ($assosiationCache->lifetime > 0) {
                $resultCacheProfile = $resultCacheProfile->setLifetime($assosiationCache->lifetime);
            }

            $tags = array();
            if (!empty($assosiationCache->tags)) {
                $tags = $assosiationCache->tags;
            } else {
                $tags = array($this->class->getName());
            }

            foreach ($tags as $key => $tag) {
                foreach ($criteria as $field => $value) {
                    $tags[$key] = MemcacheTagsManager::formatTag($tag, array($field => $value));
                }
            }

            $resultCacheProfile = $resultCacheProfile->setCacheTags($tags);
        }

        list($params, $types) = $this->expandParameters($criteria);
        $stmt = $this->conn->executeQuery($sql, $params, $types, $resultCacheProfile);

        if ($entity !== null) {
            $hints[Query::HINT_REFRESH]         = true;
            $hints[Query::HINT_REFRESH_ENTITY]  = $entity;
        }

        $hydrator = $this->em->newHydrator($this->selectJoinSql ? Query::HYDRATE_OBJECT : Query::HYDRATE_SIMPLEOBJECT);
        $entities = $hydrator->hydrateAll($stmt, $this->rsm, $hints);

        return $entities ? $entities[0] : null;
    }

    /**
     * Expands the parameters from the given criteria and use the correct binding types if found.
     *
     * @param array $criteria
     *
     * @return array
     */
    private function expandParameters($criteria)
    {
        $params = array();
        $types  = array();

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue; // skip null values.
            }

            $types[]  = $this->getType($field, $value);
            $params[] = $this->getValue($value);
        }

        return array($params, $types);
    }

    /**
     * Infers field type to be used by parameter type casting.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return integer
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function getType($field, $value)
    {
        switch (true) {
            case (isset($this->class->fieldMappings[$field])):
                $type = $this->class->fieldMappings[$field]['type'];
                break;

            case (isset($this->class->associationMappings[$field])):
                $assoc = $this->class->associationMappings[$field];

                if (count($assoc['sourceToTargetKeyColumns']) > 1) {
                    throw Query\QueryException::associationPathCompositeKeyNotSupported();
                }

                $targetClass  = $this->em->getClassMetadata($assoc['targetEntity']);
                $targetColumn = $assoc['joinColumns'][0]['referencedColumnName'];
                $type         = null;

                if (isset($targetClass->fieldNames[$targetColumn])) {
                    $type = $targetClass->fieldMappings[$targetClass->fieldNames[$targetColumn]]['type'];
                }

                break;

            default:
                $type = null;
        }

        if (is_array($value)) {
            $type = Type::getType($type)->getBindingType();
            $type += Connection::ARRAY_PARAM_OFFSET;
        }

        return $type;
    }

    /**
     * Retrieves parameter value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function getValue($value)
    {
        if ( ! is_array($value)) {
            return $this->getIndividualValue($value);
        }

        $newValue = array();

        foreach ($value as $itemValue) {
            $newValue[] = $this->getIndividualValue($itemValue);
        }

        return $newValue;
    }

    /**
     * Retrieves an individual parameter value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function getIndividualValue($value)
    {
        if ( ! is_object($value) || ! $this->em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
            return $value;
        }

        return $this->em->getUnitOfWork()->getSingleIdentifierValue($value);
    }
}
