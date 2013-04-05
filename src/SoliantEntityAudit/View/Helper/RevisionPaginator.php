<?php

namespace SoliantEntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper
    , Doctrine\ORM\EntityManager
    , Zend\ServiceManager\ServiceLocatorAwareInterface
    , Zend\ServiceManager\ServiceLocatorInterface
    , Zend\View\Model\ViewModel
    , DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter
    , Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator
    , Zend\Paginator\Paginator
    , SoliantEntityAudit\Entity\AbstractAudit
    ;

final class RevisionPaginator extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($page, $filter = array())
    {
        $entityManager = $this->getServiceLocator()->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $auditService = $this->getServiceLocator()->getServiceLocator()->get('auditService');
        $auditModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions');

        $repository = $entityManager->getRepository('SoliantEntityAudit\\Entity\\Revision');

        $qb = $repository->createQueryBuilder('revision');
        $qb->orderBy('revision.id', 'DESC');

        $i = 0;
        foreach($filter as $field => $value) {
            if (!is_null($value)) {
                $qb->andWhere("revision.$field = ?$i");
                $qb->setParameter($i, $value);
            } else {
                $qb->andWhere("revision.$field is NULL");
            }
        }

        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($auditModuleOptions->getPaginatorLimit());

        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}
