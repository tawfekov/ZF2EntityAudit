<?php

namespace ZF2EntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper
    , Doctrine\ORM\EntityManager
    , Zend\ServiceManager\ServiceLocatorAwareInterface
    , Zend\ServiceManager\ServiceLocatorInterface
    , Zend\View\Model\ViewModel
    , DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter
    , Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator
    , Zend\Paginator\Paginator
    , ZF2EntityAudit\Entity\AbstractAudit
    ;

final class RevisionEntityPaginator extends AbstractHelper implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    public function __invoke($page, $entity) {
        $entityManager = $this->getServiceLocator()->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $auditService = $this->getServiceLocator()->getServiceLocator()->get('auditService');

        if (gettype($entity) != 'string' and in_array(get_class($entity), $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions')->getAuditedEntityClasses())) {
            $auditEntityClass = 'ZF2EntityAudit\\Entity\\' . str_replace('\\', '_', get_class($entity));
            $identifiers = $auditService->getEntityIdentifierValues($entity);
        } elseif ($entity instanceof AbstractAudit) {
            $auditEntityClass = get_class($entity);
            $identifiers = $auditService->getEntityIdentifierValues($entity, true);
        } else {
            $auditEntityClass = 'ZF2EntityAudit\\Entity\\' . str_replace('\\', '_', $entity);
        }

        $search = array('auditEntityClass' => $auditEntityClass);
        if (isset($identifiers)) $search['entityKeys'] = serialize($identifiers);

        $queryBuilder = $entityManager->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity')->createQueryBuilder('rev');
        $queryBuilder->orderBy('rev.id', 'DESC');
        $i = 0;
        foreach ($search as $key => $val) {
            $i ++;
            $queryBuilder->andWhere("rev.$key = ?$i");
            $queryBuilder->setParameter($i, $val);
        }

        $adapter = new DoctrineAdapter(new ORMPaginator($queryBuilder));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}