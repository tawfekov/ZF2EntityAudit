<?php

namespace ZF2EntityAudit\Options;
use ZfcUser\Entity\UserInterface as ZfcUserInterface
    ;

class ModuleOptions
{
    private $prefix;
    private $suffix;
    private $revisionTableName;
    private $revisionEntityTableName;
    private $auditedEntityClasses;
    private $joinClasses;
    private $user;

    public function setDefaults(array $config)
    {
        $this->setPaginatorLimit(isset($config['tableNamePrefix']) ? $config['paginator.limit']: 20);
        $this->setTableNamePrefix(isset($config['tableNamePrefix']) ? $config['tableNamePrefix']: null);
        $this->setTableNameSuffix(isset($config['tableNameSuffix']) ? $config['tableNameSuffix']: '_audit');
        $this->setAuditedEntityClasses(isset($config['entities']) ? $config['entities']: array());
        $this->setRevisionTableName(isset($config['revisionTableName']) ? $config['revisionTableName']: 'Revision');
        $this->setRevisionEntityTableName(isset($config['revisionEntityTableName']) ? $config['revisionEntityTableName']: 'RevisionEntity');
    }

    public function addJoinClass($className, $mapping)
    {
        $this->joinClasses[$className] = $mapping;
        return $this;
    }

    public function getJoinClasses()
    {
        if (!$this->joinClasses) $this->joinClasses = array();
        return $this->joinClasses;
    }

    public function getPaginatorLimit()
    {
        return $this->paginatorLimit;
    }

    public function setPaginatorLimit($rows)
    {
        $this->paginatorLimit = $rows;
        return $this;
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

    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    public function setRevisionTableName($revisionTableName)
    {
        $this->revisionTableName = $revisionTableName;
        return $this;
    }

    public function getRevisionEntityTableName()
    {
        return $this->revisionEntityTableName;
    }

    public function setRevisionEntityTableName($value)
    {
        $this->revisionEntityTableName = $value;
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
