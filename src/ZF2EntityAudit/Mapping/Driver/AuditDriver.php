<?php

namespace ZF2EntityAudit\Mapping\Driver;

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
        $serviceManager = \ZF2EntityAudit\Module::getServiceManager();

        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
        $config = $serviceManager->get('auditModuleOptions');
        $metadataFactory = $entityManager->getMetadataFactory();
        $builder = new ClassMetadataBuilder($metadata);

        if ($className == 'ZF2EntityAudit\\Entity\RevisionEntity') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addManyToOne('revision', 'ZF2EntityAudit\\Entity\\Revision');
            $builder->addField('entityKeys', 'string');
            $builder->addField('auditEntityClass', 'string');
            $builder->addField('targetEntityClass', 'string');
            $builder->addField('revisionType', 'string');

            $metadata->setTableName($config->getRevisionEntityTableName());
            return;
        }

        // Revision is managed here rather than a separate namespace and driver
        if ($className == 'ZF2EntityAudit\\Entity\\Revision') {
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addField('comment', 'text', array('nullable' => true));
            $builder->addField('timestamp', 'datetime');

            // Add association between RevisionEntity and Revision
            $builder->addOneToMany('revisionEntities', 'ZF2EntityAudit\\Entity\\RevisionEntity', 'revision');

            // Add assoication between ZfcUser and Revision
            $zfcUserMetadata = $metadataFactory->getMetadataFor(\ZF2EntityAudit\Module::getZfcUserEntity());
            $builder
                ->createManyToOne('user', $zfcUserMetadata->getName())
                ->addJoinColumn('user_id', $zfcUserMetadata->getSingleIdentifierColumnName())
                ->build();

            $metadata->setTableName($config->getRevisionTableName());
            return;
        }

        // Get the entity this entity audits
        $metadataClassName = $metadata->getName();
        $metadataClass = new $metadataClassName();

        $auditedClassMetadata = $metadataFactory->getMetadataFor($metadataClass->getAuditedEntityClass());

        $builder->addManyToOne($config->getRevisionFieldName(), 'ZF2EntityAudit\\Entity\\Revision');
        $identifiers = array($config->getRevisionFieldName());

        // Add fields from target to audit entity
        foreach ($auditedClassMetadata->getFieldNames() as $fieldName) {
            $builder->addField($fieldName, $auditedClassMetadata->getTypeOfField($fieldName), array('nullable' => true));
            if ($auditedClassMetadata->isIdentifier($fieldName)) $identifiers[] = $fieldName;
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
        $serviceManager = \ZF2EntityAudit\Module::getServiceManager();
        $config = $serviceManager->get('auditModuleOptions');

        $auditEntities = array();
        foreach ($config->getAuditedEntityClasses() as $name)
            $auditEntities[] = "ZF2EntityAudit\\Entity\\" . str_replace('\\', '_', $name);

        // Add revision (manage here rather than separate namespace)
        $auditEntities[] = 'ZF2EntityAudit\\Entity\\Revision';
        $auditEntities[] = 'ZF2EntityAudit\\Entity\\RevisionEntity';

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
