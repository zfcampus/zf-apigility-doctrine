<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

return [
    'doctrine' => [
        'connection' => [
            'odm_default' => [
                'server' => (getenv('MONGO_HOST')) ? getenv('MONGO_HOST') : 'localhost',
                'port' => (getenv('MONGO_PORT')) ? getenv('MONGO_PORT') : '27017',
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
