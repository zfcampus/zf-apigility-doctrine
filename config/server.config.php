<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'service_manager' => array(
        'invokables' => array(
            'ZF\\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionLink' => 'ZF\\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionLink',
        ),
        'abstract_factories' => array(
            'ZF\Apigility\Doctrine\Server\Resource\DoctrineResourceFactory',
        ),
        'factories' => array(
            'ZfOrmCollectionFilterManager' => 'ZF\Apigility\Doctrine\Server\Collection\Service\ORMFilterManagerFactory',
            'ZfOdmCollectionFilterManager' => 'ZF\Apigility\Doctrine\Server\Collection\Service\ODMFilterManagerFactory',
            'ZfCollectionQueryManager' => 'ZF\Apigility\Doctrine\Server\Collection\Service\QueryManagerFactory',
        ),
    ),

    'zf-collection-query' => array(
        'invokables' => array(
            'default-orm-query' => 'ZF\Apigility\Doctrine\Server\Collection\Query\FetchAllOrmQuery',
            'default-odm-query' => 'ZF\Apigility\Doctrine\Server\Collection\Query\FetchAllOdmQuery',
        )
    ),

    'zf-orm-collection-filter' => array(
        'invokables' => array(
            'eq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\Equals',
            'neq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\NotEquals',
            'lt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\LessThan',
            'lte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\LessThanOrEquals',
            'gt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\GreaterThan',
            'gte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\GreaterThanOrEquals',
            'isnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\IsNull',
            'isnotnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\IsNotNull',
            'in' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\In',
            'notin' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\NotIn',
            'between' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\Between',
            'like' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\Like',
            'notlike' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\NotLike',
            'orx' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\OrX',
            'andx' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\AndX',
        ),
    ),

    'zf-odm-collection-filter' => array(
        'invokables' => array(
            'eq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Equals',
            'neq' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\NotEquals',
            'lt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\LessThan',
            'lte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\LessThanOrEquals',
            'gt' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\GreaterThan',
            'gte' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\GreaterThanOrEquals',
            'isnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\IsNull',
            'isnotnull' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\IsNotNull',
            'in' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\In',
            'notin' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\NotIn',
            'between' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Between',
            'like' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Like',
            'regex' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\Regex',
        ),
    ),

    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../asset',
            ),
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
