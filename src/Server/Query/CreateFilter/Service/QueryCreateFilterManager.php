<?php

namespace ZF\Apigility\Doctrine\Server\Query\CreateFilter\Service;

use ZF\Apigility\Doctrine\Server\Query\CreateFilter\QueryCreateFilterInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;

class QueryCreateFilterManager extends AbstractPluginManager
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
        if ($plugin instanceof QueryCreateFilterInterface) {
            // we're okay
            return;
        }

        // @codeCoverageIgnoreStart
        throw new Exception\RuntimeException(
            sprintf(
                'Plugin of type %s is invalid; must implement QueryCreateFilterInterface',
                (is_object($plugin) ? get_class($plugin) : gettype($plugin))
            )
        );
        // @codeCoverageIgnoreEnd
    }
}
