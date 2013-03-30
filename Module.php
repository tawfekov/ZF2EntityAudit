<?php

namespace ZF2EntityAudit;

use Zend\Mvc\MvcEvent
    , ZF2EntityAudit\AuditConfiguration
    , ZF2EntityAudit\AuditManager
    , ZF2EntityAudit\EventListener\CreateSchemaListener
    , ZF2EntityAudit\EventListener\LogRevisionsListener
    , ZF2EntityAudit\View\Helper\AuditDateTimeFormatter
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
                    $auditConfig = new \ZF2EntityAudit\Config();
                    $auditConfig->setDefaults($config['audit']);
                    return $auditConfig;
                },

                'auditManager' => function ($serviceManager) {
                    $eventManager = $serviceManager->get("doctrine.eventmanager.orm_default");
                    $auditConfig = $serviceManager->get("auditConfig");

                    $auth = $serviceManager->get('zfcuser_auth_service');
                    if ($auth->hasIdentity()) {
                        $auditconfig->setUser($auth->getIdentity());
                    }

                    $auditManager = new AuditManager($auditConfig);
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
