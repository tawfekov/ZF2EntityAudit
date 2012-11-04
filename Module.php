<?php

namespace ZF2EntityAudit;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface, ServiceProviderInterface {

    public function getAutoloaderConfig() {
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

    public function getConfig() {
        return include'config/module.config.php';
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                "auditManager" => function ($sm) {
                    $config = $sm->get("Config");
                    $evm = $sm->get("doctrine.eventmanager.orm_default");

                    $auditconfig = new AuditConfiguration();
                    $auditconfig->setAuditedEntityClasses($config["audited_entities"]);

                    $auditManager = new AuditManager($auditconfig);
                    $auditManager->registerEvents($evm);
                    return $auditManager;
                },
                "auditReader" => function($sm) {
                    $auditManager = $sm->get("auditManager");
                    $entityManager = $sm->get("default");
                    return $auditManager->createAuditReader($entityManager);
                }
            ),
        );
    }

}
