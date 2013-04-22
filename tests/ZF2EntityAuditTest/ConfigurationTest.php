<?php
namespace ZF2EntityAuditTest;

use ZF2EntityAuditTest\Bootstrap ;

use ZF2EntityAudit\Audit\Configuration;
use ZF2EntityAudit\Audit\Manager;
use Doctrine\ORM\Mapping AS ORM;

use ZF2EntityAuditTest\Entity\Article;
use ZF2EntityAuditTest\Entity\Writer;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
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
    }

    public function testconfiguration()
    {
        $config = new Configuration();
        $config->setCurrentUser($this->ZfcUserMock);

        $prefix = "prefix";
        $config->setTablePrefix($prefix);

        $suffix = "suffix";
        $config->setTableSuffix($suffix);

        $fieldName = "fieldName";
        $config->setRevisionFieldName($fieldName);


        $revisionIdFieldType = "string";
        $config->setRevisionIdFieldType($revisionIdFieldType);

        $tableName = "tableName";
        $config->setRevisionTableName($tableName);

        $revisionTypeFieldName = "string";
        $config->setRevisionTypeFieldName($revisionTypeFieldName);


        $ipaddress = $config->getIpAddress();
        $config->setAuditedEntityClasses(
                        array(
                          'ZF2EntityAuditTest\Entity\Article',
                          'ZF2EntityAuditTest\Entity\Writer'
                          )
                    );
        $config->setNote("default note");

        $this->auditManager = new Manager($config);
        $this->auditManager->registerEvents($this->em->getEventManager());

        /// creating the tables
        $this->schemaTool = $this->getSchemaTool();
        $this->schemaTool->createSchema(array(
            $this->em->getClassMetadata('ZF2EntityAuditTest\Entity\Article'),
            $this->em->getClassMetadata('ZF2EntityAuditTest\Entity\Writer')
        ));
        $this->assertInstanceOf("ZfcUser\Entity\User" , $this->ZfcUserMock);
        $this->assertEquals($prefix ,$config->getTablePrefix());
        $this->assertEquals($suffix ,$config->getTableSuffix());
        $this->assertEquals($fieldName ,$config->getRevisionFieldName());
        $this->assertEquals($tableName ,$config->getRevisionTableName());
        $this->assertEquals($revisionIdFieldType ,$config->getRevisionIdFieldType());
        $this->assertEquals($revisionTypeFieldName ,$config->getRevisionIdFieldType());
        $this->assertEquals($ipaddress ,"1.1.1.9");

    }

    public function testException()
    {
        $config = new Configuration();
        $this->setExpectedException("ZF2EntityAudit\Audit\Exception", "ZF2EntityAudit Verion 0.2 doesn't support anonymous editing , please use `0.1-stable` for  anonymous editing");
        $config->setCurrentUser("mockuser");
    }

    public function testCliAuditWithLocalIPAddress()
    {
        unset($_SERVER["REMOTE_ADDR"]);
        $config = new Configuration();
        $config->setCurrentUser($this->ZfcUserMock);
        $ipaddress = $config->getIpAddress();
        $this->assertEquals("127.0.0.1" , $ipaddress);
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
