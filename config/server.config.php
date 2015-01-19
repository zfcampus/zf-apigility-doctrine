<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'service_manager' => array(
        'invokables' => array(
            'ZF\\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionExtract' =>
                'ZF\\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionExtract',
        ),
        'abstract_factories' => array(
            'ZF\Apigility\Doctrine\Server\Resource\DoctrineResourceFactory',
        ),
        'factories' => array(
            'ZfApigilityDoctrineQueryProviderManager' =>
                'ZF\Apigility\Doctrine\Server\Query\Provider\Service\QueryProviderManagerFactory',
        ),
    ),

    'zf-apigility-doctrine-query-provider' => array(
        'invokables' => array(
            'default_orm' => 'ZF\Apigility\Doctrine\Server\Query\Provider\DefaultOrm',
            'default_odm' => 'ZF\Apigility\Doctrine\Server\Query\Provider\DefaultOdm',
        )
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            'zf-apigility-doctrine' => __DIR__ . '/../view',
        ),
    ),

    'validators' => array(
        'factories' => array(
            'ZF\Apigility\Doctrine\Server\Validator\NoObjectExists' =>
                'ZF\Apigility\Doctrine\Server\Validator\NoObjectExistsFactory',
            'ZF\Apigility\Doctrine\Server\Validator\ObjectExists' =>
                'ZF\Apigility\Doctrine\Server\Validator\ObjectExistsFactory',
        ),
    ),
);
