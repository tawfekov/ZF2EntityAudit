<?php

namespace ZF2EntityAudit\Controller;

use Doctrine\ORM\EntityManager;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Console\Request as ConsoleRequest;
use RuntimeException;
use ZF2EntityAudit\EventListener\LogRevisionsListener;

class ConsoleController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function updateAction()
    {
        echo "- before starting : 1- please take a backup \n";
        echo "                    2- update the all `anonymous` in the revisions table to some exsited user \n";
        echo "- cancel this command and update it then restart it again \n";
        echo "- sleeping 30 seconds \n";
        sleep(30);
        echo "waking up\n";
        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest) {
            throw new RuntimeException('You can only use this action from a console!');
        }
        $sl = $this->getServiceLocator();
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        //$auditConfig = $sl->get("auditConfig");
        $revisionTableName = $this->getAuditConfig()->getRevisionTableName();
        $revsions = $conn->createQueryBuilder()->select("r.*")->from($revisionTableName, "r")->execute()->fetchAll();
        echo "- start updating ";
        foreach ($revsions as $r) {
            try {
                echo "  --start working on revsion id #{$r["id"]}\n";
                //// don't forget to update this to your ZFC user entity name
                $currentUser = $em->getRepository("Application\Entity\User")->findBy(array("email" => $r["username"]));
                $currentUser = $currentUser[0];
                $query = $conn->createQueryBuilder();
                $query->update("$revisionTableName", "r");
                $query->where("r.id = :id");
                $query->set("r.username", $currentUser->getId());
                $query->setParameter("id", $r["id"]);
                $query->execute();
                echo "  --finished working on revsion id #{$r["id"]}\n";
            } catch (Exception $exc) {
                echo $exc->getMessage();
                continue;
            }
        }
        echo "- ✔ finished updating \n";
        echo "- now its time to manually rename the `username` field to be `user_id` and change its type to be `int`\n";
        echo "- after doing so , simply you need to run a doctrine2 cli command \n";
        echo "- './vendor/bin/doctrine-module orm:schema-tool:update --dump-sql' to view the sql executed \n";
        echo "- './vendor/bin/doctrine-module orm:schema-tool:update --force' to execute  some sql  \n";
        echo "- then you should be able to work as before ";
        echo "- Done";
    }

    public function createInitialRevisionsAction()
    {
        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest) {
            throw new RuntimeException('You can only use this action from a console!');
        }

        $userEmail = $this->params()->fromRoute('userEmail');
        $missingUserMessage = 'You must specifiy the email of a user to own the initial revisions: php public/index.php initialize-revisions example@example.com';
        if (empty($userEmail)) {
            throw new RuntimeException($missingUserMessage);
        }

        $user = $this->getUserRepository()->findOneBy(['email' => $userEmail]);

        if (empty($user)) {
            throw new RuntimeException($missingUserMessage);
        }

        $this->getAuditConfig()->setCurrentUser($user);

        $log = $this->getLogRvisionListener();
        $uow = $this->getEntityManager()->getUnitOfWork();

        $createdCount = 0;
        foreach ($this->getAuditConfig()->getAuditedEntityClasses() as $className) {
            $class = $this->getEntityManager()->getClassMetadata($className);
            
            foreach ($this->getEntityManager()->getRepository($className)->findAll() as $entity) {
                $entityId = $entity->getId();
                $revisions = $this->getAuditReader()->findRevisions($className, $entityId);

                if (!count($revisions)) {
                    $entityData = $log->getOriginalEntityData($entity);

                    $log->saveRevisionEntityData($class, $entityData, 'INS');
                    $createdCount++;
                }
            }
        }

        echo "Done: Created $createdCount revisions. Each audited entity should have at least one revision now.\n";
    }

    /**
     * @return \ZF2EntityAudit\Audit\Configuration
     */
    protected function getAuditConfig()
    {
        /** @var \ZF2EntityAudit\Audit\Configuration $auditConfig */
        $auditConfig = $this->getServiceLocator()->get('auditConfig');

        return $auditConfig;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $sl = $this->getServiceLocator();
        return $sl->get("doctrine.entitymanager.orm_default");
    }

    /**
     * @return \ZF2EntityAudit\Audit\Reader
     */
    protected function getAuditReader()
    {
        return $this->getServiceLocator()->get('auditReader');
    }

    /**
     * @return LogRevisionsListener
     */
    protected function getLogRvisionListener()
    {
        $auditManager = $this->getServiceLocator()->get('AuditManager');
        $manager = new LogRevisionsListener($auditManager);
        $manager->setEntityManager($this->getEntityManager());

        return $manager;
    }

    protected function getUserRepository()
    {
        $className = $this->getAuditConfig()->getZfcUserEntityClass();
        return $this->getEntityManager()->getRepository($className);
    }
}
