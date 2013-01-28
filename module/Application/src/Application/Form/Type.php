<?php

namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\Element;

class Type extends Form {

    public function __construct() {
        parent::__construct();
        $this->setAttribute("method", "post");
        $this->add(array(
            'name' => 'name',
            'attributes' => array(
                'type' => 'text',
                'id' => "name"
            ),
            "options" => array(
                'label' => 'Name',
            )
        ));

        $this->add(array(
            'name' => 'tag',
            'attributes' => array(
                'type' => 'text',
                'id' => "tag"
            ),
            "options" => array(
                'label' => 'Tag',
            )
        ));

        $this->add(array(
            'name' => 'save',
            'attributes' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary btn-large',
                'value' => 'Save'
            ),
            "options" => array(
                'label' => " ",
            )
        ));
        return $this;
    }

}

?>
