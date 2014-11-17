<?php

return array(
    'modules' => array(
        'DoctrineModule',
        'DoctrineORMModule',
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
        'ZFTestApigilityDb',
        'ZFTestApigilityDbApi',
    ),
    'module_listener_options' => array(
        'config_glob_paths' => array(
            __DIR__ . '/local.php',
        ),
        'module_paths' => array(
            'ZFTestApigilityGeneral' => __DIR__ . '/../../assets/module/General',
            'ZFTestApigilityDb' => __DIR__ . '/../../assets/module/Db',
            'ZFTestApigilityDbApi' => __DIR__ . '/../../assets/module/DbApi',
        ),
    ),
);
