<?php

namespace ZF2EntityAudit\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Console\Request as ConsoleRequest;
use RuntimeException;

class ConsoleController extends AbstractActionController {

    public function indexAction() {
        return new ViewModel();
    }

    public function updateAction() {
        
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
        $em = $sl->get("doctrine.entitymanager.orm_default");
        $users = $em->getRepository("Adsl\Entity\User")->findAll();
        $conn = $em->getConnection();
        $auditConfig = $sl->get("auditConfig");
        $revisionTableName = $auditConfig->getRevisionTableName();
        $revsions = $conn->createQueryBuilder()->select("r.*")->from($revisionTableName, "r")->execute()->fetchAll();
        echo "- start updating ";
        foreach ($revsions as $r) {
            try {
                echo "  --start working on revsion id #{$r["id"]}\n";
                $currentUser = $em->getRepository("Adsl\Entity\User")->findBy(array("email" => $r["username"]));
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
}