<?php

namespace ZF2EntityAudit\Entity;

class Revision
{
    private $rev;
    private $timestamp;
    private $user;

    public function __construct($rev, $timestamp,  $user , $note = '')
    {
        $this->rev = $rev;
        $this->timestamp = $timestamp;
        $this->user = $user;
        $this->note = $note;
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
}
