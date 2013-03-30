<?php

namespace ZF2EntityAudit;
use ZfcUser\Entity\UserInterface as ZfcUserInterface
    ;

class Config
{
    private $prefix;
    private $suffix;
    private $revisionTableName;
    private $auditedEntityClasses;
    private $user;

    public function setDefaults(array $config)
    {
        $this->setTableNamePrefix(isset($config['tableNamePrefix']) ? $config['tableNamePrefix']: null);
        $this->setTableNameSuffix(isset($config['tableNameSuffix']) ? $config['tableNameSuffix']: '_audit');
        $this->setAuditedEntityClasses(isset($config['entities']) ? $config['entities']: array());
        $this->setRevisionTableName(isset($config['revisionTableName']) ? $config['revisionTableName']: 'Revision');
    }

    public function getTableNamePrefix()
    {
        return $this->prefix;
    }

    public function setTableNamePrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getTableNameSuffix()
    {
        return $this->suffix;
    }

    public function setTableNameSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function getRevisionFieldName()
    {
        return 'revision';
    }

    public function getRevisionTypeFieldName()
    {
        return 'revisionType';
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

    public function setUser(ZfcUserInterface $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }
}
