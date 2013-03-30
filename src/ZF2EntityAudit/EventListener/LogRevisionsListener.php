<?php

namespace ZF2EntityAudit\EventListener;

use ZF2EntityAudit\AuditManager
    , Doctrine\Common\EventSubscriber
    , Doctrine\ORM\Events
    , Doctrine\ORM\Event\OnFlushEventArgs
    , Doctrine\ORM\Event\LifecycleEventArgs
    , Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\DBAL\Types\Type
    , ZF2EntityAudit\Entity\Revision as RevisionEntity
    ;

class LogRevisionsListener implements EventSubscriber
{
    /**
     * @var ZF2EntityAudit\AuditManager
     */
    private $auditManager;

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $insertRevisionSQL = array();

    /**
     * @var Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     * @var ZF2EntityAudit\Entity\Revision
     */
    private $revision;

    public function __construct(Manager $auditManager)
    {
        $this->setAuditManager($auditManager);
    }

    public function setAuditManager(Manager $auditManager)
    {
        $this->auditManager = $auditManager;
        return $this;
    }

    public function getAuditManager()
    {
        return $this->auditManager;
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::onFlush
            , Events::postPersist
            , Events::postUpdate
        );
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));

        if (!in_array($class->getName(), $this->getAuditManager()->getConfig()->getAuditedEntities()) {
            return
        }

        $this->saveRevisionEntityData($class, $this->getOriginalEntityData($entity), 'INS');
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();

        $class = $this->em->getClassMetadata(get_class($entity));
        if (!in_array($class->getName(), $this->getAuditManager()->getConfig()->getAuditedEntities()) {
            return
        }

        $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
        $this->saveRevisionEntityData($class, $entityData, 'UPD');
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->conn = $this->em->getConnection();
        $this->uow = $this->em->getUnitOfWork();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->resetRevision();

        foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));

            if (!in_array($class->getName(), $this->getAuditManager()->getConfig()->getAuditedEntities()) {
                continue;
            }

            if (!$this->getAuditManager()->getConfig()->getUser())
                throw new \Exception('User is not authentictated.  Cannot audit entities.');

            $entityData = array_merge($this->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
            $this->saveRevisionEntityData($class, $entityData, 'DEL');
        }
    }

    /**
     * get original entity data, including versioned field, if "version" constraint is used
     *
     * @param mixed $entity
     * @return array
     */
    private function getOriginalEntityData($entity)
    {
        $class = $this->em->getClassMetadata(get_class($entity));
        $data = $this->uow->getOriginalEntityData($entity);
        if( $class->isVersioned ){
            $versionField = $class->versionField;
            $data[$versionField] = $class->reflFields[$versionField]->getValue($entity);
        }
        return $data;
    }

    private function resetRevision()
    {
        $this->revision = null;
        return $this;
    }

    // A revision can be used across multiple entities involved in a transaction
    private function getRevision()
    {
        if (!$this->revision) {
            $revision = new RevisionEntity();
            $revision->setUser($this->getAuditManager()->getConfig()->getUser());

            $this->em->persist($revision);
            $this->em->flush();

            $this->revision = $revision;
        }

        return $this->revision;
    }

    private function getInsertRevisionSQL($class)
    {
        if (!isset($this->insertRevisionSQL[$class->name])) {
            $placeholders = array('?', '?');
            $tableName    = $this->getAuditManager()->getConfig()->getTablePrefix()
                . $class->table['name']
                . $this->getAuditManager()->getConfig()->getTableSuffix();

            $sql = "INSERT INTO " . $tableName . " ("
                    . $this->getAuditManager()->getConfig()->getRevisionFieldName()
                    . ", "
                    . $this->getAuditManager()->getConfig()->getRevisionTypeFieldName();

            foreach ($class->fieldNames AS $field) {
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $placeholders[] = (!empty($class->fieldMappings[$field]['requireSQLConversion']))
                    ? $type->convertToDatabaseValueSQL('?', $this->platform)
                    : '?';
                $sql .= ', ' . $class->getQuotedColumnName($field, $this->platform);
            }

            foreach ($class->associationMappings AS $assoc) {
                if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $sql .= ', ' . $sourceCol;
                        $placeholders[] = '?';
                    }
                }
            }

            $sql .= ") VALUES (" . implode(", ", $placeholders) . ")";
            $this->insertRevisionSQL[$class->name] = $sql;
        }

        return $this->insertRevisionSQL[$class->name];
    }

    /**
     * @param ClassMetadata $class
     * @param array $entityData
     * @param string $revType
     */
    private function saveRevisionEntityData($class, $entityData, $revType)
    {
        $params = array($this->getRevision(), $revType);
        $types = array(\PDO::PARAM_INT, \PDO::PARAM_STR);

        foreach ($class->fieldNames AS $field) {
            $params[] = $entityData[$field];
            $types[] = $class->fieldMappings[$field]['type'];
        }

        foreach ($class->associationMappings AS $field => $assoc) {
            if (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                if ($entityData[$field] !== null) {
                    $relatedId = $this->uow->getEntityIdentifier($entityData[$field]);
                }

                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                    if ($entityData[$field] === null) {
                        $params[] = null;
                        $types[] = \PDO::PARAM_STR;
                    } else {
                        $params[] = $relatedId[$targetClass->fieldNames[$targetColumn]];
                        $types[] = $targetClass->getTypeOfColumn($targetColumn);
                    }
                }
            }
        }

        $this->conn->executeUpdate($this->getInsertRevisionSQL($class), $params, $types);
    }
}
