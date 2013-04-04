<?php
namespace ZF2EntityAuditTest\Entity;

use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Table("writer")
 * @ORM\Entity
 */
class Writer
{
    /** @ORM\Id
     *	@ORM\Column(type="integer")
     *	@ORM\GeneratedValue
        */
    private $id;
    /** @ORM\Column(type="string") */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}
