<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Query;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOrmAdapter;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\ServiceManager\AbstractPluginManager;

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
    protected $filterManager;

    public function getCollectionClass()
    {
        return $this->collectionClass;
    }

    public function setCollectionClass($value)
    {
        $this->collectionClass = $value;
        return $this;
    }

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

        // Get metadata for type casting
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $metadata = (array)$cmf->getMetadataFor($entityClass);

        // Run filters on query
        if (isset($parameters['query'])) {
            foreach ($parameters['query'] as $option) {
                if(isset($option['field']) and isset($metadata['fieldMappings'][$option['field']]['type'])) {
                    switch ($metadata['fieldMappings'][$option['field']]['type']) {
                        case 'string':
                            settype($option['value'], 'string');
                            break;
                        case 'integer':
                        case 'smallint':
                        #case 'bigint':
                            settype($option['value'], 'integer');
                            break;
                        case 'boolean':
                            settype($option['value'], 'boolean');
                            break;
                        case 'decimal':
                            settype($option['value'], 'decimal');
                            break;
                        case 'date':
                            if ($option['value']) {
                                if (isset($option['format']) and $option['format']) {
                                    $format = $option['format'];
                                } else {
                                    $format = 'Y-m-d';
                                }
                                $option['value'] = \DateTime::createFromFormat($format, $option['value']);
                            }
                            break;
                        case 'time':
                            if ($option['value']) {
                                if (isset($option['format']) and $option['format']) {
                                    $format = $option['format'];
                                } else {
                                    $format = 'H:i:s';
                                }
                                $option['value'] = \DateTime::createFromFormat($format, $option['value']);
                            }
                            break;
                        case 'datetime':
                            if ($option['value']) {
                                if (isset($option['format']) and $option['format']) {
                                    $format = $option['format'];
                                } else {
                                    $format = 'Y-m-d H:i:s';
                                }
                                $option['value'] = \DateTime::createFromFormat($format, $option['value']);
                            }
                            break;
                        case 'float':
                            settype($option['value'], 'float');
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
     * @return AdapterInterface
     */
    public function getPaginatedQuery($queryBuilder)
    {
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
