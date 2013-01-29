<?php
return array(
    'modules' => array(
        'Application',
        'DoctrineModule',
        'DoctrineORMModule',
        'ZendDeveloperTools',
        'ZfcBase',
        'ZfcUser',
	'ZfcUserDoctrineORM',
        //'ScnSocialAuth',
        //'ScnSocialAuthDoctrineORM',
	'ZF2EntityAudit'
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
        'module_paths' => array(
            './module',
            './vendor',
        ),
    ),
);
