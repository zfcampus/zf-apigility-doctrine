<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin;

use ZF\Apigility\Doctrine\Server;

return [
    'router' => [
        'routes' => [
            'zf-apigility-doctrine-rpc-service' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/apigility/api/module[/:name]/doctrine-rpc[/:controller_service_name]',
                    'defaults' => [
                        'controller' => Controller\DoctrineRpcService::class,
                    ],
                ],
                'may_terminate' => true,
            ],
            'zf-apigility-doctrine-service' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/apigility/api/module[/:name]/doctrine[/:controller_service_name]',
                    'defaults' => [
                        'controller' => Controller\DoctrineRestService::class,
                    ],
                ],
                'may_terminate' => true,
            ],
            'zf-apigility-doctrine-metadata-service' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/apigility/api/doctrine[/:object_manager_alias]/metadata[/:name]',
                    'defaults' => [
                        'controller' => Controller\DoctrineMetadataService::class,
                    ],
                ],
                'may_terminate' => true,
            ],
            'zf-apigility-doctrine-autodiscovery' => [
                'type' => 'segment',
                'options' => [
                    'route' => '/apigility/api/module/:name/:version/autodiscovery/doctrine/:object_manager_alias',
                    'defaults' => [
                        'controller' => Controller\DoctrineAutodiscovery::class,
                        'action' => 'discover',
                    ],
                ],
            ],
        ],
    ],

    'service_manager' => [
        'factories' => [
            Model\DoctrineAutodiscoveryModel::class      => Model\DoctrineAutodiscoveryModelFactory::class,
            Model\DoctrineMetadataServiceResource::class => Model\DoctrineMetadataServiceResourceFactory::class,
            Model\DoctrineRestServiceModelFactory::class => Model\DoctrineRestServiceModelFactoryFactory::class,
            Model\DoctrineRestServiceResource::class     => Model\DoctrineRestServiceResourceFactory::class,
            Model\DoctrineRpcServiceModelFactory::class  => Model\DoctrineRpcServiceModelFactoryFactory::class,
            Model\DoctrineRpcServiceResource::class      => Model\DoctrineRpcServiceResourceFactory::class,
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\DoctrineAutodiscovery::class => Controller\DoctrineAutodiscoveryControllerFactory::class,
        ],
    ],

    'zf-content-negotiation' => [
        'controllers' => [
            Controller\DoctrineAutodiscovery::class   => 'Json',
            Controller\DoctrineRestService::class     => 'HalJson',
            Controller\DoctrineRpcService::class      => 'HalJson',
            Controller\DoctrineMetadataService::class => 'HalJson',
        ],
        'accept-whitelist' => [
            Controller\DoctrineAutodiscovery::class => [
                'application/json',
                'application/*+json',
            ],
            Controller\DoctrineRpcService::class => [
                'application/json',
                'application/*+json',
            ],
            Controller\DoctrineRestService::class => [
                'application/json',
                'application/*+json',
            ],
            Controller\DoctrineMetadataService::class => [
                'application/json',
                'application/*+json',
            ],
        ],
        'content-type-whitelist' => [
            Controller\DoctrineAutodiscovery::class => [
                'application/json',
                'application/*+json',
            ],
            Controller\DoctrineRpcService::class => [
                'application/json',
                'application/*+json',
            ],
            Controller\DoctrineRestService::class => [
                'application/json',
                'application/*+json',
            ],
            Controller\DoctrineMetadataService::class => [
                'application/json',
                'application/*+json',
            ],
        ],
    ],

    'zf-hal' => [
        'metadata_map' => [
            Model\DoctrineRpcServiceEntity::class => [
                'hydrator'               => 'ArraySerializable',
                'route_identifier_name'  => 'controller_service_name',
                'entity_identifier_name' => 'controller_service_name',
                'route_name'             => 'zf-apigility-doctrine-rpc-service',
            ],
            Model\DoctrineRestServiceEntity::class => [
                'hydrator'               => 'ArraySerializable',
                'route_identifier_name'  => 'controller_service_name',
                'entity_identifier_name' => 'controller_service_name',
                'route_name'             => 'zf-apigility-doctrine-service',
            ],
            Model\DoctrineMetadataServiceEntity::class => [
                'hydrator'               => 'ArraySerializable',
                'entity_identifier_name' => 'name',
                'route_identifier_name'  => 'name',
                'route_name'             => 'zf-apigility-doctrine-metadata-service',
            ],
        ],
    ],

    'zf-rest' => [
        Controller\DoctrineRpcService::class => [
            'listener'                   => Model\DoctrineRpcServiceResource::class,
            'route_name'                 => 'zf-apigility-doctrine-rpc-service',
            'entity_class'               => Model\DoctrineRpcServiceEntity::class,
            'route_identifier_name'      => 'controller_service_name',
            'entity_http_methods'        => ['GET', 'POST', 'PATCH', 'DELETE'],
            'collection_http_methods'    => ['GET', 'POST'],
            'collection_name'            => 'doctrine-rpc',
            'collection_query_whitelist' => ['version'],
        ],
        Controller\DoctrineRestService::class => [
            'listener'                   => Model\DoctrineRestServiceResource::class,
            'route_name'                 => 'zf-apigility-doctrine-service',
            'entity_class'               => Model\DoctrineRestServiceEntity::class,
            'route_identifier_name'      => 'controller_service_name',
            'entity_http_methods'        => ['GET', 'POST', 'PATCH', 'DELETE'],
            'collection_http_methods'    => ['GET', 'POST'],
            'collection_name'            => 'doctrine',
            'collection_query_whitelist' => ['version'],
        ],
        Controller\DoctrineMetadataService::class => [
            'listener'                   => Model\DoctrineMetadataServiceResource::class,
            'route_name'                 => 'zf-apigility-doctrine-metadata-service',
            'entity_class'               => Model\DoctrineMetadataServiceEntity::class,
            'route_identifier_name'      => 'name',
            'entity_http_methods'        => ['GET'],
            'collection_http_methods'    => ['GET'],
            'collection_name'            => 'doctrine-metadata',
            'collection_query_whitelist' => ['version'],
        ],
    ],
    'zf-rpc' => [
        Controller\DoctrineAutodiscovery::class => [
            'http_methods' => ['GET'],
            'route_name'   => 'zf-apigility-doctrine-autodiscovery',
        ],
    ],
    'validator_metadata' => [
        Server\Validator\ObjectExists::class => [
            'entity_class' => 'string',
            'fields'       => 'string',
        ],
        Server\Validator\NoObjectExists::class => [
            'entity_class' => 'string',
            'fields'       => 'string',
        ],
    ],
];
