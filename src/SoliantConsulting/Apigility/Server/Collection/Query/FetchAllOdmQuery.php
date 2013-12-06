<?php

namespace SoliantConsulting\Apigility\Server\Collection\Query;

use DoctrineModule\Persistence\ProvidesObjectManager;
use SoliantConsulting\Apigility\Server\Paginator\Adapter\DoctrineOdmAdapter;

class FetchAllOdmQuery implements ApigilityFetchAllQuery
{

    use ProvidesObjectManager;

    /**
     * {@inheritDoc}
     */
    public function createQuery($entityClass, array $parameters)
    {
        /** @var \Doctrine\Odm\MongoDB\Query\Builder $queryBuilder */
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->find($entityClass);

        // Orderby
        if (!isset($parameters['orderBy'])) {
            $parameters['orderBy'] = array('id' => 'asc');
        }
        foreach($parameters['orderBy'] as $fieldName => $sort) {
            $queryBuilder->sort($fieldName, $sort);
        }

        // Filter:
        if (isset($parameters['query'])) {
            foreach ($parameters['query'] as $option) {
                $queryType = 'addAnd';
                if (isset($option['where'])) {
                    if ($option['where'] == 'and') {
                        $queryType = 'addAnd';
                    } elseif ($option['where'] == 'or') {
                        $queryType = 'addOr';
                    }
                }

                switch (strtolower($option['type'])) {
                    case 'eq':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->equals($option['value']));
                        break;

                    case 'neq':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->notEqual($option['value']));
                        break;

                    case 'lt':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->lt($option['value']));
                        break;

                    case 'lte':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->lte($option['value']));
                        break;

                    case 'gt':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->gt($option['value']));
                        break;

                    case 'gte':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->gte($option['value']));
                        break;

                    case 'isnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->exists(false));
                        break;

                    case 'isnotnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->exists(true));
                        break;

                    case 'in':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->in($option['values']));
                        break;

                    case 'notin':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->notIn($option['values']));
                        break;

                    case 'between':
                        // field, from, to
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->range($option['from'], $option['to']));
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
     * @return DoctrineOdmAdapter
     */
    public function getPaginatedQuery($entityClass, array $parameters)
    {
        $queryBuilder = $this->createQuery($entityClass, $parameters);
        $adapter = new DoctrineOdmAdapter($queryBuilder);
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
        $queryBuilder->find($entityClass);
        $count = $queryBuilder->getQuery()->execute()->count();

        return $count;
    }

}