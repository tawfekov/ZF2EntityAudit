<?php

namespace ZF2EntityAudit\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use SimpleThings\EntityAudit\Utils\ArrayDiff;
use Doctrine\ORM\Mapping\ClassMetadata;

class IndexController extends AbstractActionController {

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
     * @return  \Zend\View\Model\ViewModel
     */
    public function indexAction()
    {
        $sm = $this->getServiceLocator() ;
        $auditReader = $sm->get('auditReader');
        $config = $sm->get("Config");
        $ZF2AuditConfig = $config["zf2-entity-audit"];
        $page = (int)$this->getEvent()->getRouteMatch()->getParam('page');
        $revisions = $auditReader->findRevisionHistory($ZF2AuditConfig['ui']['page.limit'], 20 * ($page - 1));
        return new ViewModel(array(
            'revisions' => $revisions,
            'auditReader' => $auditReader,
        ));
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @param integer $rev
     * @return \Zend\View\Model\ViewModel
     *
     */
    public function revisionAction() {
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

    /**
     * Lists revisions for the supplied entity.
     *
     * @param string $className
     * @param string $id
     * @return \Zend\View\Model\ViewModel
     */
    public function entityAction() {
        $className = $this->getEvent()->getRouteMatch()->getParam('className');
        $id = $this->getEvent()->getRouteMatch()->getParam('id');

        $ids = explode(',', $id);
        $revisions = $this->getServiceLocator()->get('auditReader')->findRevisions($className, $ids);
        return new ViewModel(array(
                    'id' => $id,
                    'className' => $className,
                    'revisions' => $revisions,
                ));
    }

    /**
     * Shows the data for an entity at the specified revision.
     *
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param int $rev
     * @return \Zend\View\Model\ViewModel
     */
    public function detailAction() {
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
    public function compareAction() {
        $className = $this->getEvent()->getRouteMatch()->getParam('className');
        $id = $this->getEvent()->getRouteMatch()->getParam('id');
        $oldRev = $this->getEvent()->getRouteMatch()->getParam('oldRev');
        $newRev = $this->getEvent()->getRouteMatch()->getParam('newRev');

        $em = $this->getEntityManager();
        $metadata = $em->getClassMetadata($className);
        $posted_data = $this->params()->fromPost();
        if (null === $oldRev) {
            $oldRev = (int)$posted_data['oldRev'];
        }

        if (null === $newRev) {
            $newRev = (int)$posted_data["newRev"];
        }
        $ids = explode(',', $id);
        $oldEntity = $this->getServiceLocator()->get('auditReader')->find($className, $ids, $oldRev);
        $oldData = $this->getEntityValues($metadata, $oldEntity);

        $newEntity = $this->getServiceLocator()->get('auditReader')->find($className, $ids, $newRev);
        $newData = $this->getEntityValues($metadata, $newEntity);

        $differ = new ArrayDiff();
        $diff = $differ->diff($oldData, $newData);

        return new ViewModel(array(
                    'className' => $className,
                    'id' => $id,
                    'oldRev' => $oldRev,
                    'newRev' => $newRev,
                    'diff' => $diff,
                ));
    }

    protected function getEntityValues(ClassMetadata $metadata, $entity) {
        $fields = $metadata->getFieldNames();

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        return $return;
    }

}

