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
        $cmf = $entityManager->getMetadataFactory();

        // Get the entity this entity audits
        $metadataClassName = $metadata->getName();
        $metadataClass = new $metadataClassName();

        if (!$cmf->hasMetadataFor($metadataClass->getAuditedEntityClass()))
            throw new \Exception('Metadata is not loaded for '
                . $metadataClass->getAuditedEntityClass()
                . '  Is the auditing module last to load?  It should be...');

        $auditedClassMetadata = $cmf->getMetadataFor($metadataClass->getAuditedEntityClass());

        $builder = new ClassMetadataBuilder($metadata);
        $builder->addManyToOne($config->getRevisionFieldName(), 'ZF2EntityAudit\Entity\Revision');
        $identifiers = array($config->getRevisionFieldName());

        foreach ($auditedClassMetadata->getFieldNames() as $fieldName) {
            $builder->addField($fieldName, $auditedClassMetadata->getTypeOfField($fieldName));
            if ($auditedClassMetadata->isIdentifier($fieldName)) $identifiers[] = $fieldName;
        }

        $metadata->setTableName($config->getTablePrefix() . $auditedClassMetadata->getTableName() . $config->getTableSuffix());
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
            $auditEntities[] = "ZF2EntityAudit\\GeneratedEntity\\" . str_replace('\\', '_', $name);

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
            $auditClassGenerator->addProperty($config->getRevisionFieldName(), null, $flags = PropertyGenerator::FLAG_PROTECTED);
            $auditClassGenerator->addMethod(
                'get' . $config->getRevisionFieldName(),
                array(),
                MethodGenerator::FLAG_PUBLIC,
                " return $" .  $config->getRevisionFieldName() . ";");

            $auditClassGenerator->setNamespaceName("ZF2EntityAudit\\GeneratedEntity");
            $auditClassGenerator->setName(str_replace('\\', '_', $name));

#            echo($auditClassGenerator->generate());
            eval($auditClassGenerator->generate());
        }

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
