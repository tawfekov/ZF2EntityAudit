<?php
namespace ZF2EntityAudit\Paginator;


use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\Null as NullAdapter ;
use ZF2EntityAudit\Audit\Reader ; 

class Dbal extends Paginator 
{
    protected  $reader  ; 
    public function __construct( Reader $reader ) {
        $this->reader  = $reader ;
        parent::__construct(new NullAdapter());
    }
    
    public function getItems($offset, $itemCountPerPage)
    {
        return $this->reader->findRevisionHistory($itemCountPerPage, $offset);
    }
    
    public function count()
    {
        return  $this->reader->countRevisions();
    }
    
}