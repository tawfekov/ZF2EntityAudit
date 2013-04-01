<?php

namespace ZF2EntityAudit\Entity;

use ZfcUser\Entity\UserInterface
    , Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    ;

class Revision
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    protected $comment;

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($value)
    {
        $this->comment = $value;
        return $this;
    }

    protected $revisionType;

    public function getRevisionType()
    {
        return $this->revisionType;
    }

    public function setRevisionType($value)
    {
        $this->revisionType = $value;
        return $this;
    }

    protected $timestamp;

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $value)
    {
        $this->timestamp = $value;
        return $this;
    }

    protected $user;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(UserInterface $value)
    {
        $this->user = $value;
        return $this;
    }

    public function __construct()
    {
        $this->setTimestamp(new \DateTime());
    }
}