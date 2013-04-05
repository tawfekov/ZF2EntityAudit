<?php

namespace SoliantEntityAudit\Loader;

use Zend\Loader\StandardAutoloader
    , Zend\ServiceManager\ServiceManager
    , Zend\Code\Reflection\ClassReflection
    , Zend\Code\Generator\ClassGenerator
    , Zend\Code\Generator\MethodGenerator
    , Zend\Code\Generator\PropertyGenerator
    ;

class AuditAutoloader extends StandardAutoloader
{
    private $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Dynamically scope an audit class
     *
     * @param  string $className
     * @return false|string
     */
    public function loadClass($className, $type)
    {
        $config = $this->getServiceManager()->get("auditModuleOptions");
        $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');

        $auditClass = new ClassGenerator();

        // Add revision reference getter and setter
        $auditClass->addProperty($config->getRevisionFieldName(), null, PropertyGenerator::FLAG_PROTECTED);
        $auditClass->addMethod(
            'get' . $config->getRevisionFieldName(),
            array(),
            MethodGenerator::FLAG_PUBLIC,
            " return \$this->" .  $config->getRevisionFieldName() . ";");

        $auditClass->addMethod(
            'set' . $config->getRevisionFieldName(),
            array('value'),
            MethodGenerator::FLAG_PUBLIC,
            " \$this->" .  $config->getRevisionFieldName() . " = \$value;\nreturn \$this;
            ");
        $identifiers = array($config->getRevisionFieldName());

        //  Build a discovered many to many join class
        $joinClasses = $config->getJoinClasses();
        if (in_array($className, array_keys($joinClasses))) {
            $auditClassName = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $className);
            $auditClass->setNamespaceName("SoliantEntityAudit\\Entity");
            $auditClass->setName($className);
            $auditClass->setExtendedClass('AbstractAudit');

            foreach ($joinClasses[$className]['joinColumns'] as $joinColumn) {
                $auditClass->addProperty($joinColumn['name'], null, PropertyGenerator::FLAG_PROTECTED);
            }

            foreach ($joinClasses[$className]['inverseJoinColumns'] as $joinColumn) {
                $auditClass->addProperty($joinColumn['name'], null, PropertyGenerator::FLAG_PROTECTED);
            }

            // Add function to return the entity class this entity audits
            $auditClass->addMethod(
                'getAuditedEntityClass',
                array(),
                MethodGenerator::FLAG_PUBLIC,
                " return '" .  addslashes($auditClassName) . "';"
            );

#            print_r($auditClass->generate());die();
            eval($auditClass->generate());
            return;
        }


        // Verify this autoloader is used for target class
        #FIXME:  why is this sent work outside the set namespace?
        foreach($config->getAuditedEntityClasses() as $targetClass => $targetClassOptions) {
             $auditClassName = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $targetClass);
             if ($auditClassName == $className) {
                 $currentClass = $targetClass;
             }
             $autoloadClasses[] = $auditClassName;
        }
        if (!in_array($className, $autoloadClasses)) return;

        // Get fields from target entity
        $metadataFactory = $entityManager->getMetadataFactory();
        $auditedClassMetadata = $metadataFactory->getMetadataFor($currentClass);
        $fields = $auditedClassMetadata->getFieldNames();

        // Generate audit entity
        foreach ($fields as $field) {
            $auditClass->addProperty($field, null, PropertyGenerator::FLAG_PROTECTED);
        }

        // add join fields
        foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
            if (!$mapping['isOwningSide']) continue;

            # FIXME: add support for many to many join
            if (isset($mapping['joinTable'])) {
                continue;
            }

            if (isset($mapping['joinTableColumns'])) {
                foreach ($mapping['joinTableColumns'] as $field) {
                    $auditClass->addProperty($mapping['fieldName'], null, PropertyGenerator::FLAG_PROTECTED);
                    $fields[] = $mapping['fieldName'];
                }
            } elseif (isset($mapping['joinColumnFieldNames'])) {
                foreach ($mapping['joinColumnFieldNames'] as $field) {
                    $auditClass->addProperty($mapping['fieldName'], null, PropertyGenerator::FLAG_PROTECTED);
                    $fields[] = $mapping['fieldName'];
                }
            }
        }

        // Add exchange array method
        $setters = array();
        foreach ($fields as $fieldName) {
            $setters[] = '$this->' . $fieldName . ' = (isset($data["' . $fieldName . '"])) ? $data["' . $fieldName . '"]: null;';
        }

        $auditClass->addMethod(
            'exchangeArray',
            array('data'),
            MethodGenerator::FLAG_PUBLIC,
            implode("\n", $setters)
        );

        // Add function to return the entity class this entity audits
        $auditClass->addMethod(
            'getAuditedEntityClass',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            " return '" .  addslashes($currentClass) . "';"
        );

        $auditClass->setNamespaceName("SoliantEntityAudit\\Entity");
        $auditClass->setName(str_replace('\\', '_', $currentClass));
        $auditClass->setExtendedClass('AbstractAudit');

#            echo '<pre>';
#            echo($auditClass->generate());

        eval($auditClass->generate());

        return true;
    }

}