<?php

namespace ZF\Apigility\Doctrine\Server\Resource;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ODM\MongoDB\Query\Builder as MongoDBQueryBuilder;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Stdlib\Hydrator;
use Zend\EventManager\EventInterface;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;
use ZF\Apigility\Doctrine\Server\Query\Provider\QueryProviderInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\Apigility\Doctrine\Server\Exception\InvalidArgumentException;
use ZF\Apigility\Doctrine\Server\Query\CreateFilter\QueryCreateFilterInterface;
use ZF\Rest\AbstractResourceListener;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\StaticEventManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Traversable;
use ReflectionClass;

/**
 * Class DoctrineResource
 *
 * @package ZF\Apigility\Doctrine\Server\Resource
 */
class DoctrineResource extends AbstractResourceListener implements
    ServiceManagerAwareInterface,
    ObjectManagerAwareInterface,
    EventManagerAwareInterface,
    HydratorAwareInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

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
     * @param ObjectManager|EntityManagerInterface $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Get the object manager
     *
     * @return ObjectManager|EntityManagerInterface
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
     * @var array|QueryProviderInterface
     */
    protected $queryProviders;

    /**
     * @param array|\ZF\Apigility\Doctrine\Server\Query\Provider\QueryProviderInterface[]
     *
     * @throws InvalidArgumentException if parameter is not an array or \Traversable object
     */
    public function setQueryProviders($queryProviders)
    {
        // @codeCoverageIgnoreStart
        if (!is_array($queryProviders) && !$queryProviders instanceof Traversable) {
            throw new InvalidArgumentException('queryProviders must be array or Traversable object');
        }

        foreach ($queryProviders as $qp) {
            if (!$qp instanceof QueryProviderInterface) {
                throw new InvalidArgumentException('queryProviders must implement QueryProviderInterface');
            }
        }
        // @codeCoverageIgnoreEnd

        $this->queryProviders = (array) $queryProviders;
    }

    /**
     * @return array|QueryProviderInterface[]
     */
    public function getQueryProviders()
    {
        return $this->queryProviders;
    }

    /**
     * @param $method
     *
     * @return QueryProviderInterface
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
     * @var string entityIdentifierName
     */
    protected $entityIdentifierName;

    /**
     * @return string
     */
    public function getEntityIdentifierName()
    {
        return $this->entityIdentifierName;
    }

    /**
     * @param string
     * @return $this
     */
    public function setEntityIdentifierName($value)
    {
        $this->entityIdentifierName = $value;

        return $this;
    }

    /**
     * @var string
     */
    protected $routeIdentifierName;

    /**
     * @return string
     */
    public function getRouteIdentifierName()
    {
        return $this->routeIdentifierName;
    }

    /**
     * @param string $routeIdentifierName
     * @return $this
     */
    public function setRouteIdentifierName($routeIdentifierName)
    {
        $this->routeIdentifierName = $routeIdentifierName;
        return $this;
    }

    /**
     * @var QueryCreateFilterInterface
     */
    protected $queryCreateFilter;

    public function setQueryCreateFilter(QueryCreateFilterInterface $value)
    {
        $this->queryCreateFilter = $value;

        return $this;
    }

    public function getQueryCreateFilter()
    {
        return $this->queryCreateFilter;
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
     *   'multi' => 1,
     *   'keyed' => 2,
     *   'routes' => 3
     * );
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
     * @param HydratorInterface $hydrator
     *
     * @return $this
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;

        return $this;
    }

    /**
     * @return HydratorInterface
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

        $data = $this->getQueryCreateFilter()->filter($this->getEvent(), $entityClass, $data);
        if ($data instanceof ApiProblem) {
            return $data;
        }

        $entity = new $entityClass;
        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_CREATE_PRE, $entity, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        } elseif (!$results->isEmpty() && $results->last() !== null) {
            // TODO Change to a more logical/secure way to see if data was acted and and we have the expected response
            $preEventData = $results->last();
        } else {
            $preEventData = $data;
        }

        $hydrator = $this->getHydrator();
        $hydrator->hydrate((array) $preEventData, $entity);

        $this->getObjectManager()->persist($entity);

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_CREATE_POST, $entity, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

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
        $entity = $this->findEntity($id, 'delete');

        if ($entity instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $entity;
        }
            // @codeCoverageIgnoreEnd

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_DELETE_PRE, $entity);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        $this->getObjectManager()->remove($entity);

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_DELETE_POST, $entity);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        $this->getObjectManager()->flush();

        return true;
    }

    /**
     * Respond to the PATCH method (partial update of existing entity) on
     * a collection, i.e. update multiple entities in a collection.
     *
     * @param array $data
     * @return array
     */
    public function patchList($data)
    {
        $return = new ArrayCollection();

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_PATCH_LIST_PRE, $data, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        if (!$this->getObjectManager() instanceof EntityManagerInterface) {
            throw new InvalidArgumentException('Invalid Object Manager, must implement EntityManagerInterface');
        }

        $this->getObjectManager()->getConnection()->beginTransaction();
        foreach ($data as $row) {
            $result = $this->patch($row[$this->getEntityIdentifierName()], $row);
            if ($result instanceof ApiProblem) {
                $this->getObjectManager()->getConnection()->rollback();

                return $result;
            }

            $return->add($result);
        }
        $this->getObjectManager()->getConnection()->commit();

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_PATCH_LIST_POST, $return, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        return $return;
    }

    /**
     * Delete a list of entities
     *
     * @param mixed $data
     * @return ApiProblem|mixed
     */
    public function deleteList($data)
    {
        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_DELETE_LIST_PRE, $data, $data);
        if ($results->last() instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $results->last();
        }
            // @codeCoverageIgnoreEnd

        $this->getObjectManager()->getConnection()->beginTransaction();
        foreach ($data as $row) {
            $result = $this->delete($row[$this->getEntityIdentifierName()]);

            if ($result instanceof ApiProblem) {
                // @codeCoverageIgnoreStart
                $this->getObjectManager()->getConnection()->rollback();

                return $result;
                // @codeCoverageIgnoreEnd
            }
        }
        $this->getObjectManager()->getConnection()->commit();

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_DELETE_LIST_POST, true, $data);
        if ($results->last() instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $results->last();
        }
            // @codeCoverageIgnoreEnd

        return true;
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
        $event = new DoctrineResourceEvent(DoctrineResourceEvent::EVENT_FETCH_PRE, $this);
        $event->setEntityClassName($this->getEntityClass());
        $event->setEntityId($id);
        $eventManager = $this->getEventManager();
        $response = $eventManager->trigger($event);
        if ($response->last() instanceof ApiProblem) {
            return $response->last();
        }

        $entity = $this->findEntity($id, 'fetch');

        if ($entity instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $entity;
        }
            // @codeCoverageIgnoreEnd

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_FETCH_POST, $entity);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

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
        $queryProvider = $this->getQueryProvider('fetch_all');
        $queryBuilder = $queryProvider->createQuery($this->getEvent(), $this->getEntityClass(), $data);

        if ($queryBuilder instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $queryBuilder;
        }
            // @codeCoverageIgnoreEnd

        $response = $this->triggerDoctrineEvent(
            DoctrineResourceEvent::EVENT_FETCH_ALL_PRE,
            $this->getEntityClass(),
            $data
        );
        if ($response->last() instanceof ApiProblem) {
            return $response->last();
        }

        $adapter = $queryProvider->getPaginatedQuery($queryBuilder);
        $reflection = new ReflectionClass($this->getCollectionClass());
        $collection = $reflection->newInstance($adapter);

        $results = $this->triggerDoctrineEvent(
            DoctrineResourceEvent::EVENT_FETCH_ALL_POST,
            $this->getEntityClass(),
            $data
        );
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        // Add event to set extra HAL data
        $entityClass = $this->getEntityClass();
        StaticEventManager::getInstance()->attach(
            'ZF\Rest\RestController',
            'getList.post',
            function (EventInterface $e) use ($queryProvider, $entityClass, $data) {
                /** @var \ZF\Hal\Collection $halCollection */
                $halCollection = $e->getParam('collection');
                $collection = $halCollection->getCollection();

                $collection->setItemCountPerPage($halCollection->getPageSize());
                $collection->setCurrentPageNumber($halCollection->getPage());

                $halCollection->setAttributes(
                    array(
                    'count' => $collection->getCurrentItemCount(),
                    'total' => $collection->getTotalItemCount(),
                    'collectionTotal' => $queryProvider->getCollectionTotal($entityClass)
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

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_PATCH_PRE, $entity, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        // Hydrate entity with patched data
        $this->getHydrator()->hydrate((array) $data, $entity);

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_PATCH_POST, $entity, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        $this->getObjectManager()->flush();

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

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_UPDATE_PRE, $entity, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        } elseif (!$results->isEmpty() && $results->last() !== null) {
            // TODO Change to a more logical/secure way to see if data was acted on and we have the expected response
            $preEventData = $results->last();
        } else {
            $preEventData = $data;
        }

        $this->getHydrator()->hydrate((array) $preEventData, $entity);

        $results = $this->triggerDoctrineEvent(DoctrineResourceEvent::EVENT_UPDATE_POST, $entity, $data);
        if ($results->last() instanceof ApiProblem) {
            return $results->last();
        }

        $this->getObjectManager()->flush();

        return $entity;
    }

    /**
     * This method will give custom listeners te chance to alter entities / collections.
     * Listeners can also return an ApiProblem, which will be returned immediately.
     * It is also possible to throw Exceptions, which will result in an ApiProblem eventually.
     *
     * @param $name
     * @param $entity
     * @param $data mixed The original data supplied to the resource method, if any
     *
     * @return \Zend\EventManager\ResponseCollection
     */
    protected function triggerDoctrineEvent($name, $entity, $data = null)
    {
        $event = new DoctrineResourceEvent($name, $this);
        $event->setEntity($entity);
        $event->setData($data);
        $event->setObjectManager($this->getObjectManager());
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
     * @param $method
     *
     * @return object
     */
    protected function findEntity($id, $method)
    {
        // Match identiy identifier name(s) with id(s)
        $ids = explode($this->getMultiKeyDelimiter(), $id);
        $keys = explode($this->getMultiKeyDelimiter(), $this->getEntityIdentifierName());
        $criteria = array();

        // @codeCoverageIgnoreStart
        if (count($ids) !== count($keys)) {
            return new ApiProblem(
                500,
                'Invalid multi identifier count.  '
                . count($ids)
                . ' must equal '
                . count($keys)
            );
        }
        // @codeCoverageIgnoreEnd

        foreach ($keys as $index => $identifier) {
            $criteria[$identifier] = $ids[$index];
        }

        $classMetaData = $this->getObjectManager()->getClassMetadata($this->getEntityClass());
        $routeMatch = $this->getEvent()->getRouteMatch();
        $associationMappings = $classMetaData->getAssociationNames();
        $fieldNames = $classMetaData->getFieldNames();
        $routeParams = $routeMatch->getParams();

        if (array_key_exists($this->getRouteIdentifierName(), $routeParams)) {
            unset($routeParams[$this->getRouteIdentifierName()]);
        }
        
        $reservedRouteParams = ['controller','action',
            \Zend\Mvc\ModuleRouteListener::MODULE_NAMESPACE,\Zend\Mvc\ModuleRouteListener::ORIGINAL_CONTROLLER
        ];
        $allowedRouteParams = array_diff_key($routeParams, array_flip( $reservedRouteParams ) );
        
        /**
         * Append query selection parameters by route match.
         */
        foreach ($allowedRouteParams as $routeMatchParam => $value) {
            if ($this->getStripRouteParameterSuffix() === substr(
                $routeMatchParam,
                -1 * strlen($this->getStripRouteParameterSuffix())
            )) {
                $routeMatchParam = substr($routeMatchParam, 0, -1 * strlen($this->getStripRouteParameterSuffix()));
            }

            if (in_array($routeMatchParam, $associationMappings) || in_array($routeMatchParam, $fieldNames)) {
                $criteria[$routeMatchParam] = $value;
            }
        }

        // Build query
        $queryProvider = $this->getQueryProvider($method);
        $queryBuilder = $queryProvider->createQuery($this->getEvent(), $this->getEntityClass(), null);
        
        if ($queryBuilder instanceof ApiProblem) {
            // @codeCoverageIgnoreStart
            return $queryBuilder;
        }
            // @codeCoverageIgnoreEnd

        // Add criteria
        foreach ($criteria as $key => $value) {
            if ($queryBuilder instanceof MongoDBQueryBuilder) {
                $queryBuilder->field($key)->equals($value);
            } else {
                $parameterName = 'a' . md5(rand());
                $queryBuilder->andwhere($queryBuilder->expr()->eq('row.' . $key, ":$parameterName"));
                $queryBuilder->setParameter($parameterName, $value);
            }
        }

        try {
            $entity = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            $entity = null;
        }

        if (!$entity) {
            $entity = new ApiProblem(404, 'Entity was not found');
        }

        return $entity;
    }
}
