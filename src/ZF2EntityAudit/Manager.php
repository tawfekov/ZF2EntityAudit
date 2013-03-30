<?php

namespace ZF2EntityAudit;

use Doctrine\Common\EventManager
    , Doctrine\ORM\EntityManager
    , ZF2EntityAudit\EventListener\LogRevisionsListener
    ;

/**
 * Audit Manager grants access to metadata and configuration
 * and has a factory method for audit queries.
 */
class Manager
{
    private $config;

    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }

    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function createAuditReader(EntityManager $entityManager)
    {
        return new AuditReader($entityManager, $this->getConfig());
    }

    public function registerEvents(EventManager $eventManager)
    {
        $eventManager->addEventSubscriber(new LogRevisionsListener($this));
    }
}