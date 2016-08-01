<?php

namespace ZFTestApigilityDb;

return [
    'service_manager' => [
        'invokables' => [
            'Artist_aggregate_listener' =>
                'ZFTestApigilityDb\EventListener\ArtistAggregateListener',
        ],
    ],
    'zf-apigility-doctrine-query-provider' => [
        'invokables' => [
            'Artist_default' => 'ZFTestApigilityDb\Query\Provider\Artist\DefaultQueryProvider',
            'Artist_update' => 'ZFTestApigilityDb\Query\Provider\Artist\UpdateQueryProvider',
        ]
    ],
    'zf-apigility-doctrine-query-create-filter' => [
        'invokables' => [
            'Artist' => 'ZFTestApigilityDb\Query\CreateFilter\ArtistCreateFilter',
        ]
    ],
    'zf-apigility' => [
        'doctrine-connected' => [
            'ZFTestApigilityDbApi\\V1\\Rest\\Artist\\ArtistResource' => [
                'query_create_filter' => 'Artist',
                'query_providers' => [
                    'default' => 'Artist_default',
                    'update' => 'Artist_update',
                ],
                'listeners' => [
                    'Artist_aggregate_listener',
                ],
            ],
        ],
    ],

    'doctrine' => [
        'driver' => [
           'db_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
                'paths' => [__DIR__ . '/xml'],
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => 'db_driver',
                ],
            ],
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'json_exceptions'          => [
            'display'    => true,
            'ajax_only'  => true,
            'show_trace' => true
        ],

        'doctype'            => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map'       => [],
        'strategies'         => [
            'ViewJsonStrategy',
        ],
    ],
];
