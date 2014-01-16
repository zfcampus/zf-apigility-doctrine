<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'service_manager' => array(
        'abstract_factories' => array(
            'ZF\Apigility\Doctrine\Server\Resource\DoctrineResourceFactory',
        ),
    ),

    'hydrators' => array(
        'abstract_factories' => array(
            'ZF\Apigility\Doctrine\Server\Hydrator\DoctrineHydratorFactory',
        )
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

    'router' => array(
        'routes' => array(
            'zf-apigility-doctrine-service' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/admin/api/module[/:name]/doctrine[/:controller_service_name]',
                    'defaults' => array(
                        'controller' => 'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService',
                    ),
                ),
                'may_terminate' => true,
            ),
        ),
    ),

    'zf-content-negotiation' => array(
        'controllers' => array(
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => 'HalJson',
        ),
        'accept-whitelist' => array(
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => array(
                'application/json',
                'application/*+json',
            ),
        ),
        'content-type-whitelist' => array(
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => array(
                'application/json',
                'application/*+json',
            ),
        ),
    ),

    'zf-hal' => array(
        'metadata_map' => array(
            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity' => array(
                'hydrator'        => 'ArraySerializable',
                'route_identifier_name' => 'controller_service_name',
                'route_name'      => 'zf-apigility-doctrine-service',
            ),
        ),
    ),

    'zf-rest' => array(
        'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => array(
            'listener'                   => 'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource',
            'route_name'                 => 'zf-apigility-doctrine-service',
            'entity_class'               => 'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity',
            'route_identifier_name'      => 'controller_service_name',
            'resource_http_methods'      => array('GET', 'PATCH', 'DELETE'),
            'collection_http_methods'    => array('GET', 'POST'),
            'collection_name'            => 'rest',
            'collection_query_whitelist' => array('version'),
        ),
    ),
);
