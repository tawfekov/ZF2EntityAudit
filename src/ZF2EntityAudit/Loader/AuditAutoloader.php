<?php

namespace ZF2EntityAudit\Loader;

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
     * @param  string $class
     * @return false|string
     */
    public function loadClass($class, $type)
    {
        $config = $this->getServiceManager()->get("auditModuleOptions");
        $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');

        // Verify this autoloader is used for target class
        foreach($config->getAuditedEntityClasses() as $targetClass) {
             $auditClass = 'ZF2EntityAudit\\Entity\\' . str_replace('\\', '_', $targetClass);
             if ($auditClass == $class) {
                 $currentClass = $targetClass;
             }
             $autoloadClasses[] = $auditClass;
        }
        if (!in_array($class, $autoloadClasses)) return;

        // Get fields from target entity
        $metadataFactory = $entityManager->getMetadataFactory();
        $auditedClassMetadata = $metadataFactory->getMetadataFor($currentClass);
        $fields = $auditedClassMetadata->getFieldNames();

        // Generate audit entity
        $auditClass = new ClassGenerator();
        foreach ($fields as $field) {
            $auditClass->addProperty($field, null, PropertyGenerator::FLAG_PROTECTED);
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

        // Add function to return the entity this entity audits
        $auditClass->addMethod(
            'getAuditedEntityClass',
            array(),
            MethodGenerator::FLAG_PUBLIC,
            " return '" .  addslashes($currentClass) . "';"
        );

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

        $auditClass->setNamespaceName("ZF2EntityAudit\\Entity");
        $auditClass->setName(str_replace('\\', '_', $currentClass));

#            echo '<pre>';
#            echo($auditClass->generate());
        eval($auditClass->generate());

        return true;
    }

}