<?php
namespace ZF2EntityAudit\View\Helper ;

use Zend\View\Helper\AbstractHelper;
use Doctrine\Common\Util\Debug ; 

class Dump extends AbstractHelper
{

    public function __invoke($object)
    {
        return Debug::dump($object);
    }
}
