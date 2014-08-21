<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Cache\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TagCacheRemover
{
    private $container;
    private $entityClasses = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        if (!is_callable([$em, 'tagsClear']))
            return;

        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->registerSheduledEntityClass($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $className = $this->registerSheduledEntityClass($entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $className = $this->registerSheduledEntityClass($entity);
        }

        // из документации, но не смог выяснить, в каких ситуациях они есть
        //foreach ($uow->getScheduledCollectionDeletions() as $col) {
        //}
        //foreach ($uow->getScheduledCollectionUpdates() as $col) {
        //}

        if (sizeof($this->entityClasses)) {
            $tags = array();
            foreach ($this->entityClasses as $className => $entityClasses) {
                $tags[] = $className;
                foreach ($entityClasses as $id) {
                    $tags[] = sprintf('%s[id="%s"]', $className , $id);
                }
            }
            $em->tagsClear($tags, true);
            $this->entityClasses = [];
        }
    }

    public function registerSheduledEntityClass($entity)
    {
        if (!$entity) {
            return;
        }
        $em = $this->container->get('doctrine.orm.entity_manager');

        $classMetadata = $em->getClassMetadata(get_class($entity));
        if ($classMetadata) {
            $refClass = $classMetadata->getName();

            if (!isset($this->entityClasses[$refClass])) {
                $this->entityClasses[$refClass] = array();
            }

            if (
                is_callable([$entity, 'getId'])
                && !isset($this->entityClasses[$refClass][$entity->getId()])
            ) {
                $this->entityClasses[$refClass][] = $entity->getId();
            }
        }

        return $refClass;
    }
}
