<?php
namespace ZF2EntityAuditTest\Entity;

use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Table("article")
 * @ORM\Entity(repositoryClass = "ZF2EntityAuditTest\Repository\Article" )
 * @ORM\Entity
 */
class Article
{
    /**
     *  @ORM\Id
     *  @ORM\Column(type="integer")
     *  @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(type="string") */
    private $title;

    /** @ORM\Column(type="text") */
    private $text;

    /** @ORM\ManyToOne(targetEntity="Writer" , inversedBy="articles") */
    private $writer;

    public function __construct($title, $text, $writer)
    {
        $this->title = $title;
        $this->text = $text;
        $this->writer = $writer;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getWriter()
    {
        return $this->writer;
    }

    public function getId()
    {
        return $this->id;
    }

}
