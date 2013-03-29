<?php
namespace ZF2EntityAudit\View\Helper ; 


use Zend\Http\Request;
use Zend\View\Helper\AbstractHelper;


class AuditDateTimeFormatter extends AbstractHelper 
{
    
    protected $format;

    public function setDateTimeFormat($format = "r")
    {
        $this->format = $format ;
        return $this ;
    }

    public function __invoke(\DateTime $datetime) 
    {
        return $datetime->format($this->format);
    }
}
