<?php

namespace ZF2EntityAudit\Entity;

use ZfcUser\Entity\UserInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class Revision
{
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
        $builder->addField('comment', 'text');
        $builder->addField('timestamp', 'datetime');

        $builder->addManyToOne('user', \ZF2EntityAudit\Module::getZfcUserEntity());
    }

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