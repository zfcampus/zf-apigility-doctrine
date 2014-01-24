<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Query;

use DoctrineModule\Persistence\ProvidesObjectManager;
use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOdmAdapter;

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

        // Get metadata for type casting
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $metadata = (array)$cmf->getMetadataFor($entityClass);

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

                // Type cast value
                if(isset($metadata['fieldMappings'][$option['field']]['type'])) {
                    switch ($metadata['fieldMappings'][$option['field']]['type']) {
                        case 'int':
                            settype($option['value'], 'integer');
                            break;
                        case 'boolean':
                            settype($option['value'], 'boolean');
                            break;
                        case 'float':
                            settype($option['value'], 'float');
                            break;
                        case 'string':
                            settype($option['value'], 'string');
                            break;
                        case 'bin_data_custom':
                            break;
                        case 'bin_data_func':
                            break;
                        case 'bin_data_md5':
                            break;
                        case 'bin_data':
                            break;
                        case 'bin_data_uuid':
                            break;
                        case 'collection':
                            break;
                        case 'custom_id':
                            break;
                        case 'date':
                            break;
                        case 'file':
                            break;
                        case 'hash':
                            break;
                        case 'id':
                            break;
                        case 'increment':
                            break;
                        case 'key':
                            break;
                        case 'object_id':
                            break;
                        case 'raw_type':
                            break;
                        case 'timestamp':
                            break;
                        default:
                            break;
                    }
                }

                switch (strtolower($option['type'])) {
                    case 'eq':
                        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->equals((int)$option['value']));
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