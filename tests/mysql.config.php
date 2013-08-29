<?php
return array(
    'doctrine' => array(
        'driver' => array(
            'test_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/ZF2EntityAuditTest/Entity/')
            ),
            'orm_default' => array(
                'drivers' => array(
                     'ZF2EntityAuditTest\Entity' => 'test_driver'
                )
            )
        ),
        'connection' => array(
            'orm_default' => array(
                'configuration' => 'orm_default',
                'eventmanager'  => 'orm_default',
                'driverClass'   => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
                'params' => array(
                    'host'     => 'localhost',
                    'port'     => '3306',
                    'user'     => 'travis',
                    'password' => '',
                    'dbname'   => 'zf2entityaudit',
                    'driverOptions' => array(
                        1002=>'SET NAMES utf8'
                    )
                ),
            ),
        ),
    ),
    'zfcuser' => array(
        'zend_db_adapter' => 'zfcuser_doctrine_em',
        'user_entity_class' => 'ZfcUser\Entity\User',
        'enable_registration' => true,
        'enable_username' => true,
        'enable_display_name' => true,
        'auth_identity_fields' => array( 'email', 'username' ),
        'login_form_timeout' => 300,
        'user_form_timeout' => 300,
        'login_after_registration' => true,
        'use_redirect_parameter_if_present' => true,
        'login_redirect_route' => 'default'
    )
);
