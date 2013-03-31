<?php

namespace ZF2EntityAudit;

use Zend\Mvc\MvcEvent
    , ZF2EntityAudit\Options\ModuleOptions
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
        $entityManager = $e->getApplication()->getServiceManager()->get('doctrine.entitymanager.orm_default');

        // Generate audit entities
        $auditEntities = array();
        foreach ($config->getAuditedEntityClasses() as $name) {

            // Get fields from target entity
            $metadataFactory = $entityManager->getMetadataFactory();
            $auditedClassMetadata = $metadataFactory->getMetadataFor($name);
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
                " return '" .  addslashes($name) . "';"
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
            $auditClass->setName(str_replace('\\', '_', $name));

#            echo '<pre>';
#            echo($auditClass->generate());
            eval($auditClass->generate());
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
