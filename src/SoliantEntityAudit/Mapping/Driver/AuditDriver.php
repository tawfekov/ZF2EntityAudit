<?php

namespace SoliantEntityAudit\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata
    , Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    ;

final class AuditDriver implements MappingDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     */
    function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $serviceManager = \SoliantEntityAudit\Module::getServiceManager();

        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
        $config = $serviceManager->get('auditModuleOptions');
        $metadataFactory = $entityManager->getMetadataFactory();
        $builder = new ClassMetadataBuilder($metadata);

        if ($className == 'SoliantEntityAudit\\Entity\RevisionEntity') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addManyToOne('revision', 'SoliantEntityAudit\\Entity\\Revision');
            $builder->addField('entityKeys', 'string');
            $builder->addField('auditEntityClass', 'string');
            $builder->addField('targetEntityClass', 'string');
            $builder->addField('revisionType', 'string');

            $metadata->setTableName($config->getRevisionEntityTableName());
            return;
        }

        // Revision is managed here rather than a separate namespace and driver
        if ($className == 'SoliantEntityAudit\\Entity\\Revision') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addField('comment', 'text', array('nullable' => true));
            $builder->addField('timestamp', 'datetime');

            // Add association between RevisionEntity and Revision
            $builder->addOneToMany('revisionEntities', 'SoliantEntityAudit\\Entity\\RevisionEntity', 'revision');

            // Add assoication between ZfcUser and Revision
            $zfcUserMetadata = $metadataFactory->getMetadataFor(\SoliantEntityAudit\Module::getZfcUserEntity());
            $builder
                ->createManyToOne('user', $zfcUserMetadata->getName())
                ->addJoinColumn('user_id', $zfcUserMetadata->getSingleIdentifierColumnName())
                ->build();

            $metadata->setTableName($config->getRevisionTableName());
            return;
        }

        //  Build a discovered many to many join class
        $joinClasses = $config->getJoinClasses();
        if (in_array($className, array_keys($joinClasses))) {
            $builder->addManyToOne($config->getRevisionFieldName(), 'SoliantEntityAudit\\Entity\\Revision');
            $identifiers = array($config->getRevisionFieldName());

            foreach ($joinClasses[$className]['joinColumns'] as $joinColumn) {
                $builder->addField($joinColumn['name'], 'integer', array('nullable' => true));
                $identifiers[] = $joinColumn['name'];
            }

            foreach ($joinClasses[$className]['inverseJoinColumns'] as $joinColumn) {
                $builder->addField($joinColumn['name'], 'integer', array('nullable' => true));
                $identifiers[] = $joinColumn['name'];
            }

            $metadata->setTableName($config->getTableNamePrefix() . $joinClasses[$className]['name'] . $config->getTableNameSuffix());
            $metadata->setIdentifier($identifiers);
            return;
        }


        // Get the entity this entity audits
        $metadataClassName = $metadata->getName();
        $metadataClass = new $metadataClassName();

        $auditedClassMetadata = $metadataFactory->getMetadataFor($metadataClass->getAuditedEntityClass());

        $builder->addManyToOne($config->getRevisionFieldName(), 'SoliantEntityAudit\\Entity\\Revision');
        $identifiers = array($config->getRevisionFieldName());

        // Add fields from target to audit entity
        foreach ($auditedClassMetadata->getFieldNames() as $fieldName) {
            $builder->addField($fieldName, $auditedClassMetadata->getTypeOfField($fieldName), array('nullable' => true));
            if ($auditedClassMetadata->isIdentifier($fieldName)) $identifiers[] = $fieldName;
        }

        foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
            if (!$mapping['isOwningSide']) continue;

            if (isset($mapping['joinTable'])) {
                continue;
                # print_r($mapping['joinTable']);
                # die('driver');
            }


            if (isset($mapping['joinTableColumns'])) {
                foreach ($mapping['joinTableColumns'] as $field) {
                    $builder->addField($mapping['fieldName'], 'integer', array('nullable' => true, 'columnName' => $field));
                }
            } elseif (isset($mapping['joinColumnFieldNames'])) {
                foreach ($mapping['joinColumnFieldNames'] as $field) {
                    $builder->addField($mapping['fieldName'], 'integer', array('nullable' => true, 'columnName' => $field));
                }
            } else {
                throw new \Exception('Unhandled association mapping');
            }

        }

        $metadata->setTableName($config->getTableNamePrefix() . $auditedClassMetadata->getTableName() . $config->getTableNameSuffix());
        $metadata->setIdentifier($identifiers);

        return;
    }

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    function getAllClassNames()
    {
        $serviceManager = \SoliantEntityAudit\Module::getServiceManager();
        $config = $serviceManager->get('auditModuleOptions');
        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $entityManager->getMetadataFactory();

        $auditEntities = array();
        foreach ($config->getAuditedEntityClasses() as $name => $targetClassOptions) {
            $auditClassName = "SoliantEntityAudit\\Entity\\" . str_replace('\\', '_', $name);
            $auditEntities[] = $auditClassName;
            $auditedClassMetadata = $metadataFactory->getMetadataFor($name);

            foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
                if (isset($mapping['joinTable'])) {
                    $auditJoinTableClassName = "SoliantEntityAudit\\Entity\\" . str_replace('\\', '_', $mapping['joinTable']['name']);
                    $auditEntities[] = $auditJoinTableClassName;
                    $config->addJoinClass($auditJoinTableClassName, $mapping['joinTable']);
                }
            }
        }

        // Add revision (manage here rather than separate namespace)
        $auditEntities[] = 'SoliantEntityAudit\\Entity\\Revision';
        $auditEntities[] = 'SoliantEntityAudit\\Entity\\RevisionEntity';

        return $auditEntities;
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    function isTransient($className) {
        return true;
    }
}
