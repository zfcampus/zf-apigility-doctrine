<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server\Query\Provider\Service;

use Zend\Mvc\Service\AbstractPluginManagerFactory;

class QueryProviderManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = QueryProviderManager::class;
}
