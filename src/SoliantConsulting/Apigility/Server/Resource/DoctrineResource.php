<?php

namespace SoliantConsulting\Apigility\Server\Resource;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use DoctrineModule\Stdlib\Hydrator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

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
        $parameters = $this->getEvent()->getQueryParams();

        // Pagination
        $limit = 25;
        if (isset($parameters['_limit'])) {
            $limit = ($parameters['_limit'] <= 100) ? $parameters['_limit'] : 100;
        }
        $offset = 0;
        if (isset($parameters['_page'])) {
            $offset = ($parameters['_page'] * $limit);
        }

        // Order by
        $orderBy = array();
        if (isset($parameters['_orderBy'])) {
            foreach($parameters['_orderBy'] as $fieldName => $sort) {
                $orderBy[$fieldName] = $sort;
            }
        }

        // Filters
        $criteria = array();
        if (isset($parameters['_query'])) {
            foreach ($parameters['_query'] as $fieldName => $filter) {
                $criteria[$fieldName] = $filter;
            }
        }

        // Run query
        $repository = $this->getObjectManager()->getRepository($this->getEntityClass());
        $results =  $repository->findBy($criteria, $orderBy, $limit, $offset);
        $adapter = new ArrayAdapter($results->toArray());

        // Return collection:
        $collectionClass = $this->getCollectionClass();
        return new $collectionClass($adapter);
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