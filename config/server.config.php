<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server;

use Zend\ServiceManager\Factory\InvokableFactory;

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
        'aliases' => [
            'default_odm' => Query\Provider\DefaultOdm::class,
            'default_orm' => Query\Provider\DefaultOrm::class,
        ],
        'factories' => [
            Query\Provider\DefaultOdm::class => InvokableFactory::class,
            Query\Provider\DefaultOrm::class => InvokableFactory::class,
        ],
    ],

    'zf-apigility-doctrine-query-create-filter' => [
        'aliases' => [
            'default' => Query\CreateFilter\DefaultCreateFilter::class,
        ],
        'factories' => [
            Query\CreateFilter\DefaultCreateFilter::class => InvokableFactory::class,
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
            Validator\ObjectExists::class   => Validator\ObjectExistsFactory::class,
        ],
    ],
];
