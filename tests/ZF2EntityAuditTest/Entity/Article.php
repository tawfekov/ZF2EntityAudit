<?php
namespace ZF2EntityAuditTest\Entity;

use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Table("article")
 * @ORM\Entity
 */
class Article
{
    /**
     *  @ORM\Id
     *  @ORM\Column(type="integer")
     *  @ORM\GeneratedValue
     */
    private $id;

    /** @ORM\Column(type="string") */
    private $title;

    /** @ORM\Column(type="text") */
    private $text;

    /** @ORM\ManyToOne(targetEntity="Writer") */
    private $author;

    public function __construct($title, $text, $author)
    {
        $this->title = $title;
        $this->text = $text;
        $this->author = $author;
    }

    public function setText($text)
    {
        $this->text = $text;
    }
}
