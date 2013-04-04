<?php

namespace ZF2EntityAudit\Audit;

use ZF2EntityAudit\Metadata\MetadataFactory;
use ZfcUser\Entity\UserInterface;
use ZF2EntityAudit\Audit\Exception;

class Configuration
{
    private $prefix = '';
    private $suffix = '_audit';
    private $revisionFieldName = 'rev';
    private $revisionTypeFieldName = 'revtype';
    private $revisionTableName = 'revisions';
    private $auditedEntityClasses = array();
    private $currentUser = '';
    private $revisionIdFieldType = 'integer';
    private $note = "";

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

    public function setCurrentUser( $user)
    {
        if ($user instanceof UserInterface === false) {
            throw  Exception::NotSupported();
        }
        $this->currentUser = $user;
    }

    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    public function setRevisionIdFieldType($revisionIdFieldType)
    {
        $this->revisionIdFieldType = $revisionIdFieldType;
    }

    public function getRevisionIdFieldType()
    {
        return $this->revisionIdFieldType;
    }

    public function setNote($note = "")
    {
        $this->note = $note ;
    }

    public function getNote()
    {
        return $this->note;
    }
}
