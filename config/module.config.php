<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'service_manager' => array(
        'abstract_factories' => array(
            'Apigility\Doctrine\Server\Resource\DoctrineResourceFactory',
            'Apigility\Doctrine\Server\Hydrator\DoctrineHydratorFactory',
        ),
    ),

    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../asset',
            ),
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Apigility\Doctrine\Admin\Controller\App' => 'Apigility\Doctrine\Admin\Controller\AppController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'apigility-doctrine-admin' => array(
                'type'  => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin',
                    'defaults' => array(
                        'controller' => 'Apigility\Doctrine\Admin\Controller\App',
                        'action'     => 'index',
                    ),
                ),
            ),
            'apigility-doctrine-admin-create-module' => array(
                'type'  => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/create-module',
                    'defaults' => array(
                        'controller' => 'Apigility\Doctrine\Admin\Controller\App',
                        'action'     => 'createModule',
                    ),
                ),
            ),
            'apigility-doctrine-admin-select-entities' => array(
                'type'  => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/select-entities[/:moduleName][/:objectManagerAlias]',
                    'defaults' => array(
                        'controller' => 'Apigility\Doctrine\Admin\Controller\App',
                        'action'     => 'selectEntities',
                    ),
                ),
            ),
            'apigility-doctrine-admin-create-resources' => array(
                'type'  => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/create-resources[/:moduleName]',
                    'defaults' => array(
                        'controller' => 'Apigility\Doctrine\Admin\Controller\App',
                        'action'     => 'createResources',
                    ),
                ),
            ),

            'apigility-doctrine-admin-done' => array(
                'type'  => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/done[/:moduleName][/:results]',
                    'defaults' => array(
                        'controller' => 'Apigility\Doctrine\Admin\Controller\App',
                        'action'     => 'done',
                    ),
                ),
            ),
        ),
    ),
);
