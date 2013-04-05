<?php

namespace SoliantEntityAudit;

use Zend\Mvc\MvcEvent
    , SoliantEntityAudit\Options\ModuleOptions
    , SoliantEntityAudit\Service\AuditService
    , SoliantEntityAudit\Loader\AuditAutoloader
    , SoliantEntityAudit\EventListener\LogRevision
    , SoliantEntityAudit\View\Helper\DateTimeFormatter
    , SoliantEntityAudit\View\Helper\EntityValues
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

    public function onBootstrap(MvcEvent $e)
    {
        self::setServiceManager($e->getApplication()->getServiceManager());

        $auditAutoloader = new AuditAutoloader();
        $auditAutoloader->setServiceManager($e->getApplication()->getServiceManager());
        $auditAutoloader->registerNamespace('SoliantEntityAudit\\Entity', __DIR__);
        $auditAutoloader->register();

        // Subscribe log revision event listener
        $e->getApplication()->getServiceManager()->get('doctrine.eventmanager.orm_default')
            ->addEventSubscriber(new LogRevision($e->getApplication()->getServiceManager()));
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

                'auditService' => function($sm) {
                    return new AuditService();
                }
            ),
        );
    }

    public function getViewHelperConfig()
    {
         return array(
            'factories' => array(
                'auditDateTimeFormatter' => function($sm) {
                    $Servicelocator = $sm->getServiceLocator();
                    $config = $Servicelocator->get("Config");
                    $format = $config['audit']['datetime.format'];
                    $formatter = new DateTimeFormatter();
                    return $formatter->setDateTimeFormat($format);
                },

                'auditService' => function($sm) {
                    return new AuditService();
                }
            )
        );
    }
}
