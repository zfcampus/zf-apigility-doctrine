<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider\Service;

use Zend\Mvc\Service\AbstractPluginManagerFactory;

class QueryProviderManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = 'ZF\Apigility\Doctrine\Server\Query\Provider\Service\QueryProviderManager';
}
