<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Query;

use DoctrineModule\Persistence\ProvidesObjectManager;
use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOdmAdapter;
use Zend\ServiceManager\AbstractPluginManager;

class FetchAllOdmQuery implements ApigilityFetchAllQuery
{
    use ProvidesObjectManager;

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

        // Run filters on query
        if (isset($parameters['query'])) {
            foreach ($parameters['query'] as $option) {
                // Type cast value
                if(isset($option['field']) and isset($metadata['fieldMappings'][$option['field']]['type'])) {
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
                            if ($option['value']) {
                                if (isset($option['format']) and $option['format']) {
                                    $format = $option['format'];
                                } else {
                                    $format = 'Y-m-d H:i:s';
                                }
                                $option['value'] = \DateTime::createFromFormat($format, $option['value']);
                            }
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

                $filter = $this->getFilterManager()->get(strtolower($option['type']));
                $filter->filter($queryBuilder, $option);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param       $queryBuilder
     *
     * @return DoctrineOdmAdapter
     */
    public function getPaginatedQuery($queryBuilder)
    {
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