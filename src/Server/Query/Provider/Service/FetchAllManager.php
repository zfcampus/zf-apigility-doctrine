<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider\Service;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;
use ZF\Apigility\Doctrine\Server\Query\Provider\FetchAll\FetchAllQueryProviderInterface;

class FetchAllManager extends AbstractPluginManager
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
        if ($plugin instanceof FetchAllQueryProviderInterface) {
            // we're okay
            return;
        }

        // @codeCoverageIgnoreStart
        throw new Exception\RuntimeException(
            sprintf(
                'Plugin of type %s is invalid; must implement FetchAllQueryProviderInterface',
                (is_object($plugin) ? get_class($plugin) : gettype($plugin))
            )
        );
        // @codeCoverageIgnoreEnd
    }
}
