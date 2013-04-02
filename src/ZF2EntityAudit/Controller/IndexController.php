<?php

namespace ZF2EntityAudit\Controller;

use Zend\Mvc\Controller\AbstractActionController
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
        $revisions = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\Revision')->findBy(
                array(), array('id' => 'DESC'), 20, 20 * $page
        );

        return array(
            'revisions' => $revisions,
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
        $revisionEntityId = (int) $this->getEvent()->getRouteMatch()->getParam('revisionEntityId');
        $revisionEntity = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity')->find($revisionEntityId);

        if (!$revisionEntity)
            return $this->plugin('redirect')->toRoute('audit');

        return array(
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
        $entityClass = $this->getEvent()->getRouteMatch()->getParam('entityClass');

        if (in_array($entityClass, \ZF2EntityAudit\Module::getServiceManager()->get('auditModuleOptions')->getAuditedEntityClasses())) {
            $auditEntityClass = 'ZF2EntityAudit\\Entity\\' . str_replace('\\', '_', $entityClass);
        } else {
            $auditEntityClass = $entityClass;
        }

        $this->getServiceLocator()->get('auditService')->getRevisionEntities($entityClass);

        $revisionEntities = \ZF2EntityAudit\Module::getServiceManager()
            ->get('doctrine.entitymanager.orm_default')
            ->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity')
            ->findBy(array('auditEntityClass' => $auditEntityClass), array('id' => 'DESC'));

        return array(
            'entityClass' => $entityClass,
            'revisionEntities' => $revisionEntities,
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

