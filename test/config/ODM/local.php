<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

return [
    'doctrine' => [
        'connection' => [
            'odm_default' => [
                'server' => 'localhost',
                'port' => '27017',
                'user' => '',
                'password' => '',
                'dbname' => 'zf_apigility_doctrine_server_test',
            ],
        ],
        'configuration' => [
            'odm_default' => [
                'hydrator_dir' => __DIR__ . '/../../data/DoctrineMongoODMModule/Hydrator',
            ],
        ],
    ],
];
