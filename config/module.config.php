<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
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
            'SoliantConsulting\Apigility\Admin\Controller\App' => 'SoliantConsulting\Apigility\Admin\Controller\AppController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'soliantconsulting-apigility-admin' => array(
                'type'  => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin',
                    'defaults' => array(
                        'controller' => 'SoliantConsulting\Apigility\Admin\Controller\App',
                        'action'     => 'index',
                    ),
                ),
            ),
            'soliantconsulting-apigility-admin-create-module' => array(
                'type'  => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/create-module',
                    'defaults' => array(
                        'controller' => 'SoliantConsulting\Apigility\Admin\Controller\App',
                        'action'     => 'createModule',
                    ),
                ),
            ),
            'soliantconsulting-apigility-admin-select-entities' => array(
                'type'  => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/select-entities[/:moduleName][/:objectManagerAlias]',
                    'defaults' => array(
                        'controller' => 'SoliantConsulting\Apigility\Admin\Controller\App',
                        'action'     => 'selectEntities',
                    ),
                ),
            ),
            'soliantconsulting-apigility-admin-create-resources' => array(
                'type'  => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/create-resources[/:moduleName]',
                    'defaults' => array(
                        'controller' => 'SoliantConsulting\Apigility\Admin\Controller\App',
                        'action'     => 'createResources',
                    ),
                ),
            ),

            'soliantconsulting-apigility-admin-done' => array(
                'type'  => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/soliant-consulting/apigility/admin/done[/:moduleName][/:results]',
                    'defaults' => array(
                        'controller' => 'SoliantConsulting\Apigility\Admin\Controller\App',
                        'action'     => 'done',
                    ),
                ),
            ),
        ),
    ),
);
