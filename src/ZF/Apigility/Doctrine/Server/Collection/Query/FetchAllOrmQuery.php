<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Query;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOrmAdapter;
use Zend\Paginator\Adapter\AdapterInterface;


/**
 * Class FetchAllOrmQuery
 *
 * @package ZF\Apigility\Doctrine\Server\Resource\Query
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
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->eq('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['value']);
                        break;

                    case 'neq':
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->neq('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['value']);
                        break;

                    case 'lt':
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->lt('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['value']);
                        break;

                    case 'lte':
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->lte('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['value']);
                        break;

                    case 'gt':
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->gt('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['value']);
                        break;

                    case 'gte':
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->gte('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['value']);
                        break;

                    case 'isnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->isNull('row.' . $option['field']));
                        break;

                    case 'isnotnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->isNotNull('row.' . $option['field']));
                        break;

                    case 'in':
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->in('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['values']);
                        break;

                    case 'notin':
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType($queryBuilder->expr()->notIn('row.' . $option['field'], ":$parameter"));
                        $queryBuilder->setParameter($parameter, $option['values']);
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
                        $parameter = uniqid('a');
                        $queryBuilder->$queryType("mod(row." . $option['field'] . ", :$parameter) = 0")
                                     ->setParameter($parameter, $option['value']);
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
     * @return AdapterInterface
     */
    public function getPaginatedQuery($entityClass, array $parameters)
    {
        $queryBuilder = $this->createQuery($entityClass, $parameters);
        $adapter = new DoctrineOrmAdapter($queryBuilder->getQuery(), false);
        return $adapter;
    }

    /**
     * @param       $entityClass
     *
     * @return int
     */
    public function getCollectionTotal($entityClass)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();

        $queryBuilder->select('count(row.id)')
            ->from($entityClass, 'row');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
