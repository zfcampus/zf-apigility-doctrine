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
        ),
    ),
    'zf-orm-collection-filter' => array(
        'invokables' => array(
            'innerjoin' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\InnerJoin',
        ),
    ),
);
