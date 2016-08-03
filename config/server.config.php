<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server;

return [
    'service_manager' => [
        'abstract_factories' => [
            Resource\DoctrineResourceFactory::class,
        ],
        'factories' => [
            'ZfApigilityDoctrineQueryProviderManager'
                => Query\Provider\Service\QueryProviderManagerFactory::class,
            'ZfApigilityDoctrineQueryCreateFilterManager'
                => Query\CreateFilter\Service\QueryCreateFilterManagerFactory::class,
        ],
    ],

    'zf-apigility-doctrine-query-provider' => [
        'invokables' => [
            'default_orm' => Query\Provider\DefaultOrm::class,
            'default_odm' => Query\Provider\DefaultOdm::class,
        ],
    ],

    'zf-apigility-doctrine-query-create-filter' => [
        'invokables' => [
            'default' => Query\CreateFilter\DefaultCreateFilter::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'zf-apigility-doctrine' => __DIR__ . '/../view',
        ],
    ],

    'validators' => [
        'factories' => [
            Validator\NoObjectExists::class => Validator\NoObjectExistsFactory::class,
            Validator\ObjectExists::class => Validator\ObjectExistsFactory::class,
        ],
    ],
];
