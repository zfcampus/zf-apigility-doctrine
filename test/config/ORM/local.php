<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016-2018 Zend Technologies USA Inc. (http://www.zend.com)
 */

use ZFTestApigilityDb\Type\RevType;

return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'configuration' => 'orm_default',
                'eventmanager'  => 'orm_default',
                'driverClass'   => \Doctrine\DBAL\Driver\PDOSqlite\Driver::class,
                'params' => [
                    'memory' => true,
                ],
            ],
        ],
        'configuration' => [
            'orm_default' => [
                'types' => [
                    RevType::NAME => RevType::class,
                ],
            ],
        ],
    ],
];
