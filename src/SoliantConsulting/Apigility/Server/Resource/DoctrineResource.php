<?php

namespace SoliantConsulting\Apigility\Server\Resource;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use DoctrineModule\Stdlib\Hydrator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use Zend\Paginator\Paginator;

/**
 * Class DoctrineResourceListener
 *
 * @package SoliantConsulting\Apigility\Server\Resource
 */
class DoctrineResource extends AbstractResourceListener
    implements ObjectManagerAwareInterface
{

    use ProvidesObjectManager;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * @param \Zend\Stdlib\Hydrator\HydratorInterface $hydrator
     */
    public function setHydrator($hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @return \Zend\Stdlib\Hydrator\HydratorInterface
     */
    public function getHydrator()
    {
        if (!$this->hydrator) {
            $this->hydrator = new Hydrator\DoctrineObject($this->getObjectManager(), $this->getEntityClass());
        }
        return $this->hydrator;
    }


    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function create($data)
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;
        $hydrator = $this->getHydrator();
        $hydrator->hydrate((array)$data, $entity);

        $this->getObjectManager()->persist($entity);
        $this->getObjectManager()->flush();

        return $entity;
    }

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        $entity = $this->getObjectManager()->find($this->getEntityClass(), $id);
        if (!$entity) {
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }

        $this->getObjectManager()->remove($entity);
        $this->getObjectManager()->flush();

        return true;
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function deleteList($data)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for collections');
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        return $this->getObjectManager()->find($this->getEntityClass(), $id);
    }

    /**
     * Fetch all or a subset of resources
     *
     *
     * @see https://github.com/TomHAnderson/soliantconsulting-apigility/blob/master/src/SoliantConsulting/Apigility/Server/Resource/AbstractResource.php
     * @param  array $params
     * @return ApiProblem|mixed
     */
    public function fetchAll($params = array())
    {
       $queryBuilder = $this->getObjectManager()->createQueryBuilder();

        $queryBuilder->select('row')
            ->from($this->getEntityClass(), 'row');

        $totalCountQueryBuilder = clone $queryBuilder;

        $parameters = $this->getEvent()->getQueryParams()->toArray();

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

        // Build collection and paginator
        $collectionClass = $this->getCollectionClass();
        $collection = new $collectionClass($queryBuilder->getQuery(), false);
        $paginator = new Paginator($collection);

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

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patch($id, $data)
    {
        $entity = $this->getObjectManager()->find($this->getEntityClass(), $id);
        if (!$entity) {
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }

        // Load full data:
        $hydrator = $this->getHydrator();
        $originalData = $hydrator->extract($entity);
        $patchedData = array_merge($originalData, (array)$data);

        // Hydrate entity
        $hydrator->hydrate($patchedData, $entity);
        $this->getObjectManager()->flush();

        return $entity;
    }

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function update($id, $data)
    {
        $entity = $this->getObjectManager()->find($this->getEntityClass(), $id);
        if (!$entity) {
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }

        $hydrator = $this->getHydrator();
        $hydrator->hydrate((array)$data, $entity);
        $this->getObjectManager()->flush();

        return $entity;
    }

}