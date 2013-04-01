<?php

namespace ZF2EntityAudit\Entity;

use ZfcUser\Entity\UserInterface;

class Revision
{
    private $rev;
    private $timestamp;
    private $user;

    function __construct($rev, $timestamp, UserInterface $user)
    {
        $this->rev = $rev;
        $this->timestamp = $timestamp;
        $this->user = $user;
    }

    public function getRev()
    {
        return $this->rev;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getUser()
    {
        return $this->user;
    }
}