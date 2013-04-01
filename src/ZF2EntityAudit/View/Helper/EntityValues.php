<?php

namespace ZF2EntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper
    , Doctrine\ORM\EntityManager
    , Zend\ServiceManager\ServiceLocatorAwareInterface
    ;

final class EntityValues extends AbstractHelper implements ServiceLocatorAwareInterface {
    use \Db\Model\Component\ServiceLocator;

    protected function __invoke($entity) {
        $em = $this->getServiceLocator()
            ->getServiceLocator()
            ->get('doctrine.entitymanager.orm_default');

        $metadata = $em->getClassMetadata(get_class($entity));
        $fields = $metadata->getFieldNames();

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        krsort($data);

        return $return;
    }
}