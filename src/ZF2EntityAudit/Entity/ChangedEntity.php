<?php

namespace ZF2EntityAudit\Entity;

class ChangedEntity
{
    private $className;
    private $id;
    private $revType;
    private $entity;
    
    public function __construct($className, array $id, $revType, $entity)
    {
        $this->className = $className;
        $this->id = $id;
        $this->revType = $revType;
        $this->entity = $entity;
    }
    
    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     *
     * @return array
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRevisionType()
    {
        return $this->revType;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}