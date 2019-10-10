<?php

namespace ZF2EntityAudit;

return array(
    'router' => array(
        'routes' => array(
            'audit_index' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/audit[/:page]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                        'page' => "1"
                    ),
                ),
            ),
            'audit_log' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/audit/log[/:page]',
                    'constraints' => array(
                        'page' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                        'page' => '1'
                    )
                )
            ),
            'audit_viewrevision' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/audit/revision[/:rev]',
                    'constraints' => array(
                        'rev' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'viewrevision',
                        'rev' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    )
                )
            ),
            'audit_viewentity' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/audit/entity[/:id[/:className]]',
                    'constraints' => array(
                        'id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'viewentity',
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'className' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    )
                )
            ),
            'audit_viewdetails' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/audit/details[/:id[/:className[/:rev]]]',
                    'constraints' => array(
                        'id' => '[0-9]*',
                        'rev' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'viewdetail',
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'className' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'rev' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    )
                )
            ),
            'audit_compare' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/audit/compare[/:className[/:id]]',
                    'constraints' => array(
                        'id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'compare',
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'className' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                )
            ),
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'update-datebase' => array(
                    'options' => array(
                        'route' => 'update',
                        'defaults' => array(
                            'controller' => 'ZF2EntityAudit\Controller\Console',
                            'action' => 'update'
                        )
                    )
                ),
                'ititialize-revisions' => array(
                    'options' => array(
                        'route' => 'initialize-revisions <userEmail>',
                        'defaults' => array(
                            'controller' => 'ZF2EntityAudit\Controller\Console',
                            'action' => 'createInitialRevisions',
                            'userEmail' => null
                        )
                    )
                )
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'ZF2EntityAudit\Controller\Index' => 'ZF2EntityAudit\Controller\IndexController',
            'ZF2EntityAudit\Controller\Console' => 'ZF2EntityAudit\Controller\ConsoleController'
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
            'paginator/control.phtml' => __DIR__ . '/../view/zf2-entity-audit/paginator/controls.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
