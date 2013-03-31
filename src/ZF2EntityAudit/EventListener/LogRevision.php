<?php

namespace ZF2EntityAudit\EventListener;

use Doctrine\Common\EventSubscriber
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Events
    , Doctrine\ORM\Event\OnFlushEventArgs
    , Doctrine\ORM\Event\LifecycleEventArgs
    , ZF2EntityAudit\Entity\Revision as RevisionEntity
    , ZF2EntityAudit\Config
    , Zend\Code\Reflection\ClassReflection;
    ;

class LogRevision implements EventSubscriber
{
    private $entityManager;
    private $config;
    private $revision;

    public function __construct(EntityManager $entityManager, Config $config)
    {
        $this->setEntityManager($entityManager);
        $this->setConfig($config);
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::onFlush
            , Events::preRemove
            , Events::postPersist
            , Events::postUpdate
        );
    }

    // Reflect audited entity properties
    private function getEntityProperties($entity)
    {
        $properties = array();

        $reflectedAuditedEntity = new ClassReflection($entity);
        foreach($reflectedAuditedEntity->getProperties() as $property) {
            $property->setAccessible(true);
            $properties[$property->getName()] = $property->getValue($entity);
        }

        return $properties;
    }

    // Copy all properties from entity to it's audited version and persist
    private function auditEntity($entity, $revisionType)
    {
        if (!in_array(get_class($entity), $this->getConfig()->getAuditedEntityClasses()))
            return;

        $auditEntityClass = 'ZF2EntityAudit\\Entity\\' . str_replace('\\', '_', get_class($entity));
        $auditEntity = new $auditEntityClass();
        $auditEntity->setAuditProperties($this->getEntityProperties($entity));

        $revisionSetter = 'set' . $this->getConfig()->getRevisionFieldName();
        $auditEntity->$revisionSetter($this->getRevision($revisionType));

        $this->getEntityManager()->persist($auditEntity);
        $this->getEntityManager()->flush();

        return $auditEntity;
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        return $this->auditEntity($eventArgs->getEntity(), 'INS');
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        return $this->auditEntity($eventArgs->getEntity(), 'UPD');
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        return $this->auditEntity($eventArgs->getEntity(), 'DEL');
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->resetRevision();
        return;
    }

    // A revision can be used across multiple entities involved in a transaction
    private function getRevision($revisionType)
    {
        if (!$this->revision) {
            $revision = new RevisionEntity();
            $revision->setUser($this->getConfig()->getUser());
            $revision->setRevisionType($revisionType);

            $this->getEntityManager()->persist($revision);
            // You must flush the revision for the compound audit key to work
            $this->getEntityManager()->flush();

            $this->revision = $revision;
        }

        return $this->revision;
    }

    private function resetRevision()
    {
        $this->revision = null;
        return $this;
    }
}
