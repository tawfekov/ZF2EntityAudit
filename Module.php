<?php

namespace ZF2EntityAudit;

use Zend\Mvc\MvcEvent
    , Zf2EntityAudit\Options\ModuleOptions
    , ZF2EntityAudit\EventListener\LogRevision
    , ZF2EntityAudit\View\Helper\AuditDateTimeFormatter
    , Zend\ServiceManager\ServiceManager
    , Zend\Code\Reflection\ClassReflection
    , Zend\Code\Generator\ClassGenerator
    , Zend\Code\Generator\MethodGenerator
    , Zend\Code\Generator\PropertyGenerator
    ;

class Module
{
    private static $serviceManager;

    public static function getZfcUserEntity()
    {
        return self::getServiceManager()->get('zfcuser_module_options')->getUserEntityClass();
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        self::setServiceManager($e->getApplication()->getServiceManager());

        $config = $e->getApplication()->getServiceManager()->get("auditModuleOptions");

        // Generate audit entities
        $auditEntities = array();
        foreach ($config->getAuditedEntityClasses() as $name) {
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

            // Add function to populate all properties
            $setters = array();
            foreach ($auditClassGenerator->getProperties() as $x) {
                $setters[] = '$this->' . $x->getName() . ' = (isset($properties["' . $x->getName() . '"])) ? $properties["' . $x->getName() . '"]: null;';
            }

            // Get parent class properties
            if ($extendedClass = $auditClassGenerator->getExtendedClass()) {
                while ($extendedClass) {
                    $extendedClassReflection = ClassGenerator::fromReflection(new ClassReflection($extendedClass));
                    foreach ($extendedClassReflection->getProperties() as $x) {
                        $setters[] = '$this->' . $x->getName() . ' = (isset($properties["' . $x->getName() . '"])) ? $properties["' . $x->getName() . '"]: null;';
                    }

                    $extendedClass = $extendedClassReflection->getExtendedClass();
                }
            }

            if ($auditClassGenerator->getExtendedClass()) {
                $auditClassGenerator->addUse($auditClassGenerator->getExtendedClass(), 'auditExtendsClass');
                $auditClassGenerator->setExtendedClass('auditExtendsClass');
            }

            $auditClassGenerator->addMethod(
                'setAuditProperties',
                array('properties'),
                MethodGenerator::FLAG_PUBLIC,
                implode("\n", $setters));

            // Add revision reference getter and setter
            $auditClassGenerator->addProperty($config->getRevisionFieldName(), null, PropertyGenerator::FLAG_PROTECTED);
            $auditClassGenerator->addMethod(
                'get' . $config->getRevisionFieldName(),
                array(),
                MethodGenerator::FLAG_PUBLIC,
                " return \$this->" .  $config->getRevisionFieldName() . ";");

            $auditClassGenerator->addMethod(
                'set' . $config->getRevisionFieldName(),
                array('value'),
                MethodGenerator::FLAG_PUBLIC,
                " \$this->" .  $config->getRevisionFieldName() . " = \$value;\nreturn \$this;
                ");

            $auditClassGenerator->setNamespaceName("ZF2EntityAudit\\Entity");
            $auditClassGenerator->setName(str_replace('\\', '_', $name));

#            echo '<pre>';
#            echo($auditClassGenerator->generate());
#            die();
            eval($auditClassGenerator->generate());
        }

        // Subscribe log revision event listener
        $e->getApplication()->getServiceManager()->get('doctrine.eventmanager.orm_default')
            ->addEventSubscriber(
                new LogRevision(
                    $e->getApplication()->getServiceManager()->get("doctrine.entitymanager.orm_default"),
                    $config
                )
            );
    }

    public static function setServiceManager(ServiceManager $serviceManager)
    {
        self::$serviceManager = $serviceManager;
    }

    public static function getServiceManager()
    {
        return self::$serviceManager;
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'auditModuleOptions' => function($serviceManager){
                    $config = $serviceManager->get('Application')->getConfig();
                    $auditConfig = new ModuleOptions();
                    $auditConfig->setDefaults($config['audit']);

                    $auth = $serviceManager->get('zfcuser_auth_service');
                    if ($auth->hasIdentity()) {
                        $auditConfig->setUser($auth->getIdentity());
                    }

                    return $auditConfig;
                },

                'auditReader' => function($sm) {
                    $entityManager = $sm->get('doctrine.entitymanager.orm_default');
                    return $auditManager->createAuditReader($entityManager);
                },
            ),
        );
    }

    public function getViewHelperConfig()
    {
         return array(
            'factories' => array(
                'AuditDateTimeFormatter' => function($sm) {
                    $Servicelocator = $sm->getServiceLocator();
                    $config = $Servicelocator->get("Config");
                    $format = $config['audit']['datetime.format'];
                    $formatter = new AuditDateTimeFormatter();
                    return $formatter->setDateTimeFormat($format);
                }
            )
        );
    }
}
