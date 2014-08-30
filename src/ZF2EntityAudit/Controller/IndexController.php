<?php

namespace ZF2EntityAudit\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZF2EntityAudit\Paginator\DbalAdapter;
use Zend\Paginator\Paginator ;

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
     * @return \Zend\View\Model\ViewModel
     */
    public function indexAction()
    {
        /**
         * @var integer $page
         */
        $sm             = $this->getServiceLocator() ;
        $auditReader    = $sm->get('auditReader');
        $config         = $sm->get("Config");
        $ZF2AuditConfig = $config["zf2-entity-audit"];
        $page           = (int) $this->params()->fromRoute('page');
        $limit          = $ZF2AuditConfig['ui']['page.limit'];
        $paginator      = new Paginator(new DbalAdapter($auditReader->paginateRevisionsQuery()));

        $paginator->setDefaultItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);

        return new ViewModel(array(
            'paginator'   => $paginator ,
            'auditReader' => $auditReader,
        ));
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function viewRevisionAction()
    {
        $rev = (int) $this->params()->fromRoute('rev');
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
     * @return ViewModel
     */
    public function viewEntityAction()
    {
        $className = $this->params()->fromRoute('className');
        $className = str_replace('_', '\\', $className);
        $id        = $this->params()->fromRoute('id');

        $ids       = explode(',', $id);
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
     * @return \Zend\View\Model\ViewModel
     */
    public function viewdetailAction()
    {
        $className = $this->params()->fromRoute('className');
        $className = str_replace('_', '\\', $className);
        $id        = $this->params()->fromRoute('id');
        $rev       = $this->params()->fromRoute('rev');
        $ids       = explode(',', $id);
        $entity    = $this->getServiceLocator()->get('auditReader')->find($className, $ids, $rev);
        $data      = $this->getServiceLocator()->get('auditReader')->getEntityValues($className, $entity);
        ksort($data);

        return new ViewModel(array(
            'id' => $id,
            'rev' => $rev,
            'className' => $className,
            'entity' => $entity,
            'data' => $data,
        ));
    }

    /**
     * Compares an entity at 2 different revisions.
     *
     * @return \Zend\Http\Response
     */
    public function compareAction()
    {
        /**
         * @var string   $className
         * @var string   $id        Comma separated list of identifiers
         * @var null|int $oldRev    if null, pulled from the posted data
         * @var null|int $newRev    if null, pulled from the posted data
         */
        $className = $this->params()->fromRoute('className');
        $className = str_replace('_', '\\', $className);
        $id        = $this->params()->fromRoute('id');
        $oldRev    = $this->params()->fromQuery('oldRev', null);
        $newRev    = $this->params()->fromQuery('newRev', null);

        $posted_data = $this->params()->fromPost();
        if (null === $oldRev) {
            $oldRev = (int) $posted_data['oldRev'];
        }

        if (null === $newRev) {
            $newRev = (int) $posted_data["newRev"];
        }
        $ids = explode(',', $id);
        $diff = $this->getServiceLocator()->get('auditReader')->diff($className, $ids, $oldRev, $newRev);

        return new ViewModel(array(
            'className' => $className,
            'id' => $id,
            'oldRev' => $oldRev,
            'newRev' => $newRev,
            'diff' => $diff,
        ));
    }

}
