<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider;

use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOdmAdapter;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;

class DefaultOdm extends AbstractQueryProvider
{
    /**
     * {@inheritDoc}
     */
    public function createQuery(ResourceEvent $event, $entityClass, $parameters)
    {
        /**
         * @var \Doctrine\Odm\MongoDB\Query\Builder $queryBuilder
         */
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->find($entityClass);

        return $queryBuilder;
    }

    /**
     * @param   $queryBuilder
     *
     * @return DoctrineOdmAdapter
     */
    public function getPaginatedQuery($queryBuilder)
    {
        $adapter = new DoctrineOdmAdapter($queryBuilder);

        return $adapter;
    }

    /**
     * @param   $entityClass
     *
     * @return int
     */
    public function getCollectionTotal($entityClass)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->find($entityClass);
        $count = $queryBuilder->getQuery()->execute()->count();

        return $count;
    }
}
