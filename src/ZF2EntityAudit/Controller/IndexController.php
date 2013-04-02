<?php

namespace ZF2EntityAudit\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZF2EntityAudit\Utils\ArrayDiff;
use Doctrine\ORM\Mapping\ClassMetadata;

class IndexController extends AbstractActionController
{

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
    }

    /**
     * Renders a paginated list of revisions.
     *
     * @param int $page
     */
    public function indexAction()
    {
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $revisions = $this->getEntityManager()->getRepository('ZF2EntityAudit\\Entity\\Revision')->findBy(
            array(), array('id' => 'DESC'), 20, 20 * $page
        );

        return new ViewModel(array(
            'revisions' => $revisions,
        ));
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @param integer $rev
     * @return \Zend\View\Model\ViewModel
     *
     */
    public function revisionAction()
    {
        $rev = (int) $this->getEvent()->getRouteMatch()->getParam('rev');
        $revision = $this->getServiceLocator()->get('auditReader')->findRevision($rev);
        if (!$revision) {
            echo(sprintf('Revision %i not found', $rev));
        }
        $changedEntities = $this->getServiceLocator()->get('auditReader')->findEntitesChangedAtRevision($rev);

        return new ViewModel(array(
            'revision' => $revision,
            'changedEntities' => $changedEntities,
        ));
    }

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
     * @return \Zend\View\Model\ViewModel
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
     * Shows the data for an entity at the specified revision.
     *
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param int $rev
     * @return \Zend\View\Model\ViewModel
     */
    public function detailAction()
    {
        $className = $this->getEvent()->getRouteMatch()->getParam('className');
        $id = $this->getEvent()->getRouteMatch()->getParam('id');
        $rev = $this->getEvent()->getRouteMatch()->getParam('rev');
        $em = $this->getEntityManager();
        $metadata = $em->getClassMetadata($className);

        $ids = explode(',', $id);
        $entity = $this->getServiceLocator()->get('auditReader')->find($className, $ids, $rev);

        return new ViewModel(array(
                    'id' => $id,
                    'rev' => $rev,
                    'className' => $className,
                    'entity' => $entity,
                ));
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

