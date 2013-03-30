<?php

namespace ZF2EntityAudit;

return array(
    'doctrine' => array(
        'driver' => array(
            __NAMESPACE__ . '_driver' => array(
                'class' => 'ZF2EntityAudit\Mapping\Driver\AuditDriver',
            ),

            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver',
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'audit' => 'ZF2EntityAudit\Controller\IndexController'
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),

    'router' => array(
        'routes' => array(
            'audit' => array(
                'type' => 'Literal',
                'priority' => 1000,
                'options' => array(
                    'route' => '/audit',
                    'defaults' => array(
                        'controller' => 'audit',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'log' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/log[/:page]',
                            'constraints' => array(
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'revisions' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/revision[/:rev]',
                            'constraints' => array(
                                'rev' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'revision',
                                'rev' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/audit/entity[/:id[/:className]]',
                            'constraints' => array(
                                'id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'entity',
                                'className' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'details' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/audit/details[/:id[/:className[/:rev]]]',
                            'constraints' =>array(
                                'id' => '[0-9]*',
                                'rev' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action' => 'detail',
                                'id' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'className' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'rev' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'compare' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/audit/compare[/:className[/:id]]',
                            'constraints' =>array(
                                'id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action' => 'compare',
                                'id' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'className' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);