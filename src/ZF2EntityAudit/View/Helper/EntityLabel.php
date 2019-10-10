<?php

namespace ZF2EntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Doctrine\ORM\EntityManager;

class EntityLabel extends AbstractHelper
{
    public function __invoke($entity, $className, $id, $prefixToIgnore = null)
    {
        // By default, show the class name and ID
        $className = str_replace($prefixToIgnore, '', $className);
        $label = "$className identifier $id";

        // But if the entity has __toString(), show that instead
        if ($entity && method_exists($entity, '__toString')) {
            $label = $entity->__toString();
        }

        return $label;
    }
}