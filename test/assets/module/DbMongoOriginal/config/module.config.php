<?php

namespace ZFTestApigilityDbMongo;

return array(
    'doctrine' => array(
        'driver' => array(
            'odm_driver' => array(
                'class' => 'Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver',
                'paths' => array(__DIR__ . '/yml'),
            ),
            'odm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Document' => 'odm_driver',
                ),
            ),
        ),
    ),
);
