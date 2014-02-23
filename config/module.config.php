<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */


return array(
    'service_manager' => array(
        'invokables' => array(
            'ZF\\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionLink' => 'ZF\\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionLink',
        ),
        'abstract_factories' => array(
            'ZF\Apigility\Doctrine\Server\Resource\DoctrineResourceFactory',
        ),
        'factories' => array(
            'ZfOrmCollectionFilterManager' => 'ZF\Apigility\Doctrine\Server\Collection\Service\ORMFilterManagerFactory',
            'ZfOdmCollectionFilterManager' => 'ZF\Apigility\Doctrine\Server\Collection\Service\ODMFilterManagerFactory',
        ),
    ),

    'zf-orm-collection-filter' => array(
        'invokables' => array(
            'eq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\Equals',
            'neq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\NotEquals',
            'lt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\LessThan',
            'lte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\LessThanOrEquals',
            'gt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\GreaterThan',
            'gte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\GreaterThanOrEquals',
            'isnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\IsNull',
            'isnotnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\IsNotNull',
            'in' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\In',
            'notin' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\NotIn',
            'between' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\Between',
            'like' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\Like',
            'notlike' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\Like',
        ),
    ),

    'zf-odm-collection-filter' => array(
        'invokables' => array(
            'eq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Equals',
            'neq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\NotEquals',
            'lt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\LessThan',
            'lte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\LessThanOrEquals',
            'gt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\GreaterThan',
            'gte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\GreaterThanOrEquals',
            'isnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\IsNull',
            'isnotnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\IsNotNull',
            'in' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\In',
            'notin' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\NotIn',
            'between' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Between',
            'like' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Like',
            'regex' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Regex',
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
            'zf-apigility-doctrine-metadata-service' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/admin/api/doctrine[/:object_manager_alias]/metadata[/:name]',
                    'defaults' => array(
                        'controller' => 'ZF\Apigility\Doctrine\Admin\Controller\DoctrineMetadataService',
                    ),
                ),
                'may_terminate' => true,
            ),
        ),
    ),

    'zf-content-negotiation' => array(
        'controllers' => array(
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => 'HalJson',
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineMetadataService' => 'HalJson',
        ),
        'accept-whitelist' => array(
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => array(
                'application/json',
                'application/*+json',
            ),
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineMetadataService' => array(
                'application/json',
                'application/*+json',
            ),
        ),
        'content-type-whitelist' => array(
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => array(
                'application/json',
                'application/*+json',
            ),
            'ZF\Apigility\Doctrine\Admin\Controller\DoctrineMetadataService' => array(
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
                'entity_identifier_name' => 'controller_service_name',
                'route_name'      => 'zf-apigility-doctrine-service',
            ),
            'ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceEntity' => array(
                'hydrator'        => 'ArraySerializable',
                'entity_identifier_name' => 'name',
                'route_identifier_name'      => 'name',
                'route_name'      => 'zf-apigility-doctrine-metadata-service',
            ),
        ),
    ),

    'zf-rest' => array(
        'ZF\Apigility\Doctrine\Admin\Controller\DoctrineRestService' => array(
            'listener'                   => 'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource',
            'route_name'                 => 'zf-apigility-doctrine-service',
            'entity_class'               => 'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity',
            'route_identifier_name'      => 'controller_service_name',
            'resource_http_methods'      => array('GET', 'POST', 'PATCH', 'DELETE'),
            'collection_http_methods'    => array('GET', 'POST'),
            'collection_name'            => 'doctrine',
            'collection_query_whitelist' => array('version'),
        ),
        'ZF\Apigility\Doctrine\Admin\Controller\DoctrineMetadataService' => array(
            'listener'                   => 'ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceResource',
            'route_name'                 => 'zf-apigility-doctrine-metadata-service',
            'entity_class'               => 'ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceEntity',
            'route_identifier_name'      => 'name',
            'resource_http_methods'      => array('GET'),
            'collection_http_methods'    => array('GET'),
            'collection_name'            => 'doctrine-metadata',
            'collection_query_whitelist' => array('version'),
        ),
    ),
);
