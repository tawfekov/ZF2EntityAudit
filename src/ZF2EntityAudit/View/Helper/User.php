<?php

namespace ZF2EntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Helper\Gravatar;
use Doctrine\ORM\EntityManager;

class User extends AbstractHelper
{
    protected $em;
    
protected $ZFcUserClassName  ; 
    
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
    
    public function setZfcUserEntityClass($className)
    {
        $this->ZFcUserClassName = $className ;
    }
    public function __invoke($userId)
    {
        $user = $this->em->getRepository($this->ZFcUserClassName)->find($userId);
        $html = '<div class="span6">';
        $html .= '<div class="span4" style="float:left;margin-top:5px">';
        if ($user->getDisplayName()) {
            $html .= "<p>DisplayName : {$this->getView()->escapeHtml($user->getDisplayName())}<br/>";
        }
        if ($user->getUserName()) {
            $html .= "UserName : {$this->getView()->escapeHtml($user->getUserName())}<br/>";
        }
        if ($user->getEmail()) {
            $html .= "Email Address : {$this->getView()->escapeHtml($user->getEmail())}</p>";
        }
        $html .= '<span class=" badge badge-info">15 insert</span> ';
        $html .= '<span class=" badge badge-warning">8 updates</span> ';
        $html .= '<span class=" badge badge-important">15 delete</span> ';
        $html .= '</div>';
        $html .= '<div class="span2">';
        $html .= '<div>'.$this->Gravatar($user->getEmail()).'</div>';
        $html .= '</div>';

        return $html;
    }

    public function Gravatar($email)
    {
        $options = array(
            'img_size' => 96,
            'default_img' => 'mm',
            'rating' => 'g',
            'secure' => false,
        );

        return $this->getView()->Gravatar($email , $options);
    }

}
