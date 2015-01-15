<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider\Fetch;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\ServiceManager\AbstractPluginManager;

interface FetchQueryProviderInterface extends ObjectManagerAwareInterface
{
    /**
     * @param string $entityClass
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    public function createQuery($entityClass);
}
