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
        ///$this->em->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());;

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

    public function testaddingNote()
    {

        $auditManager = $this->auditManager;

        $user = new Writer("tawfek-daghistani");
        /// setting the note
        $auditManager->getConfiguration()->setNote("first_note");

        $this->em->persist($user);
        $this->em->flush();
        /// setting new  note
        $auditManager->getConfiguration()->setNote("second_note");
        $user->setName("Tawfek-Daghistani");
        $this->em->flush();

        $reader = $this->getAuditReader();
        $revisions = $reader->findRevisions(get_class($user), $user->getId());

        $this->assertEquals(2, count($revisions));

        $this->assertEquals("second_note", $revisions[0]->getNote());
        $this->assertEquals("first_note", $revisions[1]->getNote());

    }

    public function testNoRevesionFoundException()
    {
        $this->setExpectedException("ZF2EntityAudit\Audit\Exception", "No revision '1000' exists.");
        $this->getAuditReader()->findRevision(1000);
    }

    public function testFindRevisionsById()
    {
        $auditManager = $this->auditManager;

        $user = new Writer("tawfek-daghistani");
        /// setting the note
        $auditManager->getConfiguration()->setNote("first_note");

        $this->em->persist($user);
        $this->em->flush();

        $this->assertEquals(1, count($this->getAuditReader()->findRevision(1)));
    }

    public function testNotAuditedException()
    {
        $this->setExpectedException("ZF2EntityAudit\Audit\Exception","Class 'ZF2EntityAuditTest\Entity\Write' is not audited.");
        $this->getAuditReader()->findRevisions("ZF2EntityAuditTest\Entity\Write",1);
    }

    public function testFindingRepositoryClasses()
    {
        /// its useless now , but i will work on it soon
        $WriterRepository = $this->em->getRepository("ZF2EntityAuditTest\Entity\Writer");
        $ArticleRepository = $this->em->getRepository("ZF2EntityAuditTest\Entity\Article");

        $this->assertEquals("Doctrine\ORM\EntityRepository" , get_class($WriterRepository));
        $this->assertEquals("Doctrine\ORM\EntityRepository" , get_class($ArticleRepository));

    }

    public function testOneToManay()
    {

        $writer = new Writer("tawfek-daghistani");
        $article_1 = new Article("this is title","thisi is the body", $writer);
        $article_2 = new Article("this is title2","thisi is the body2", $writer);
        $this->em->persist($writer);
        $this->em->persist($article_2);
        $this->em->persist($article_1);
        $this->em->flush();

        $reader = $this->getAuditReader();
        $changedEntities = $reader->findEntitesChangedAtRevision("1");
        $this->assertEquals(count($changedEntities),"3");


        $foundRevisions = $reader->findRevisions("ZF2EntityAuditTest\Entity\Writer" , $writer->getId());
        $this->assertEquals(count($foundRevisions),"1");

        $writer->setName("Tawfekov");

        $this->em->persist($writer);
        $this->em->flush();

        $foundRevisions = $reader->findRevisions("ZF2EntityAuditTest\Entity\Writer" , $writer->getId());
        $this->assertEquals(count($foundRevisions),"2");

        $_writer = $article_2->getWriter();
        $this->assertInstanceOf("ZF2EntityAuditTest\Entity\Writer",$_writer);

    }

    public function testSimpleRevertBack()
    {
        $writer = new Writer("tawfekov");
        $this->em->persist($writer);
        $this->em->flush();


        $writer->setName("Tawfek-Daghistani");
        $this->em->persist($writer);
        $this->em->flush();
        $this->assertTrue($this->auditManager->revertBack($this->em,"ZF2EntityAuditTest\Entity\Writer" , $writer->getId() , "1" , "2"));

    }


    public function testPaginator()
    {
        $reader = $this->getAuditReader();
        $query = $reader->paginateRevisionsQuery();
        $paginatorAdapter = new \ZF2EntityAudit\Paginator\DbalAdapter($query);
        $paginator = new \Zend\Paginator\Paginator($paginatorAdapter);

        for ($i =0 ; $i < 20 ; $i++) {
            $writer = new Writer("tawfek" . rand());
            $article = new Article("title" , "text" , $writer);
            $this->em->persist($writer);
            $this->em->persist($article);
        }
        $this->em->flush();
        $this->assertEquals($reader->countRevisions() , "1");
        $this->assertEquals($paginator->count() , "1");

        for ($i =0 ; $i < 20 ; $i++) {
            $writer = new Writer("tawfek" . rand());
            $article = new Article("title" , "text" , $writer);
            $this->em->persist($writer);
            $this->em->persist($article);
            $this->em->flush();
        }
        // its 21 because (20 flushes + 1 flush as bluk flush in line 330 )
        $this->assertEquals($reader->countRevisions() , "21");
        $this->assertEquals($paginator->getAdapter()->count() , "21");
        $this->assertEquals(count($paginator->getAdapter()->getItems(1, 12)) , "12");
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
