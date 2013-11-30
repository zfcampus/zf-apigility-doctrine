<?php

namespace SoliantConsulting\Apigility\Server\Collection\Query;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use Zend\Paginator\Paginator;
use ZF\Hal\Collection;


/**
 * Class FetchAllOrmQuery
 *
 * @package SoliantConsulting\Apigility\Server\Resource\Query
 */
class FetchAllOrmQuery
    implements ObjectManagerAwareInterface, ApigilityFetchAllQuery
{

    use ProvidesObjectManager;

    protected $collectionClass;

    public function getCollectionClass()
    {
        return $this->collectionClass;
    }

    public function setCollectionClass($value)
    {
        $this->collectionClass = $value;
        return $this;
    }

    /**
     * @param string $entityClass
     * @param array $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    public function createQuery($entityClass, array $parameters)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();

        $queryBuilder->select('row')
            ->from($entityClass, 'row');

        $totalCountQueryBuilder = clone $queryBuilder;

        // Orderby
        if (!isset($parameters['orderBy'])) {
            $parameters['orderBy'] = array('id' => 'asc');
        }
        foreach($parameters['orderBy'] as $fieldName => $sort) {
            $queryBuilder->addOrderBy("row.$fieldName", $sort);
        }

        // Add query parameters
        if (isset($parameters['query'])) {
            foreach ($parameters['query'] as $option) {
                // Allow and/or queries
                if (isset($option['where'])) {
                    if ($option['where'] == 'and') {
                        $queryType = 'andWhere';
                    } elseif ($option['where'] == 'or') {
                        $queryType = 'orWhere';
                    }
                }

                if (!isset($queryType)) {
                    $queryType = 'andWhere';
                }

                switch (strtolower($option['type'])) {
                    case 'eq':
                        // field, value
                        $queryBuilder->$queryType($queryBuilder->expr()->eq('row.' . $option['field'], $option['value']));
                        break;

                    case 'neq':
                        $queryBuilder->$queryType($queryBuilder->expr()->neq('row.' . $option['field'], $option['value']));
                        break;

                    case 'lt':
                        $queryBuilder->$queryType($queryBuilder->expr()->lt('row.' . $option['field'], $option['value']));
                        break;

                    case 'lte':
                        $queryBuilder->$queryType($queryBuilder->expr()->lte('row.' . $option['field'], $option['value']));
                        break;

                    case 'gt':
                        $queryBuilder->$queryType($queryBuilder->expr()->gt('row.' . $option['field'], $option['value']));
                        break;

                    case 'gte':
                        $queryBuilder->$queryType($queryBuilder->expr()->gte('row.' . $option['field'], $option['value']));
                        break;

                    case 'isnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->isNull('row.' . $option['field']));
                        break;

                    case 'isnotnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->isNotNull('row.' . $option['field']));
                        break;

                    case 'in':
                        $queryBuilder->$queryType($queryBuilder->expr()->in('row.' . $option['field'], $option['values']));
                        break;

                    case 'notin':
                        $queryBuilder->$queryType($queryBuilder->expr()->notIn('row.' . $option['field'], $option['values']));
                        break;

                    case 'like':
                        $queryBuilder->$queryType($queryBuilder->expr()->like('row.' . $option['field'], $queryBuilder->expr()->literal($option['value'])));
                        break;

                    case 'notlike':
                        $queryBuilder->$queryType($queryBuilder->expr()->notLike('row.' . $option['field'], $queryBuilder->expr()->literal($option['value'])));
                        break;

                    case 'between':
                        // field, from, to
                        $queryBuilder->$queryType($queryBuilder->expr()->between('row.' . $option['field'], $option['from'], $option['to']));
                        break;

                    case 'decimation':
                        // field, value
                        $md5 = 'a' . md5(uniqid()); # parameter cannot start with #
                        $queryBuilder->$queryType("mod(row." . $option['field'] . ", :$md5) = 0")
                                     ->setParameter($md5, $option['value']);
                        break;

                    default:
                        break;
                }
            }
        }

        return $queryBuilder;
    }

    /**
     * @param       $entityClass
     * @param array $parameters
     *
     * @return HalCollection
     */
    public function getPaginatedQuery($entityClass, array $parameters)
    {
        $queryBuilder = $this->createQuery($entityClass, $parameters);

        // Build collection and paginator
        $collectionClass = $this->getCollectionClass();
        $collection = new $collectionClass($queryBuilder->getQuery(), false);
        $paginator = new Paginator($collection);

        $totalCountQueryBuilder = $this->getObjectManager()->createQueryBuilder();
        $totalCountQueryBuilder->select('row')
            ->from($entityClass, 'row');

        // Total count collection (is this the right use of total?)
        $totalCountCollection = new $collectionClass($totalCountQueryBuilder->getQuery(), false);

        // Setup HAL collection
        $halCollection = new Collection($paginator);
        $halCollection->setAttributes(array(
            'count' => sizeof($collection),
            'total' => sizeof($totalCountCollection),
        ));
        $halCollection->setCollectionRouteOptions(array(
            'query' => $parameters
        ));

        return $halCollection;
    }
}