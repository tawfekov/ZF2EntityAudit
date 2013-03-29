<?php

namespace ZF2EntityAudit;

use Zend\Mvc\MvcEvent
    , SimpleThings\EntityAudit\AuditConfiguration
    , SimpleThings\EntityAudit\AuditManager
    , SimpleThings\EntityAudit\EventListener\CreateSchemaListener
    , SimpleThings\EntityAudit\EventListener\LogRevisionsListener
    , ZF2EntityAudit\View\Helper\DateTimeFormatter
    , Zend\ServiceManager\ServiceManager
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
                    'SimpleThings' => __DIR__ . '/src/SimpleThings',
                ),
            ),
        );
    }

    public function init($moduleManager) {
#        print_r(get_class_methods($moduleManager));die();
    }

    public function onBootstrap(MvcEvent $e)
    {
        // Initialize the audit manager by creating an instance of it
        $sm = $e->getApplication()->getServiceManager();
        $this->setServiceManager($sm);

        $auditManager = $this->getServiceManager()->get('auditManager');
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
                'auditConfig' => function($serviceManager){
                    $config = $serviceManager->get('Application')->getConfig();
                    $auditconfig = new AuditConfiguration();
                    $auditconfig->setAuditedEntityClasses($config['audit']['entities']);
                    return $auditconfig;
                },

                'auditManager' => function ($serviceManager) {
                    $eventManager = $serviceManager->get("doctrine.eventmanager.orm_default");
                    $config = $serviceManager->get('Application')->getConfig();
                    $auditconfig = $serviceManager->get("auditConfig");

                    $auth = $serviceManager->get('zfcuser_auth_service');
                    if ($auth->hasIdentity()) {
                        $auditconfig->setUser($auth->getIdentity());
                    }

                    $auditManager = new AuditManager($auditconfig);
                    $eventManager->addEventSubscriber(new CreateSchemaListener($auditManager));
                    $eventManager->addEventSubscriber(new LogRevisionsListener($auditManager));
                    return $auditManager;
                },

                'auditReader' => function($sm) {
                    $auditManager = $sm->get('auditManager');
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
                'DateTimeFormatter' => function($sm) {
                    $Servicelocator = $sm->getServiceLocator();
                    $config = $Servicelocator->get("Config");
                    $format = $config['zf2-entity-audit']['ui']['datetime.format'];
                    $formatter = new DateTimeFormatter();
                    return $formatter->setDateTimeFormat($format);
                }
            )
        );
    }
}
