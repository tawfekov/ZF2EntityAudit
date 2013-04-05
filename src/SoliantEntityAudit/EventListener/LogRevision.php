<?php

namespace SoliantEntityAudit\EventListener;

use Doctrine\Common\EventSubscriber
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Events
    , Doctrine\ORM\Event\OnFlushEventArgs
    , Doctrine\ORM\Event\PostFlushEventArgs
    , Zend\ServiceManager\ServiceManager
    , SoliantEntityAudit\Entity\Revision as RevisionEntity
    , SoliantEntityAudit\Options\ModuleOptions
    , SoliantEntityAudit\Entity\RevisionEntity as RevisionEntityEntity
    , Zend\Code\Reflection\ClassReflection
    , Doctrine\ORM\PersistentCollection
    ;

class LogRevision implements EventSubscriber
{
    private $entityManager;
    private $serviceManager;
    private $config;
    private $revision;
    private $entities;
    private $reexchangeEntities;
    private $collectionUpdates;
    private $inAuditTransaction;

    public function __construct($serviceManager)
    {
        $this->setServiceManager($serviceManager);
        $this->setEntityManager($this->getServiceManager()->get("doctrine.entitymanager.orm_default"));
        $this->setConfig($this->getServiceManager()->get("auditModuleOptions"));
    }

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
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

    public function setConfig(ModuleOptions $config)
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
        return array(Events::onFlush, Events::postFlush);
    }

    private function setEntities($entities)
    {
        if ($this->entities) return $this;
        $this->entities = $entities;

        return $this;
    }

    private function resetEntities()
    {
        $this->entities = array();
        return $this;
    }

    private function getEntities()
    {
        return $this->entities;
    }

    private function getReexchangeEntities()
    {
        if (!$this->reexchangeEntities) $this->reexchangeEntities = array();
        return $this->reexchangeEntities;
    }

    private function resetReexchangeEntities()
    {
        $this->reexchangeEntities = array();
    }

    private function addReexchangeEntity($entityMap)
    {
        $this->reexchangeEntities[] = $entityMap;
    }

    public function addCollectionUpdate($collection)
    {
        $this->collectionUpdates[] = $collection;
    }

    public function getCollectionUpdates()
    {
        return $this->collectionUpdates;
    }

    public function setInAuditTransaction($setting)
    {
        $this->inAuditTransaction = $setting;
        return $this;
    }

    public function getInAuditTransaction()
    {
        return $this->inAuditTransaction;
    }

    private function getRevision()
    {
        return $this->revision;
    }

    private function resetRevision()
    {
        $this->revision = null;
        return $this;
    }

    // You must flush the revision for the compound audit key to work
    private function buildRevision()
    {
        if ($this->revision) return;

        $revision = new RevisionEntity();
        if ($this->getConfig()->getUser()) $revision->setUser($this->getConfig()->getUser());
        $revision->setComment($this->getServiceManager()->get('auditService')->getComment());

        $this->revision = $revision;
    }

    // Reflect audited entity properties
    private function getClassProperties($entity)
    {
        $properties = array();

        $reflectedAuditedEntity = new ClassReflection($entity);

        // Get mapping from metadata

        foreach($reflectedAuditedEntity->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);

            // Set values to getId for classes
            if ($value instanceof \StdClass and method_exists($value, 'getId')) {
                $value = $value->getId();
            }

            // If a property is an object we probably are not mapping that to
            // a field.  Do no special handing...
#            if (gettype($value) == 'object') {
##                $value = $value->getId();
#            }
            $properties[$property->getName()] = $value;
        }

        return $properties;
    }

    private function auditEntity($entity, $revisionType)
    {
        if (!in_array(get_class($entity), array_keys($this->getConfig()->getAuditedEntityClasses())))
            return array();

        $auditEntityClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', get_class($entity));
        $auditEntity = new $auditEntityClass();
        $auditEntity->exchangeArray($this->getClassProperties($entity));

        $revisionSetter = 'set' . $this->getConfig()->getRevisionFieldName();
        $auditEntity->$revisionSetter($this->getRevision());

        $revisionEntity = new RevisionEntityEntity();
        $revisionEntity->setRevision($this->getRevision());
        $revisionEntity->setRevisionType($revisionType);

        // Re-exchange data after flush to map generated fields
        if ($revisionType ==  'INS' or $revisionType ==  'UPD') {
            $this->addReexchangeEntity(array(
                'auditEntity' => $auditEntity,
                'entity' => $entity,
                'revisionEntity' => $revisionEntity,
            ));
        } else {
            $revisionEntity->setAuditEntity($auditEntity);
        }

        return array($auditEntity, $revisionEntity);
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entities = array();

        $this->buildRevision();

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions() AS $entity) {
            $entities = array_merge($entities, $this->auditEntity($entity, 'INS'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityUpdates() AS $entity) {
            $entities = array_merge($entities, $this->auditEntity($entity, 'UPD'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityDeletions() AS $entity) {
            $entities = array_merge($entities, $this->auditEntity($entity, 'DEL'));
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionDeletions() AS $col) {
            die('deletion.  If you reached this you should just try to figure out the next foreach block first.');
            print_r($col);die();
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() AS $collectionToUpdate) {
            if ($collectionToUpdate instanceof PersistentCollection) {
                $this->addCollectionUpdate($collectionToUpdate);
            }
        }

        $this->setEntities($entities);
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->getEntities() and !$this->getInAuditTransaction()) {
            $this->setInAuditTransaction(true);
            $this->getEntityManager()->beginTransaction();

            $this->getEntityManager()->persist($this->getRevision());
            $this->getEntityManager()->flush();

            // Insert entites will trigger key generation and must be
            // re-exchanged (delete entites go out of scope)
            foreach ($this->getReexchangeEntities() as $entityMap) {
                $entityMap['auditEntity']->exchangeArray($this->getClassProperties($entityMap['entity']));
                $entityMap['revisionEntity']->setAuditEntity($entityMap['auditEntity']);
            }

            foreach ($this->getEntities() as $entity) {

                // Audit complete collections as a snapshot of an updated entity
                # FIXME: many to many data are not populated in audit
                foreach ($this->getCollectionUpdates() as $collection) {
                    foreach ($this->getClassProperties($entity) as $key => $value) {
                        if ($value instanceof PersistentCollection) {
                            die('persistent collection found');
                            continue;
                        }
                    }
                }

                $this->getEntityManager()->persist($entity);
            }


            $this->getEntityManager()->flush();

            $this->getEntityManager()->commit();
            $this->resetEntities();
            $this->resetReexchangeEntities();
            $this->resetRevision();
            $this->setInAuditTransaction(false);
        }
    }
}
