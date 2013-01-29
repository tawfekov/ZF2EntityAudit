<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController {

    public function indexAction() {

        $sm = $this->getServiceLocator();
        $em = $sm->get("doctrine.entitymanager.orm_default");
        $type_form = new \Application\Form\Type();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $type = new \Application\Entity\Type();
            $type_form->setInputFilter($type->getInputFilter());
            $type_form->setData($request->getPost());
            if ($type_form->isValid()) {
                $type->exchangeArray($type_form->getData());
                $em->persist($type);
                $em->flush();
                return $this->redirect()->toUrl("/");
            }
        }
        $records = $em->getRepository("Application\Entity\Type")->findAll();
        return new ViewModel(array(
                    "records" => $records,
                    "form" => $type_form
                ));
    }

    public function editAction() {
        if (!$this->zfcUserAuthentication()->hasIdentity()) {
            return $this->redirect()->toRoute('zfcuser/login');
        }

        $id = (int) $this->getEvent()->getRouteMatch()->getParam('id');
        $sm = $this->getServiceLocator();
        $em = $sm->get("doctrine.entitymanager.orm_default");
        $type = $em->getRepository("Application\Entity\Type")->find($id);

        $type_form = new \Application\Form\Type();
        $type_form->bind($type);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $type_form->setInputFilter($type->getInputFilter());
            $type_form->setData($request->getPost());
            if ($type_form->isValid()) {
                $type->exchangeArray($type_form->getData()->getArrayCopy());
                $em->persist($type);
                $em->flush();
                return $this->redirect()->toUrl("/");
            }
        }

        return new ViewModel(array(
            'form' => $type_form,
            'type' => $type,
        ));
    }

    public function deleteAction() {
        $id = (int) $this->getEvent()->getRouteMatch()->getParam('id');

        $em = $this->getServiceLocator()->get("doctrine.entitymanager.orm_default");

        $type = $em->getRepository("Application\Entity\Type")->find($id);
        if ($type) $em->remove($type);

        $em->flush();

        return $this->redirect()->toUrl("/");
    }
}
