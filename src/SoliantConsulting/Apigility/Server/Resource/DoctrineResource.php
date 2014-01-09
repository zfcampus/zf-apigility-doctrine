<?php

namespace SoliantConsulting\Apigility\Server\Resource;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use DoctrineModule\Stdlib\Hydrator;
use SoliantConsulting\Apigility\Server\Collection\Query;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use ZF\Hal\Collection;
use Zend\EventManager\StaticEventManager;
use SoliantConsulting\Apigility\Server\Hydrator\Strategy\CollectionExtract;

/**
 * Class DoctrineResource
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
     * If the extractCollections array contains a collection for this resource
     * expand that collection instead of returning a link to the collection
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        $parameters = $this->getEvent()->getQueryParams()->toArray();

        if ($this->getEvent()->getRouteParam('zoom')) {
            $parameters['zoom'] = $this->getEvent()->getRouteParam('zoom');
        }

        if (isset($parameters['zoom'])) {
            foreach ($parameters['zoom'] as $collectionName) {
                if ($this->getHydrator()->getExtractService()->hasStrategy($collectionName)) {
                    $this->getHydrator()->getExtractService()->removeStrategy($collectionName);
                    $this->getHydrator()->getExtractService()->addStrategy($collectionName, new CollectionExtract());
                }
            }
        }

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
        // Load parameters
        $parameters = $this->getEvent()->getQueryParams()->toArray();

        if ($this->getEvent()->getRouteParam('query')) {
            $parameters['query'] = $this->getEvent()->getRouteParam('query');
        }

        if ($this->getEvent()->getRouteParam('orderBy')) {
            $parameters['orderBy'] = $this->getEvent()->getRouteParam('orderBy');
        }

        // Load the correct queryFactory:
        $objectManager = $this->getObjectManager();
        /** @var Query\ApigilityFetchAllQuery $queryBuilder */
        if (class_exists('\\Doctrine\\ORM\\EntityManager') && $objectManager instanceof \Doctrine\ORM\EntityManager) {
            $queryBuilder = new Query\FetchAllOrmQuery();
        } elseif (class_exists('\\Doctrine\\ODM\\MongoDB\\DocumentManager') && $objectManager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $queryBuilder = new Query\FetchAllOdmQuery();
        } else {
            return new ApiProblem(500, 'No valid doctrine module is found for objectManager ' . get_class($objectManager));
        }

        // Create collection
        $queryBuilder->setObjectManager($objectManager);
        $adapter = $queryBuilder->getPaginatedQuery($this->getEntityClass(), $parameters);
        $reflection = new \ReflectionClass($this->getCollectionClass());
        $collection = $reflection->newInstance($adapter);

        // Add event to set extra HAL parameters
        $entityClass = $this->getEntityClass();
        StaticEventManager::getInstance()->attach('ZF\Rest\RestController', 'getList.post',
            function($e) use ($queryBuilder, $entityClass, $parameters) {
                $halCollection = $e->getParam('collection');
                $halCollection->getCollection()->setItemCountPerPage($halCollection->getPageSize());
                $halCollection->getCollection()->setCurrentPageNumber($halCollection->getPage());

                $halCollection->setAttributes(array(
                   'count' => $halCollection->getCollection()->getCurrentItemCount(),
                   'total' => $halCollection->getCollection()->getTotalItemCount(),
                   'collectionTotal' => $queryBuilder->getCollectionTotal($entityClass),
                ));

                $halCollection->setCollectionRouteOptions(array(
                    'query' => $parameters
                ));
            }
        );

        return $collection;
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
