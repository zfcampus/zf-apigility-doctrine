<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider\FetchAll;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\ServiceManager\AbstractPluginManager;

interface FetchAllQueryProviderInterface extends ObjectManagerAwareInterface
{
    /**
     * @param string $entityClass
     * @param array  $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    public function createQuery($entityClass, $parameters);

    /**
     * @param   $queryBuilder
     *
     * @return AdapterInterface
     */
    public function getPaginatedQuery($queryBuilder);

    /**
     * @param   $entityClass
     *
     * @return int
     */
    public function getCollectionTotal($entityClass);
}
