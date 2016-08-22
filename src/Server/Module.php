<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server;

use Zend\ModuleManager\ModuleManager;
use ZF\Apigility\Doctrine\Server\Query\CreateFilter\QueryCreateFilterInterface;
use ZF\Apigility\Doctrine\Server\Query\Provider\QueryProviderInterface;

class Module
{
    /**
     * Returns configuration to merge with application configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/server.config.php';
    }

    /**
     * Module init
     *
     * @param ModuleManager $moduleManager
     */
    public function init(ModuleManager $moduleManager)
    {
        $sm = $moduleManager->getEvent()->getParam('ServiceManager');
        $serviceListener = $sm->get('ServiceListener');

        $serviceListener->addServiceManager(
            'ZfApigilityDoctrineQueryProviderManager',
            'zf-apigility-doctrine-query-provider',
            QueryProviderInterface::class,
            'getZfApigilityDoctrineQueryProviderConfig'
        );

        $serviceListener->addServiceManager(
            'ZfApigilityDoctrineQueryCreateFilterManager',
            'zf-apigility-doctrine-query-create-filter',
            QueryCreateFilterInterface::class,
            'getZfApigilityDoctrineQueryCreateFilterConfig'
        );
    }

    /**
     * Expected to return an array of modules on which the current one depends on
     *
     * @return array
     */
    public function getModuleDependencies()
    {
        return ['Phpro\DoctrineHydrationModule'];
    }
}
