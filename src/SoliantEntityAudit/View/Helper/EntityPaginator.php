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

final class EntityPaginator extends AbstractHelper implements ServiceLocatorAwareInterface
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

    public function __invoke($page, $entityClass) {
        $entityManager = $this->getServiceLocator()->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $auditService = $this->getServiceLocator()->getServiceLocator()->get('auditService');
        $auditModuleOptions = $this->getServiceLocator()->getServiceLocator()->get('auditModuleOptions');

        if (in_array($entityClass, array_keys(\SoliantEntityAudit\Module::getServiceManager()->get('auditModuleOptions')->getAuditedEntityClasses()))) {
            $auditEntityClass = 'SoliantEntityAudit\\Entity\\' . str_replace('\\', '_', $entityClass);
        } else {
            $auditEntityClass = $entityClass;
        }

        $repository = $entityManager->getRepository('SoliantEntityAudit\\Entity\\RevisionEntity');

        $qb = $repository->createQueryBuilder('revisionEntity');
        $qb->orderBy('revisionEntity.id', 'DESC');

        $qb->andWhere('revisionEntity.auditEntityClass = ?1')
            ->setParameter(1, $auditEntityClass);

        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($auditModuleOptions->getPaginatorLimit());
        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }
}