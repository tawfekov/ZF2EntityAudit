<?php

namespace ZF2EntityAudit;

use Doctrine\DBAL\Types\Type
    , Doctrine\ORM\EntityManager
    , Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\Common\Collections\ArrayCollection
    , Zend\ServiceManager\ServiceManager
    , ZF2EntityAudit\Options\ModuleOptions
    , ZF2EntityAudit\Exception as AuditException
    ;

class Reader
{
    private $entityManager;
    private $config;

    /**
     * @param EntityManager $em
     * @param Config $config
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->setEntityManager($serviceManager->get('doctrine.entitymanager.orm_default'));
        $this->setConfig($serviceManager->get('auditModuleOptions'));
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

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Find a class at the specific revision.
     *
     * This method does not require the revision to be exact but it also searches for an earlier revision
     * of this entity and always returns the latest revision below or equal the given revision
     *
     * @param string $className
     * @param mixed $id
     * @param int $revision
     * @return object
     */
    public function find($className, $id, $revision)
    {
        if (!in_array($className, $this->getConfig()->getAuditedEntityClasses())) {
            throw AuditException::notAudited($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableNamePrefix() . $class->table['name'] . $this->config->getTableNameSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL  = "e." . $this->config->getRevisionFieldName() ." <= ?";

        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                $columnName = $class->fieldMappings[$idField]['columnName'];
            } else if (isset($class->associationMappings[$idField])) {
                $columnName = $class->associationMappings[$idField]['joinColumns'][0];
            }

            $whereSQL .= " AND " . $columnName . " = ?";
        }

        $columnList = "";
        $columnMap  = array();

        foreach ($class->fieldNames as $columnName => $field) {
            if ($columnList) {
                $columnList .= ', ';
            }

            $type = Type::getType($class->fieldMappings[$field]['type']);
            $columnList .= $type->convertToPHPValueSQL(
                $class->getQuotedColumnName($field, $this->platform), $this->platform) .' AS ' . $field;
            $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
        }

        foreach ($class->associationMappings AS $assoc) {
            if ( ($assoc['type'] & ClassMetadata::TO_ONE) == 0 || !$assoc['isOwningSide']) {
                continue;
            }

            foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                if ($columnList) {
                    $columnList .= ', ';
                }

                $columnList .= $sourceCol;
                $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
            }
        }

        $values = array_merge(array($revision), array_values($id));

        $query = "SELECT " . $columnList . " FROM " . $tableName . " e WHERE " . $whereSQL . " ORDER BY e.rev DESC";
        $row = $this->em->getConnection()->fetchAssoc($query, $values);

        if (!$row) {
            throw AuditException::noRevisionFound($class->name, $id, $revision);
        }

        $revisionData = array();

        foreach ($columnMap as $fieldName => $resultColumn) {
            $revisionData[$fieldName] = $row[$resultColumn];
        }

        return $this->createEntity($class->name, $revisionData);
    }

    /**
     * Simplified and stolen code from UnitOfWork::createEntity.
     *
     * NOTICE: Creates an old version of the entity, HOWEVER related associations are all managed entities!!
     *
     * @param string $className
     * @param array $data
     * @return object
     */
    private function createEntity($className, array $data)
    {
        $class = $this->em->getClassMetadata($className);
        $entity = $class->newInstance();

        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $value = $type->convertToPHPValue($value, $this->platform);
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }

        foreach ($class->associationMappings as $field => $assoc) {
            // Check if the association is not among the fetch-joined associations already.
            if (isset($hints['fetched'][$className][$field])) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            if ($assoc['type'] & ClassMetadata::TO_ONE) {
                if ($assoc['isOwningSide']) {
                    $associatedId = array();
                    foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                        $joinColumnValue = isset($data[$srcColumn]) ? $data[$srcColumn] : null;
                        if ($joinColumnValue !== null) {
                            $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                        }
                    }
                    if ( ! $associatedId) {
                        // Foreign key is NULL
                        $class->reflFields[$field]->setValue($entity, null);
                    } else {
                        $associatedEntity = $this->em->getReference($targetClass->name, $associatedId);
                        $class->reflFields[$field]->setValue($entity, $associatedEntity);
                    }
                } else {
                    // Inverse side of x-to-one can never be lazy
                    $class->reflFields[$field]->setValue($entity, $this->getEntityPersister($assoc['targetEntity'])
                            ->loadOneToOneEntity($assoc, $entity));
                }
            } else {
                // Inject collection
                $reflField = $class->reflFields[$field];
                $reflField->setValue($entity, new ArrayCollection);
            }
        }

        return $entity;
    }

    /**
     * Return a list of all revisions.
     *
     * @param int $limit
     * @param int $offset
     * @return Revision[]
     */
    public function findRevisionHistory($limit = 20, $offset = 0)
    {
        return $this->getEntityManager()->getRepository('ZF2EntityAudit\\Entity\\Revision')->findBy(array(), array(), $limit, $offset);
    }

    /**
     * Return a list of ChangedEntity instances created at the given revision.
     *
     * @param int $revision
     * @return ChangedEntity[]
     */
    public function findEntitesChangedAtRevision($revision)
    {
        $auditedEntities = $this->getConfig()->getAuditedEntityClasses();

        $changedEntities = array();
        foreach ($auditedEntities AS $className) {
            $class = $this->getEntityManager()->getClassMetadata($className);
            $tableName = $this->config->getTableNamePrefix() . $class->table['name'] . $this->config->getTableNameSuffix();

            $whereSQL   = "e." . $this->config->getRevisionFieldName() ." = ?";
            $columnList = "e." . 'revisionType';
            $columnMap  = array();

            foreach ($class->fieldNames as $columnName => $field) {
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $columnList .= ', ' . $type->convertToPHPValueSQL(
                    $class->getQuotedColumnName($field, $this->platform), $this->platform) . ' AS ' . $field;
                $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
            }

            foreach ($class->associationMappings AS $assoc) {
                if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $columnList .= ', ' . $sourceCol;
                        $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
                    }
                }
            }

            $this->platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
            $query = "SELECT " . $columnList . " FROM " . $tableName . " e WHERE " . $whereSQL;
            $revisionsData = $this->getEntityManager()->getConnection()->executeQuery($query, array($revision));

            foreach ($revisionsData AS $row) {
                $id   = array();
                $data = array();

                foreach ($class->identifier AS $idField) {
                    $id[$idField] = $row[$idField];
                }

                foreach ($columnMap as $fieldName => $resultName) {
                    $data[$fieldName] = $row[$resultName];
                }

                $entity = $this->createEntity($className, $row);
                $changedEntities[] = new ChangedEntity($className, $id, $row['revisionType'], $entity);
            }
        }
        return $changedEntities;
    }

    /**
     * Return the revision object for a particular revision.
     *
     * @param  int $rev
     * @return Revision
     */
    public function findRevision($rev)
    {
        return $this->getEntityManager()->getRepository('Audit\Entity\Revision')->find($rev);
    }

    /**
     * Find all revisions that were made of entity class with given id.
     *
     * @param string $className
     * @param mixed $id
     * @return Revision[]
     */
    public function findRevisions($className, $id)
    {
        if (!in_array($className, $this->getConfig()->getAuditedEntityClasses())) {
            throw AuditException::notAudited($className);
        }

        $class = $this->getEntityManager()->getClassMetadata($className);
        $tableName = $this->config->getTableNamePrefix() . $class->table['name'] . $this->config->getTableNameSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL = "";
        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->fieldMappings[$idField]['columnName'] . " = ?";
            } else if (isset($class->associationMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->associationMappings[$idField]['joinColumns'][0] . " = ?";
            }
        }

        $query = "SELECT r.id FROM " . $this->config->getRevisionTableName() . " r " .
                 "INNER JOIN " . $tableName . " e ON r.id = e." . $this->config->getRevisionFieldName() . " WHERE " . $whereSQL . " ORDER BY r.id DESC";
        $revisionsData = $this->getEntityManager()->getConnection()->fetchAll($query, array_values($id));

        $revisions = array();
        $this->platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();

        foreach ($revisionsData AS $row) {
            $revisions[] = $this->findRevision($row['id']);
        }

        return $revisions;
    }

    protected function getEntityPersister($entity)
    {
        $uow = $this->getEntityManager()->getUnitOfWork();
        return $uow->getEntityPersister($entity);
    }
}
