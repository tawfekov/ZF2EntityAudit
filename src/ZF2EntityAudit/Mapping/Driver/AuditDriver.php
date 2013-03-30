<?php
namespace ZF2EntityAudit\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;

use Zend\Code\Reflection\ClassReflection;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\AssociationBuilder;

final class AuditDriver implements MappingDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadataInfo $metadata
     */
    function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $serviceManager = \ZF2EntityAudit\Module::getServiceManager();

        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
        $auditManager = $serviceManager->get('auditManager');

        $config = $auditManager->getConfiguration();
        $metadataFactory = $entityManager->getMetadataFactory();

        // Revision is managed here rather than a separate namespace and driver
        if ($className == 'ZF2EntityAudit\\Entity\\Revision') {
            $builder = new ClassMetadataBuilder($metadata);
            $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
            $builder->addField('comment', 'text');
            $builder->addField('timestamp', 'datetime');

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

        // Verify the metadata for the target class has been loaded
        if (!$metadataFactory->hasMetadataFor($metadataClass->getAuditedEntityClass()))
            throw new \Exception('Metadata is not loaded for '
                . $metadataClass->getAuditedEntityClass()
                . '  Is the auditing module last to load?');

        $auditedClassMetadata = $metadataFactory->getMetadataFor($metadataClass->getAuditedEntityClass());

        $builder = new ClassMetadataBuilder($metadata);
        $builder->addManyToOne($config->getRevisionFieldName(), 'ZF2EntityAudit\Entity\Revision');
        $identifiers = array($config->getRevisionFieldName());

        // Add fields from target to audit entity
        foreach ($auditedClassMetadata->getFieldNames() as $fieldName) {
            $builder->addField($fieldName, $auditedClassMetadata->getTypeOfField($fieldName));
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
        $auditManager = $serviceManager->get('auditManager');

        $config = $auditManager->getConfiguration();
        // Generate audit entities
        $auditEntities = array();
        foreach ($config->getAuditedEntityClasses() as $name) {
            $auditEntities[] = "ZF2EntityAudit\\Entity\\" . str_replace('\\', '_', $name);

            $auditClassGenerator = ClassGenerator::fromReflection(new ClassReflection($name));

            // Namespace corrections by useing all sub-namespaces of the original namespace
            $auditClassGenerator->addUse($auditClassGenerator->getNamespaceName());
            $subNamespace = $auditClassGenerator->getNamespaceName();
            while ($subNamespace = strstr($subNamespace, '\\', true)) {
                $auditClassGenerator->addUse($subNamespace);
            }

            // Add function to return the entity this entity audits
            $auditClassGenerator->addMethod(
                'getAuditedEntityClass',
                array(),
                MethodGenerator::FLAG_PUBLIC,
                " return '" .  addslashes($name) . "';");

            // Add revision reference column
            $auditClassGenerator->addProperty($config->getRevisionFieldName(), null, PropertyGenerator::FLAG_PROTECTED);
            $auditClassGenerator->addMethod(
                'get' . $config->getRevisionFieldName(),
                array(),
                MethodGenerator::FLAG_PUBLIC,
                " return \$this->" .  $config->getRevisionFieldName() . ";");

            $auditClassGenerator->setNamespaceName("ZF2EntityAudit\\Entity");
            $auditClassGenerator->setName(str_replace('\\', '_', $name));

            #echo($auditClassGenerator->generate());
            eval($auditClassGenerator->generate());
        }

        // Add revision (manage here rather than separate namespace)
        $auditEntities[] = 'ZF2EntityAudit\\Entity\\Revision';

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
