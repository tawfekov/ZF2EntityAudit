<?php

namespace ZF2EntityAudit\Entity;

class Revision
{
    private $rev;
    private $timestamp;
    private $user;
    private $ipaddress;

    public function __construct($rev, $timestamp,  $user , $note = '' , $ipaddress = '')
    {
        $this->rev = $rev;
        $this->timestamp = $timestamp;
        $this->user = $user;
        $this->note = $note;
        $this->ipaddress = $ipaddress;
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

    public function getNote()
    {
        return $this->note;
    }

    public function setIpaddress()
    {
        return $this->ipaddress;
    }

    public function getIpAddress()
    {
        return $this->ipaddress;
    }

}
