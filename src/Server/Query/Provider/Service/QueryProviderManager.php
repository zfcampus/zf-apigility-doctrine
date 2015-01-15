<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider\Service;

use ZF\Apigility\Doctrine\Server\Query\Provider\QueryProviderInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;

class QueryProviderManager extends AbstractPluginManager
{
    protected $invokableClasses = array();

    /**
     * @param mixed $plugin
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof QueryProviderInterface) {
            // we're okay
            return;
        }

        // @codeCoverageIgnoreStart
        throw new Exception\RuntimeException(
            sprintf(
                'Plugin of type %s is invalid; must implement QueryProviderInterface',
                (is_object($plugin) ? get_class($plugin) : gettype($plugin))
            )
        );
        // @codeCoverageIgnoreEnd
    }
}
