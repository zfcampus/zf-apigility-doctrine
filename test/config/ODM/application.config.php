<?php

return array(
    'modules' => array(
        'DoctrineModule',
        'DoctrineMongoODMModule',
        'Phpro\DoctrineHydrationModule',
        'ZF\Apigility',
        'ZF\Apigility\Admin',
        'ZF\Hal',
        'ZF\ContentNegotiation',
        'ZF\Rest',
        'ZF\Rpc',
        'ZF\Configuration',
        'ZF\Versioning',
        'ZF\ApiProblem',
        'ZF\Apigility\Doctrine\Admin',
        'ZF\Apigility\Doctrine\Server',
        'ZFTestApigilityGeneral',
        'ZFTestApigilityDbMongo',
        'ZFTestApigilityDbMongoApi',
    ),
    'module_listener_options' => array(
        'config_glob_paths' => array(
            __DIR__ . '/local.php',
        ),
        'module_paths' => array(
            'ZFTestApigilityGeneral' => __DIR__ . '/../../assets/module/General',
            'ZFTestApigilityDbMongo' => __DIR__ . '/../../assets/module/DbMongo',
            'ZFTestApigilityDbMongoApi' => __DIR__ . '/../../assets/module/DbMongoApi',
        ),
    ),
);
