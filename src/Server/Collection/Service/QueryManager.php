<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Service;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;
use ZF\Apigility\Doctrine\Server\Collection\Query\ApigilityFetchAllQuery;

class QueryManager extends AbstractPluginManager
{
    protected $invokableClasses = array();

    /**
     * @param mixed $filter
     *
     * @return void
     * @throws Exception\RuntimeException
     */
    public function validatePlugin($filter)
    {
        if ($filter instanceof ApigilityFetchAllQuery) {
            // we're okay
            return;
        }

        // @codeCoverageIgnoreStart
        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement ApigilityFetchAllQuery',
            (is_object($filter) ? get_class($filter) : gettype($filter))
        ));
        // @codeCoverageIgnoreEnd
    }
}
