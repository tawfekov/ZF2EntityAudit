<?php

namespace ZF2EntityAudit\Audit;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use ZF2EntityAudit\EventListener\CreateSchemaListener;
use ZF2EntityAudit\EventListener\LogRevisionsListener;

/**
 * Audit Manager grants access to metadata and configuration
 * and has a factory method for audit queries.
 */
class Manager
{
    private $config;

    private $metadataFactory;

    /**
     * @param AuditConfiguration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->metadataFactory = $config->createMetadataFactory();
    }

    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function createAuditReader(EntityManager $em)
    {
        return new Reader($em, $this->config, $this->metadataFactory);
    }

    public function registerEvents(EventManager $evm)
    {
        $evm->addEventSubscriber(new CreateSchemaListener($this));
        $evm->addEventSubscriber(new LogRevisionsListener($this));
    }
}
