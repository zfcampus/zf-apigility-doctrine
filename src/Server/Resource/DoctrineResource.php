<?php

namespace ZF\Apigility\Doctrine\Server\Resource;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use DoctrineModule\Stdlib\Hydrator;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use ZF\Apigility\Doctrine\Server\Collection\Query;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use Zend\EventManager\StaticEventManager;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Stdlib\ArrayUtils;

/**
 * Class DoctrineResource
 *
 * @package ZF\Apigility\Doctrine\Server\Resource
 */
class DoctrineResource extends AbstractResourceListener
    implements ObjectManagerAwareInterface, ServiceManagerAwareInterface, EventManagerAwareInterface
{
    use ProvidesObjectManager;
    use EventManagerAwareTrait;

    /**
     * @var array
     */
    protected $eventIdentifier = ['ZF\Apigility\Doctrine\DoctrineResource'];

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var Query\ApigilityFetchAllQuery
     */
    protected $fetchAllQuery;

    /**
     * @param ServiceManager $serviceManager
     *
     * @return $this
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param \ZF\Apigility\Doctrine\Server\Collection\Query\ApigilityFetchAllQuery $fetchAllQuery
     */
    public function setFetchAllQuery($fetchAllQuery)
    {
        $this->fetchAllQuery = $fetchAllQuery;
    }

    /**
     * @return \ZF\Apigility\Doctrine\Server\Collection\Query\ApigilityFetchAllQuery
     */
    public function getFetchAllQuery()
    {
        return $this->fetchAllQuery;
    }

    /**
     * @var string
     */
    protected $multiKeyDelimiter = '.';

    public function setMultiKeyDelimiter($value)
    {
        $this->multiKeyDelimiter = $value;

        return $this;
    }

    public function getMultiKeyDelimiter()
    {
        return $this->multiKeyDelimiter;
    }

    /**
     * For /multi/1/keyed/2/routes/3 the route parameter
     * names may include an id suffix (e.g. id, _id, Id)
     * and this will be striped to create criteria
     *
     * Example
     * $objectManager->getRepository(...)->findOneBy(
         'multi' => 1,
         'keyed' => 2,
         'routes' => 3
      );
     *
     * @var string
     */
    protected $stripRouteParameterSuffix = '_id';

    public function setStripRouteParameterSuffix($value)
    {
        $this->stripRouteParameterSuffix = $value;

        return $this;
    }

    public function getStripRouteParameterSuffix()
    {
        return $this->stripRouteParameterSuffix;
    }

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
            // @codeCoverageIgnoreStart
            // FIXME: find a way to test this line from a created API.  Shouldn't all created API's have a hydrator?
            $this->hydrator = new Hydrator\DoctrineObject($this->getObjectManager(), $this->getEntityClass());
        }
            // @codeCoverageIgnoreEnd
        return $this->hydrator;
    }

    /**
     * Create a resource
     *
     * @param  mixed            $data
     * @return ApiProblem|mixed
     */
    public function create($data)
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;
        $hydrator = $this->getHydrator();
        $hydrator->hydrate((array) $data, $entity);

        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_CREATE_PRE, $entity);
        $this->getObjectManager()->persist($entity);
        $this->getObjectManager()->flush();
        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_CREATE_POST, $entity);

        return $entity;
    }

    /**
     * Delete a resource
     *
     * @param  mixed            $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        $entity = $this->findEntity($id);
        if (!$entity) {
            // @codeCoverageIgnoreStart
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }
            // @codeCoverageIgnoreEnd

        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_DELETE_PRE, $entity);
        $this->getObjectManager()->remove($entity);
        $this->getObjectManager()->flush();
        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_DELETE_POST, $entity);

        return true;
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed            $data
     * @return ApiProblem|mixed
     *                               @codeCoverageIgnore
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
     * @param  mixed            $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        /**
         * Zoom would be a nice-to-have
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
        */

        $entity = $this->findEntity($id);
        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_FETCH_POST, $entity);

        return $entity;
    }

    /**
     * Fetch all or a subset of resources
     *
     *
     * @see Apigility/Doctrine/Server/Resource/AbstractResource.php
     * @param  array            $params
     * @return ApiProblem|mixed
     */
    public function fetchAll($data = array())
    {
        $objectManager = $this->getObjectManager();

        // Build query
        $fetchAllQuery = $this->getFetchAllQuery();
        $queryBuilder = $fetchAllQuery->createQuery($this->getEntityClass(), $data);

        if ($queryBuilder instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $queryBuilder;
        }
            // @codeCoverageIgnoreEnd

        $adapter = $fetchAllQuery->getPaginatedQuery($queryBuilder);
        $reflection = new \ReflectionClass($this->getCollectionClass());
        $collection = $reflection->newInstance($adapter);

        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_FETCH_ALL_POST, null, $collection);

        // Add event to set extra HAL data
        $entityClass = $this->getEntityClass();
        StaticEventManager::getInstance()->attach('ZF\Rest\RestController', 'getList.post',
            function ($e) use ($fetchAllQuery, $entityClass, $data) {
                $halCollection = $e->getParam('collection');
                $halCollection->getCollection()->setItemCountPerPage($halCollection->getPageSize());
                $halCollection->getCollection()->setCurrentPageNumber($halCollection->getPage());

                $halCollection->setAttributes(array(
                   'count' => $halCollection->getCollection()->getCurrentItemCount(),
                   'total' => $halCollection->getCollection()->getTotalItemCount(),
                   'collectionTotal' => $fetchAllQuery->getCollectionTotal($entityClass),
                ));

                $halCollection->setCollectionRouteOptions(array(
                    'query' => ArrayUtils::iteratorToArray($data)
                ));
            }
        );

        return $collection;
    }

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed            $id
     * @param  mixed            $data
     * @return ApiProblem|mixed
     */
    public function patch($id, $data)
    {
        $entity = $this->findEntity($id);
        if (!$entity) {
            // @codeCoverageIgnoreStart
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }
            // @codeCoverageIgnoreEnd

        // Hydrate entity with patched data
        $this->getHydrator()->hydrate((array) $data, $entity);

        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_PATCH_PRE, $entity);
        $this->getObjectManager()->flush();
        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_PATCH_POST, $entity);

        return $entity;
    }

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed            $data
     * @return ApiProblem|mixed
     *                               @codeCoverageIgnore
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @param  mixed            $id
     * @param  mixed            $data
     * @return ApiProblem|mixed
     */
    public function update($id, $data)
    {
        $entity = $this->findEntity($id);
        if (!$entity) {
            // @codeCoverageIgnoreStart
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
            // @codeCoverageIgnoreEnd
        }

        $this->getHydrator()->hydrate((array) $data, $entity);

        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_UPDATE_PRE, $entity);
        $this->getObjectManager()->flush();
        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_UPDATE_POST, $entity);

        return $entity;
    }

    /**
     * This method will give custom listeners te chance to alter entities / collections.
     * The listeners are not allowed to give an early result.
     * It is possible to throw Exceptions, which will result in an ApiProblem eventually.
     *
     * @param      $name
     * @param      $entity
     * @param null $collection
     *
     * @return \Zend\EventManager\ResponseCollection
     */
    protected function triggerDoctrineEvent($name, $entity, $collection = null)
    {
        $event = new DoctrineResourceEvent($name, $this);
        $event->setEntity($entity);
        $event->setCollection($collection);
        $event->setResourceEvent($this->getEvent());

        $eventManager = $this->getEventManager();
        $response = $eventManager->trigger($event);
        return $response;
    }

    /**
     * Gets an entity by route params and/or the specified id
     *
     * @param $id
     *
     * @return object
     */
    protected function findEntity($id)
    {
        $classMetaData = $this->getObjectManager()->getClassMetadata($this->getEntityClass());
        $identifierFieldNames = $classMetaData->getIdentifierFieldNames();

        $criteria = array();

        // Check if ID is a composite ID
        if (strpos($id, $this->getMultiKeyDelimiter()) !== false) {
            $compositeIdParts = explode($this->getMultiKeyDelimiter(), $id);
            foreach ($compositeIdParts as $index => $compositeIdPart) {
                $criteria[$identifierFieldNames[$index]] = $compositeIdPart;
            }
        } else {
            $criteria[$identifierFieldNames[0]] = $id;
        }

        $routeMatch = $this->getEvent()->getRouteMatch();
        $associationMappings = $classMetaData->getAssociationNames();
        $fieldNames = $classMetaData->getFieldNames();

        foreach ($routeMatch->getParams() as $routeMatchParam => $value) {

            if (substr($routeMatchParam,
                (-1 * abs(strlen($this->getStripRouteParameterSuffix())) == $this->getStripRouteParameterSuffix()))) {

                $routeMatchParam = substr($routeMatchParam, 0,
                    strlen($routeMatchParam) - strlen($this->getStripRouteParameterSuffix()));
            }

            if (in_array($routeMatchParam, $associationMappings)
                or in_array($routeMatchParam, $fieldNames)) {

                $criteria[$routeMatchParam] = $value;
            }
        }

        $entity = $this->getObjectManager()->getRepository($this->getEntityClass())
            ->findOneBy($criteria);

        return $entity;
    }
}
