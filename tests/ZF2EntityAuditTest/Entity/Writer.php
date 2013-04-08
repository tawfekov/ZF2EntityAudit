<?php
namespace ZF2EntityAuditTest\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection ;

/**
 * @ORM\Table("writer")
 * @ORM\Entity(repositoryClass = "ZF2EntityAuditTest\Repository\Writer" )
 * @ORM\Entity
 */
class Writer
{
    /** @ORM\Id
     *	@ORM\Column(type="integer")
     *	@ORM\GeneratedValue(strategy="AUTO")
        */
    private $id;

    /** @ORM\Column(type="string") */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Article")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="writer_id", referencedColumnName="id")
     * })
     */
    private $articles;

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

    public function setArticle(Article $article)
    {
        $this->articles[] = $article;

        return $this->articles;
    }

    public function getAtricles()
    {
        return $this->articles;
    }
}
