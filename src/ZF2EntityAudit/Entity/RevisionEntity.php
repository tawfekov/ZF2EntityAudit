<?php

namespace ZF2EntityAudit\Entity;

use ZfcUser\Entity\UserInterface
    , Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    , Zend\ServiceManager\ServiceManager
    , Zend\Code\Reflection\ClassReflection;
    ;

class RevisionEntity
{
    private $id;

    // Foreign key to the revision
    private $revision;

    // An array of primary keys for the target entity
    private $entityKeys;

    // The entity name
    private $entityClass;

    public function getServiceManager()
    {
        return \ZF2EntityAudit\Module::getServiceManager();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setRevision(Revision $revision)
    {
        $this->revision = $revision;
        return $this;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function setEntityClass($value)
    {
        $this->entityClass = $value;
        return $this;
    }

    public function getRevision()
    {
        return $this->revision;
    }

    public function getEntityKeys()
    {
        return unserialize($this->entityKeys);
    }

    public function setEntityKeys($value)
    {
        $this->entityKeys = serialize($value);
    }

    public function setEntity(Audit $entity)
    {
        $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $entityManager->getMetadataFactory();

        // Get entity metadata
        // Audited entities will always have composite keys
        $metadata = $metadataFactory->getMetadataFor(get_class($entity));
        $identifiers = $metadata->getIdentifierValues($entity);
        if(isset($identifiers['revision'])) unset($identifiers['revision']);

        $this->setEntityClass(get_class($entity));
        $this->setEntityKeys($identifiers);

        return $this;
    }

    public function getEntity()
    {
        $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');

        return $entityManager->getRepository($this->getEntityClass())->findOneBy($this->getEntityKeys());
    }
}