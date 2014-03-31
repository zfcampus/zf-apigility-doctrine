<?php

namespace DbMongo;

return [
    'doctrine' => [
        'driver' => [
            'odm_driver' => [
                'class' => 'Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver',
                'paths' => [__DIR__ . '/yml'],
            ],
            'odm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Document' => 'odm_driver'
                ]
            ],
        ],
    ],
];
