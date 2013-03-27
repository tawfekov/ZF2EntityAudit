<?php

namespace ZF2EntityAudit\Audit;

use ZF2EntityAudit\Metadata\MetadataFactory;

class Configuration
{
    private $prefix = '';
    private $suffix = '_audit';
    private $revisionFieldName = 'rev';
    private $revisionTypeFieldName = 'revtype';
    private $revisionTableName = 'revisions';
    private $auditedEntityClasses = array();
    private $currentUsername = '';
    private $revisionIdFieldType = 'integer';

    public function getTablePrefix()
    {
        return $this->prefix;
    }

    public function setTablePrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getTableSuffix()
    {
        return $this->suffix;
    }

    public function setTableSuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    public function getRevisionFieldName()
    {
        return $this->revisionFieldName;
    }

    public function setRevisionFieldName($revisionFieldName)
    {
        $this->revisionFieldName = $revisionFieldName;
    }

    public function getRevisionTypeFieldName()
    {
        return $this->revisionTypeFieldName;
    }

    public function setRevisionTypeFieldName($revisionTypeFieldName)
    {
        $this->revisionTypeFieldName = $revisionTypeFieldName;
    }

    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    public function setRevisionTableName($revisionTableName)
    {
        $this->revisionTableName = $revisionTableName;
    }

    public function setAuditedEntityClasses(array $classes)
    {
        $this->auditedEntityClasses = $classes;
    }

    public function createMetadataFactory()
    {
        return new MetadataFactory($this->auditedEntityClasses);
    }
    
    public function setCurrentUsername($username)
    {
        $this->currentUsername = $username;
    }
    
    public function getCurrentUsername()
    {
        return $this->currentUsername;
    }

    public function setRevisionIdFieldType($revisionIdFieldType)
    {
        $this->revisionIdFieldType = $revisionIdFieldType;
    }

    public function getRevisionIdFieldType()
    {
        return $this->revisionIdFieldType;
    }
}
