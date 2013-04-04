<?php
return array(
    'output_buffering' => false, // required for testing sessions
    'modules' => array(
        'DoctrineModule',
        'DoctrineORMModule',
        'ZfcBase',
        'ZfcUser',
        'ZfcUserDoctrineORM',
        'ZF2EntityAudit'
    ),
    'module_listener_options' => array(
        'config_glob_paths' => array(
            __DIR__ . '/testing.config.php',
        ),
        'module_paths' => array(),
    ),
);
