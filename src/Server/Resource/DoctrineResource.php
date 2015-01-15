<?php

namespace ZF\Apigility\Doctrine\Server\Resource;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
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
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Traversable;

/**
 * Class DoctrineResource
 *
 * @package ZF\Apigility\Doctrine\Server\Resource
 */
class DoctrineResource extends AbstractResourceListener implements
    ObjectManagerAwareInterface,
    ServiceManagerAwareInterface,
    EventManagerAwareInterface
{
    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Set the event manager instance used by this context.
     *
     * For convenience, this method will also set the class name / LSB name as
     * identifiers, in addition to any string or array of strings set to the
     * $this->eventIdentifier property.
     *
     * @param  EventManagerInterface $events
     * @return mixed
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $identifiers = array(__CLASS__, get_class($this));
        if (isset($this->eventIdentifier)) {
            if ((is_string($this->eventIdentifier))
                || (is_array($this->eventIdentifier))
                || ($this->eventIdentifier instanceof Traversable)
            ) {
                $identifiers = array_unique(array_merge($identifiers, (array) $this->eventIdentifier));
            } elseif (is_object($this->eventIdentifier)) {
                $identifiers[] = $this->eventIdentifier;
            }
            // silently ignore invalid eventIdentifier types
        }
        $events->setIdentifiers($identifiers);
        $this->events = $events;
        if (method_exists($this, 'attachDefaultListeners')) {
            $this->attachDefaultListeners();
        }
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events instanceof EventManagerInterface) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Get the object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @var array
     */
    protected $eventIdentifier = array('ZF\Apigility\Doctrine\DoctrineResource');

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var queryProviders array
     */
    protected $queryProviders;

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
     * @param ZF\Apigility\Doctrine\Query\Provider\QueryProviderInterface
     */
    public function setQueryProviders(array $queryProviders)
    {
        $this->queryProviders = $queryProviders;
    }

    /**
     * @param ZF\Apigility\Doctrine\Query\Provider\QueryProviderInterface
     */
    public function getQueryProviders()
    {
        return $this->queryProviders;
    }

    /**
     * @return \ZF\Apigility\Doctrine\Server\Collection\Query\ApigilityFetchAllQuery
     */
    public function getQueryProvider($method)
    {
        $queryProviders = $this->getQueryProviders();

        if (isset($queryProviders[$method])) {
            return $queryProviders[$method];
        }

        return $queryProviders['default'];
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
     * @param  mixed $data
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
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        $entity = $this->findEntity($id, 'delete');

        if ($entity instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $entity;
        }
            // @codeCoverageIgnoreEnd

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
     * @param              mixed $data
     * @return             ApiProblem|mixed
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
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        $entity = $this->findEntity($id, 'fetch');

        if ($entity instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $entity;
        }
            // @codeCoverageIgnoreEnd

        if (!$entity) {
            // @codeCoverageIgnoreStart
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }

        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_FETCH_POST, $entity);

        return $entity;
    }

    /**
     * Fetch all or a subset of resources
     *
     * @see    Apigility/Doctrine/Server/Resource/AbstractResource.php
     * @param  array $data
     * @return ApiProblem|mixed
     */
    public function fetchAll($data = array())
    {
        // Build query
        $queryProvider = $this->getQueryProvider('fetch-all');
        $queryBuilder = $queryProvider->createQuery($this->getEntityClass(), $data);

        if ($queryBuilder instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $queryBuilder;
        }
            // @codeCoverageIgnoreEnd

        // Run fetch all pre with query builder
        $event = new DoctrineResourceEvent(DoctrineResourceEvent::EVENT_FETCH_ALL_PRE, $this);
        $event->setQueryBuilder($queryBuilder);
        $event->setResourceEvent($this->getEvent());
        $event->setEntity($this->getEntityClass());
        $eventManager = $this->getEventManager();
        $response = $eventManager->trigger($event);

        $adapter = $queryProvider->getPaginatedQuery($queryBuilder);
        $reflection = new \ReflectionClass($this->getCollectionClass());
        $collection = $reflection->newInstance($adapter);

        $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_FETCH_ALL_POST, null, $collection);

        // Add event to set extra HAL data
        $entityClass = $this->getEntityClass();
        StaticEventManager::getInstance()->attach(
            'ZF\Rest\RestController',
            'getList.post',
            function ($e) use ($queryProvider, $entityClass, $data) {
                $halCollection = $e->getParam('collection');
                $collection = $halCollection->getCollection();

                $collection->setItemCountPerPage($halCollection->getPageSize());
                $collection->setCurrentPageNumber($halCollection->getPage());

                $halCollection->setAttributes(
                    array(
                    'count' => $collection->getCurrentItemCount(),
                    'total' => $collection->getTotalItemCount(),
                    'collectionTotal' => $queryProvider->getCollectionTotal($entityClass),
                    )
                );

                $halCollection->setCollectionRouteOptions(
                    array(
                    'query' => ArrayUtils::iteratorToArray($data)
                    )
                );
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
        $entity = $this->findEntity($id, 'patch');

        if ($entity instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $entity;
        }
            // @codeCoverageIgnoreEnd

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
     * @param              mixed $data
     * @return             ApiProblem|mixed
     *                               @codeCoverageIgnore
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
        $entity = $this->findEntity($id, 'update');

        if ($entity instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $entity;
        }
            // @codeCoverageIgnoreEnd

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
     * @param $name
     * @param $entity
     * @param null   $collection
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
    protected function findEntity($id, $method)
    {
        $classMetaData = $this->getObjectManager()->getClassMetadata($this->getEntityClass());
        $identifierFieldNames = $classMetaData->getIdentifierFieldNames();

        $criteria = array();

        // Check if ID is a composite ID
        if (strpos($id, $this->getMultiKeyDelimiter()) !== false) {
            $compositeIdParts = explode($this->getMultiKeyDelimiter(), $id);

            if (sizeof($compositeIdParts) != sizeof($identifierFieldNames)) {
                return new ApiProblem(
                    500,
                    'Invalid multi identifier count.  '
                    . sizeof($compositeIdParts)
                    . ' must equal '
                    . sizeof($identifierFieldNames)
                );
            }

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
            if (substr(
                $routeMatchParam,
                (-1 * abs(strlen($this->getStripRouteParameterSuffix())) == $this->getStripRouteParameterSuffix())
            )) {
                $routeMatchParam = substr(
                    $routeMatchParam,
                    0,
                    strlen($routeMatchParam) - strlen($this->getStripRouteParameterSuffix())
                );
            }

            if (in_array($routeMatchParam, $associationMappings)
                or in_array($routeMatchParam, $fieldNames)
            ) {
                $criteria[$routeMatchParam] = $value;
            }
        }

        // Build query
        $queryProvider = $this->getQueryProvider($method);
        $queryBuilder = $queryProvider->createQuery($this->getEntityClass(), null);

        if ($queryBuilder instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $queryBuilder;
        }
            // @codeCoverageIgnoreEnd

        // Add criteria
        foreach ($criteria as $key => $value) {
            if ($queryBuilder instanceof \Doctrine\ODM\MongoDB\Query\Builder) {
                $queryBuilder->field($key)->equals($value);
            } else {
                $queryBuilder->andwhere($queryBuilder->expr()->eq('row.' . $key, $value));
            }
        }

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
