<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\ServiceManager\AbstractPluginManager;
use ZF\Rest\ResourceEvent;

interface QueryProviderInterface extends ObjectManagerAwareInterface
{
    /**
     * @param string $entityClass
     * @param array  $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    public function createQuery(ResourceEvent $event, $entityClass, $parameters);

    /**
     * This function is not necessary for any but fetch-all queries
     * In order to provide a single QueryProvider service this is
     * included in this interface.
     *
     * @param   $queryBuilder
     *
     * @return AdapterInterface
     */
    public function getPaginatedQuery($queryBuilder);

    /**
     * This function is not necessary for any but fetch-all queries
     * In order to provide a single QueryProvider service this is
     * included in this interface.
     *
     * @param   $entityClass
     *
     * @return int
     */
    public function getCollectionTotal($entityClass);
}
