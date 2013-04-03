<?php

namespace ZF2EntityAudit\Controller;

use Zend\Mvc\Controller\AbstractActionController
 , DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter
 , Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator
 , Zend\Paginator\Paginator
 ;

class IndexController extends AbstractActionController
{
    /**
     * Renders a paginated list of revisions.
     *
     * @param int $page
     */
    public function indexAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $repository = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\Revision');

        $qb = $repository->createQueryBuilder('revision');
        $qb->orderBy('revision.id', 'DESC');

        $adapter = new DoctrineAdapter(new ORMPaginator($qb));
        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(20);

        if($page) $paginator->setCurrentPageNumber($page);

        return array(
            'paginator' => $paginator,
        );
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @param integer $rev
     *
     */
    public function revisionAction()
    {
        $revisionId = (int)$this->getEvent()->getRouteMatch()->getParam('revisionId');

        $revision = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\Revision')
            ->find($revisionId);

        if (!$revision)
            return $this->plugin('redirect')->toRoute('audit');

        return array(
            'revision' => $revision,
        );
    }

    /**
     * Show the detail for a specific revision entity
     */
    public function revisionEntityAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $revisionEntityId = (int) $this->getEvent()->getRouteMatch()->getParam('revisionEntityId');

        $revisionEntity = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity')->find($revisionEntityId);

        if (!$revisionEntity)
            return $this->plugin('redirect')->toRoute('audit');

        $repository = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity');

        return array(
            'page' => $page,
            'revisionEntity' => $revisionEntity,
            'auditService' => $this->getServiceLocator()->get('auditService'),
        );
    }

    /**
     * Lists revisions for the supplied entity.  Takes an audited entity class or audit class
     *
     * @param string $className
     * @param string $id
     */
    public function entityAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $entityClass = $this->getEvent()->getRouteMatch()->getParam('entityClass');

        return array(
            'entityClass' => $entityClass,
            'page' => $page,
        );
    }

    /**
     * Compares an entity at 2 different revisions.
     *
     *
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param null|int $oldRev if null, pulled from the posted data
     * @param null|int $newRev if null, pulled from the posted data
     * @return Response
     */
    public function compareAction()
    {
        $revisionEntityId_old = $this->getRequest()->getPost()->get('revisionEntityId_old');
        $revisionEntityId_new = $this->getRequest()->getPost()->get('revisionEntityId_new');

        $revisionEntity_old = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity')->find($revisionEntityId_old);
        $revisionEntity_new = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity')->find($revisionEntityId_new);

        if (!$revisionEntity_old and !$revisionEntity_new)
            return $this->plugin('redirect')->toRoute('audit');

        return array(
            'revisionEntity_old' => $revisionEntity_old,
            'revisionEntity_new' => $revisionEntity_new,
        );
    }
}

