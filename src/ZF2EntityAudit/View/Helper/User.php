<?php

namespace ZF2EntityAudit\View\Helper;

use Zend\View\Helper\AbstractHelper;
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
        $html = '<div class="row">';
        $html .= '<div class="col-lg-8">';
        if ($user->getDisplayName()) {
            $html .= "<p><strong>DisplayName</strong>: {$this->getView()->escapeHtml($user->getDisplayName())}<br/>";
        }
        if ($user->getUserName()) {
            //$html .= "<strong>UserName</strong>: {$this->getView()->escapeHtml($user->getUserName())}<br/>";
        }
        if ($user->getEmail()) {
            $html .= "<strong>Email Address</strong>: {$this->getView()->escapeHtml($user->getEmail())}</p>";
        }
        //$html .= '<span class="label label-info">15 insert</span> ';
        //$html .= '<span class="label label-warning">8 updates</span> ';
        //$html .= '<span class="label label-danger">15 delete</span> ';
        $html .= '</div>';
        $html .= '<div class="col-lg-4">';
        $html .= '<div>'.$this->Gravatar($user->getEmail()).'</div>';
        $html .= '</div>';

        return $html;
    }

    public function Gravatar($email)
    {
        $options = array(
            'img_size' => 90,
            'default_img' => 'mm',
            'rating' => 'g',
            'secure' => false,
        );

        $attributes = array(
            'class' => 'img-responsive img-circle',
        );

        return $this->getView()->Gravatar($email , $options, $attributes);
    }

}
