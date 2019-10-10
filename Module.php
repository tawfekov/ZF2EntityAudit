<?php

namespace ZF2EntityAudit;
use Zend\Mvc\MvcEvent;

use ZF2EntityAudit\Audit\Configuration;
use ZF2EntityAudit\Audit\Manager ;
use ZF2EntityAudit\EventListener\CreateSchemaListener;
use ZF2EntityAudit\EventListener\LogRevisionsListener;
use ZF2EntityAudit\View\Helper\DateTimeFormatter;
use ZF2EntityAudit\View\Helper\EntityLabel;
use ZF2EntityAudit\View\Helper\User as UserBlock;
use ZF2EntityAudit\View\Helper\Dump ;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

class Module implements ConsoleUsageProviderInterface
{
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
        // Initialize the audit manager by creating an instance of it
        $sm = $e->getApplication()->getServiceManager();
        $sm->get('auditManager');
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
                    $auditconfig = new Configuration();
                    $auditconfig->setAuditedEntityClasses($config['zf2-entity-audit']['entities']);
                    $auditconfig->setZfcUserEntityClass($config['zf2-entity-audit']['zfcuser.entity_class']);

                    if (!empty($config['zf2-entity-audit']['note'])) {
                        $auditconfig->setNote($config['zf2-entity-audit']['note']);
                    }

                    if (!empty($config['zf2-entity-audit']['noteFormField'])) {
                        $auditconfig->setNoteFormField($config['zf2-entity-audit']['noteFormField']);
                    }

                    return $auditconfig;
                },

                'auditManager' => function ($sm) {
                    $config = $sm->get('Config');
                    $evm = $sm->get('doctrine.eventmanager.orm_default');

                    $auditconfig = $sm->get('auditConfig');

                    if ($config['zf2-entity-audit']['zfcuser.integration'] === true) {
                        $auth = $sm->get('zfcuser_auth_service');
                        if ($auth->hasIdentity()) {
                            $identity = $auth->getIdentity();
                            $auditconfig->setCurrentUser($identity);
                        }
                        /* need to handle the unauthenticated user action case, do it your own , 99% i will drop support for unauthenticated user auditing  */ 
                    }
                    $auditManager = new Manager($auditconfig);
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
                },
                'UserBlock' => function($sm){
                    $Servicelocator = $sm->getServiceLocator();
                    $em             = $Servicelocator->get("doctrine.entitymanager.orm_default");
                    $config = $Servicelocator->get("Config");
                    $helper         = new UserBlock();
                    $helper->setEntityManager($em);
                    $helper->setZfcUserEntityClass($config['zf2-entity-audit']['zfcuser.entity_class']);
                    return $helper ;
                },
                'EntityLabel' => function($sm){
                    $helper         = new EntityLabel();
                    return $helper ;
                },
                'Dump' => function(){
                     $helper = new Dump();
                     return $helper;
                }
            )
        );
    }

    public function getConsoleUsage(Console $console)
    {
         return array(
             "update" => "update the database from 0.1 to be  0.2 compatibale ",
             "initialize" => "create initial revisions for all audited entities that do not have any revision"
          );
    }
}
