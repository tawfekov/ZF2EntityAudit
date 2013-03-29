<?php

namespace ZF2EntityAudit;
use ZfcUser\Entity\UserInterface as ZfcUserInterface
    ;

class AuditConfiguration
{
    private $prefix = '';
    private $suffix = '_audit';
    private $revisionTableName = 'Revision';
    private $auditedEntityClasses = array();
    private $user;

    public function getTablePrefix()
    {
        return $this->prefix;
    }

    public function setTablePrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getTableSuffix()
    {
        return $this->suffix;
    }

    public function setTableSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function getRevisionFieldName()
    {
        return 'rev';
    }

    public function getRevisionTypeFieldName()
    {
        return 'revtype';
    }

    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    public function setRevisionTableName($revisionTableName)
    {
        $this->revisionTableName = $revisionTableName;
        return $this;
    }

    public function getAuditedEntityClasses()
    {
        return $this->auditedEntityClasses;
    }

    public function setAuditedEntityClasses(array $classes)
    {
        $this->auditedEntityClasses = $classes;
        return $this;
    }

    public function createMetadataFactory()
    {
        return new Metadata\MetadataFactory($this->auditedEntityClasses);
    }

    public function setUser(ZfcUserInterface $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getRevisionIdFieldType()
    {
        return 'integer';
    }
}
