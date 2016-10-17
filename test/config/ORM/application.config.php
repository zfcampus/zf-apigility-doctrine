<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

return [
    'modules' => [
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
    ],
    'module_listener_options' => [
        'config_glob_paths' => [
            __DIR__ . '/local.php',
        ],
        'module_paths' => [
            'ZFTestApigilityGeneral' => __DIR__ . '/../../assets/module/ZFTestApigilityGeneral',
            'ZFTestApigilityDb' => __DIR__ . '/../../assets/module/ZFTestApigilityDb',
            'ZFTestApigilityDbApi' => __DIR__ . '/../../assets/module/ZFTestApigilityDbApi',
        ],
    ],
];
