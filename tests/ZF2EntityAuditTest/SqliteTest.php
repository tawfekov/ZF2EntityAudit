<?php
namespace ZF2EntityAuditTest;

use ZF2EntityAuditTest\Bootstrap ;

use ZF2EntityAudit\Audit\Configuration;
use ZF2EntityAudit\Audit\Manager;
use Doctrine\ORM\Mapping AS ORM;

use ZF2EntityAuditTest\Entity\Article;
use ZF2EntityAuditTest\Entity\Writer;

class SqliteTest extends \PHPUnit_Framework_TestCase
{
     /**
      * @var EntityManager
      */
    private $em = null;

    /**
     * @var Manager
     */
    private $auditManager = null;

    /**
    * @var user
    **/
    private $ZfcUserMock = null ;

    private $schemaTool = null ;

    public function setUp()
    {
        $this->Bootstrap = new Bootstrap();
        $this->em = $this->Bootstrap->getServiceManager()->get("doctrine.entitymanager.orm_default");

        /// echo sql logger
        //$this->em->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());;

        /// let's create the default user
        $this->ZfcUserMock = $this->createUser() ;

        $auditConfig = new Configuration();
        $auditConfig->setCurrentUser($this->ZfcUserMock);
        $auditConfig->setAuditedEntityClasses(
                        array(
                          'ZF2EntityAuditTest\Entity\Article',
                          'ZF2EntityAuditTest\Entity\Writer'
                          )
                    );
        $auditConfig->setNote("default note");

        $this->auditManager = new Manager($auditConfig);
        $this->auditManager->registerEvents($this->em->getEventManager());

        /// creating the tables
        $this->schemaTool = $this->getSchemaTool();
        $this->schemaTool->createSchema(array(
            $this->em->getClassMetadata('ZF2EntityAuditTest\Entity\Article'),
            $this->em->getClassMetadata('ZF2EntityAuditTest\Entity\Writer')
        ));
    }

    public function testAuditable()
    {
        $user = new Writer("beberlei");
        $article = new Article("test", "yadda!", $user);

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT * FROM writer_audit')));
        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT * FROM article_audit')));

        $article->setText("oeruoa");

        $this->em->flush();

        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT * FROM article_audit')));

        $this->em->remove($user);
        $this->em->remove($article);
        $this->em->flush();

        $this->assertEquals(3, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT * FROM writer_audit')));
        $this->assertEquals(3, count($this->em->getConnection()->fetchAll('SELECT * FROM article_audit')));
    }

    public function testFind()
    {
        $user = new Writer("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        //$reader = $this->auditManager->createAuditReader($this->getServiceManager()->get("doctrine.entitymanager.orm_default"));
        $reader = $this->getAuditReader();
        $auditUser = $reader->find(get_class($user), $user->getId(), 1);

        $this->assertInstanceOf(get_class($user), $auditUser, "Audited User is also a User instance.");
        $this->assertEquals($user->getId(), $auditUser->getId(), "Ids of audited user and real user should be the same.");
        $this->assertEquals($user->getName(), $auditUser->getName(), "Name of audited user and real user should be the same.");
        $this->assertFalse($this->em->contains($auditUser), "Audited User should not be in the identity map.");
        $this->assertNotSame($user, $auditUser, "User and Audited User instances are not the same.");

    }

    public function testFindNoRevisionFound()
    {
        $reader = $this->getAuditReader();

        $this->setExpectedException("ZF2EntityAudit\Audit\Exception", "No revision of class 'ZF2EntityAuditTest\Entity\Writer' (1) was found at revision 1 or before. The entity did not exist at the specified revision yet.");
        $auditUser = $reader->find("ZF2EntityAuditTest\Entity\Writer", 1, 1);
    }

    public function testFindNotAudited()
    {
        $reader = $this->getAuditReader();

        $this->setExpectedException("ZF2EntityAudit\Audit\Exception", "Class 'stdClass' is not audited.");
        $auditUser = $reader->find("stdClass", 1, 1);
    }

    public function testFindRevisionHistory()
    {
        $user = new Writer("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        $article = new Article("test", "yadda!", $user);

        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->getAuditReader();
        $revisions = $reader->findRevisionHistory();

        $this->assertEquals(2, count($revisions));
        $this->assertContainsOnly('ZF2EntityAudit\Entity\Revision', $revisions);

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('MOCKUSER', $revisions[1]->getUser()->getDisplayName());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('MOCKUSER', $revisions[1]->getUser()->getDisplayName());
    }

    public function testFindEntitesChangedAtRevision()
    {
        $user = new Writer("beberlei");
        $article = new Article("test", "yadda!", $user);

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->getAuditReader();
        $changedEntities = $reader->findEntitesChangedAtRevision(1);

        $this->assertEquals(2, count($changedEntities));
        $this->assertContainsOnly('ZF2EntityAudit\Entity\ChangedEntity', $changedEntities);

        $this->assertEquals('ZF2EntityAuditTest\Entity\Article', $changedEntities[0]->getClassName());
        $this->assertEquals('INS', $changedEntities[0]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[0]->getId());
        $this->assertInstanceOf('ZF2EntityAuditTest\Entity\Article', $changedEntities[0]->getEntity());

        $this->assertEquals('ZF2EntityAuditTest\Entity\Writer', $changedEntities[1]->getClassName());
        $this->assertEquals('INS', $changedEntities[1]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[1]->getId());
        $this->assertInstanceOf('ZF2EntityAuditTest\Entity\Writer', $changedEntities[1]->getEntity());
    }

    public function testFindRevisions()
    {

        $user = new Writer("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        $user->setName("beberlei2");
        $this->em->flush();

        $reader = $this->getAuditReader();
        $revisions = $reader->findRevisions(get_class($user), $user->getId());

        $this->assertEquals(2, count($revisions));
        $this->assertContainsOnly('ZF2EntityAudit\Entity\Revision', $revisions);

        $this->assertInstanceOf("ZfcUser\Entity\User", $revisions[1]->getUser());

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('MOCKUSER', $revisions[1]->getUser()->getDisplayName());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('MOCKUSER', $revisions[1]->getUser()->getDisplayName());
    }

    public function tearDown()
    {
        return $this->getSchemaTool()->dropDatabase();
    }

    private function getServiceManager()
    {
        $sl  =$this->Bootstrap->getServiceManager();

        return $sl;
    }

    private function createUser()
    {
        // create our mock user
        $sl = $this->getServiceManager();
        $userService = $sl->get("zfcuser_user_service");
        $randomness = "mockuser_".mt_rand();
        $data = array(
            "username"       => "{$randomness}",
            "password"       => "password",
            "passwordVerify" => "password",
            "display_name"   => "MOCKUSER",
            "email"          => "{$randomness}@google.com"
        );
        $user = $userService->register($data);

        return $user;
    }
    private function getSchemaTool()
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);

        return $schemaTool;
    }

    private function getAuditReader()
    {
        $em = $this->getServiceManager()->get("doctrine.entitymanager.orm_default");

        return $this->auditManager->createAuditReader($em);
    }
}
