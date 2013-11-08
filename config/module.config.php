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
        ),
    ),
);
