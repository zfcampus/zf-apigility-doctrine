<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Query;

use DoctrineModule\Persistence\ProvidesObjectManager;
use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOdmAdapter;
use Zend\ServiceManager\AbstractPluginManager;
use ZF\ApiProblem\ApiProblem;

class FetchAllOdmQuery implements ApigilityFetchAllQuery
{
    use ProvidesObjectManager;

    /**
     * {@inheritDoc}
     */
    public function createQuery($entityClass, $parameters)
    {
        /** @var \Doctrine\Odm\MongoDB\Query\Builder $queryBuilder */
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->find($entityClass);

        // Orderby
        if (!isset($parameters['orderBy'])) {
            $parameters['orderBy'] = array('id' => 'asc');
        }
        foreach ($parameters['orderBy'] as $fieldName => $sort) {
            $queryBuilder->sort($fieldName, $sort);
        }

        // Get metadata for type casting
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $metadata = (array) $cmf->getMetadataFor($entityClass);

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
