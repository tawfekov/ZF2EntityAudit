<?php

namespace ZF2EntityAudit;
use Zend\Mvc\MvcEvent
    , SimpleThings\EntityAudit\AuditConfiguration
    , SimpleThings\EntityAudit\AuditManager
    , SimpleThings\EntityAudit\EventListener\CreateSchemaListener
    , SimpleThings\EntityAudit\EventListener\LogRevisionsListener
    ;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        // Initialize the audit manager by creating an instance of it
        $sm = $e->getApplication()->getServiceManager();
        $auditManager = $sm->get('auditManager');
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'auditConfig' => function($sm){
                    $config = $sm->get('Config');
                    $auditconfig = new AuditConfiguration();
                    $auditconfig->setAuditedEntityClasses($config['audited_entities']);
                    return $auditconfig;
                },

                'auditManager' => function ($sm) {
                    $config = $sm->get('Config');
                    $evm = $sm->get('doctrine.eventmanager.orm_default');

                    $auditconfig = $sm->get('auditConfig');

                    if ($config['zfcuser.integration'] === true) {
                        $auth = $sm->get('zfcuser_auth_service');
                        if ($auth->hasIdentity()) {
                            $identity = $auth->getIdentity();
                            $auditconfig->setCurrentUsername($identity->getEmail());
                        } else {
                            $auditconfig->setCurrentUsername('Anonymous');
                        }
                    } else {
                        $auditconfig->setCurrentUsername('Anonymous');
                    }
                    $auditManager = new AuditManager($auditconfig);
                    $evm->addEventSubscriber(new CreateSchemaListener($auditManager));
                    $evm->addEventSubscriber(new LogRevisionsListener($auditManager));
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
}