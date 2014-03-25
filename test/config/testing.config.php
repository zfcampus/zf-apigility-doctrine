<?php

return array(
    'doctrine' => array(
        'connection' => array(
            'orm_default' => array(
                'configuration' => 'orm_default',
                'eventmanager'  => 'orm_default',
                'driverClass'   => 'Doctrine\DBAL\Driver\PDOSqlite\Driver',
                'params' => array(
                    'memory' => true,
                ),
            ),
            'odm_default' => array(
                'server' => 'localhost',
#                'port' => '10143',
                'user' => '',
                'password' => '',
                'dbname' => 'zf_apigility_doctrine_server_test',
            ),
        ),
    ),
);