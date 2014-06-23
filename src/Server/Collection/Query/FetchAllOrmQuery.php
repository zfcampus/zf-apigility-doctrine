<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Query;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOrmAdapter;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\ServiceManager\AbstractPluginManager;
use ZF\ApiProblem\ApiProblem;

/**
 * Class FetchAllOrmQuery
 *
 * @package ZF\Apigility\Doctrine\Server\Resource\Query
 */
class FetchAllOrmQuery
    implements ObjectManagerAwareInterface, ApigilityFetchAllQuery
{

    use ProvidesObjectManager;

    protected $filterManager;

    public function setFilterManager(AbstractPluginManager $filterManager)
    {
        $this->filterManager = $filterManager;

        return $this;
    }

    public function getFilterManager()
    {
        return $this->filterManager;
    }

    /**
     * @param string $entityClass
     * @param array  $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    public function createQuery($entityClass, $parameters)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();

        $queryBuilder->select('row')
            ->from($entityClass, 'row');

        // Get metadata for type casting
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $entityMetaData = $cmf->getMetadataFor($entityClass);
        $metadata = (array) $entityMetaData;
        // Orderby
        if (!isset($parameters['orderBy'])) {
            $parameters['orderBy'] = array($entityMetaData->getIdentifier()[0] => 'asc');
        }
        foreach ($parameters['orderBy'] as $fieldName => $sort) {
            $queryBuilder->addOrderBy("row.$fieldName", $sort);
        }

        // Run filters on query
        if (isset($parameters['query'])) {
            foreach ($parameters['query'] as $option) {
                if (!isset($option['type']) or !$option['type']) {
                // @codeCoverageIgnoreStart
                    return new ApiProblem(500, 'Array element "type" is required for all filters');
                }
                // @codeCoverageIgnoreEnd

                try {
                    $filter = $this->getFilterManager()->get(strtolower($option['type']));
                } catch (\Zend\ServiceManager\Exception\ServiceNotFoundException $e) {
                // @codeCoverageIgnoreStart
                    return new ApiProblem(500, $e->getMessage());
                }
                // @codeCoverageIgnoreEnd
                $filter->filter($queryBuilder, $metadata, $option);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param   $queryBuilder
     *
     * @return AdapterInterface
     */
    public function getPaginatedQuery($queryBuilder)
    {
        $adapter = new DoctrineOrmAdapter($queryBuilder->getQuery(), false);

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
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $entityMetaData = $cmf->getMetadataFor($entityClass);

        $queryBuilder->select('count(row.' . $entityMetaData->getIdentifier()[0] . ')')
            ->from($entityClass, 'row');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
