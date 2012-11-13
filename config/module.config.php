<?php

return array(
    'router' => array(
        'routes' => array(
            'audit_index' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/audit',
                    'defaults' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                        'page' => "1"
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'page' => "[0-9]"
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
            'audit_viewrevision' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/audit/revision[/:rev]',
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
                    'route' => '/audit/compare[/:id[/:className[/:oldRev[/:newRev]]]]',
                    'constraints' => array(
                        '__NAMESPACE__' => 'zf2entityaudit\Controller',
                        'controller' => 'Index',
                        'action' => 'compare',
                        'id' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'className' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'oldRev' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'newRev' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                )
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'ZF2EntityAudit\Controller\Index' => 'ZF2EntityAudit\Controller\IndexController'
        ),
    ),
    'service_manager' => array(
        'aliases' => array(
            'default' => 'doctrine.entitymanager.orm_default',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);