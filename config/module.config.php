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
                    'page' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:page]',
                            'constraints' => array(
                                'page' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'index',
                                'page' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'revisions' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/revision[/:revisionId]',
                            'constraints' => array(
                                'revisionId' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'revision',
                                'revisionId' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'revision-entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/revision-entity[/:revisionEntityId]',
                            'constraints' => array(
                                'revisionEntityId' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'revisionEntity',
                                'revisionEntityId' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'entity' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/entity[/:entityClass]',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action'     => 'entity',
                                'entityClass' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                        ),
                    ),
                    'compare' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/audit/compare',
                            'defaults' => array(
                                'controller' => 'audit',
                                'action' => 'compare',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);