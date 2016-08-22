<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server\Event\Listener;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Phpro\DoctrineHydrationModule\Service\DoctrineHydratorFactory;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Hydrator\HydratorInterface;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayObject;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;
use ZF\Apigility\Doctrine\Server\Exception\InvalidArgumentException;

/**
 * The purpose of this listener is to handle toMany relationships that were supplied in the request method. Historically
 * only entity identifiers should have been passed in which was used to establish a relationship with the entity. This
 * listener will create or update the embedded entities and strip out the additional data allowing related entities to
 * also be created or updated with the parent, preventing multiple calls. Downstream, the relationships will continue to
 * be managed by the hydrator and whatever strategies are defined on it
 */
class CollectionListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * @var null
     */
    protected $entityHydratorMap = null;

    /**
     * @var array
     */
    protected $classMetadataMap = [];

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $entityCollectionValuedAssociations = [];

    /**
     * @var
     */
    protected $rootEntity;

    /**
     * @var InputFilterInterface
     */
    protected $inputFilter;

    /**
     * @var array Data supplied to be processed, likely from POST or PUT body
     */
    protected $objectData;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceManager;

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            DoctrineResourceEvent::EVENT_UPDATE_PRE,
            [$this, 'handleCollections']
        );

        $this->listeners[] = $events->attach(
            DoctrineResourceEvent::EVENT_CREATE_PRE,
            [$this, 'handleCollections']
        );
    }

    /**
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * @param DoctrineResourceEvent $event
     * @return array
     */
    public function handleCollections(DoctrineResourceEvent $event)
    {
        // Setup the dependencies
        $this->setObjectManager($event->getObjectManager());
        $this->setRootEntity($event->getEntity());
        $this->setObjectData((array) $event->getData());
        $this->setInputFilter($event->getResourceEvent()->getInputFilter());
        $this->setServiceManager($event->getTarget()->getServiceManager());

        // Start processing with the root entity, if any nested entities will be handled by the iterateEntity method
        $this->setObjectData(
            $this->iterateEntity($this->getRootEntity(), $this->getObjectData(), $this->getInputFilter())
        );

        $event->setData($this->getObjectData());

        return $this->getObjectData();
    }

    /**
     * @param object|string $entity
     * @param array $data
     * @param InputFilterInterface $inputFilter
     * @return mixed
     */
    protected function iterateEntity($entity, $data, InputFilterInterface $inputFilter)
    {
        $metadata     = $this->getClassMetadata($entity);
        $associations = $this->getEntityCollectionValuedAssociations($entity, $data, true);

        if ($associations->count() > 0) {
            foreach ($associations->getIterator() as $association) {
                // Skip associations that don't have data
                if ($this->validateAssociationData($association, $data)) {
                    foreach ($data[$association] as &$subEntityData) {
                        $associationTargetClass = $metadata->getAssociationTargetClass($association);
                        // Handle nested / subresource by recursion
                        if ($this
                                ->getEntityCollectionValuedAssociations($associationTargetClass, $subEntityData, true)
                                ->count() > 0
                        ) {
                            $subEntityData = $this->iterateEntity(
                                $metadata->getAssociationTargetClass($association),
                                $subEntityData,
                                $this->getAssociatedEntityInputFilter($association, $inputFilter)
                            );
                        }

                        $subEntityData = $this->processEntity($associationTargetClass, $subEntityData);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param $targetEntityClassName
     * @param $data
     * @return object|null
     */
    protected function processEntity($targetEntityClassName, $data)
    {
        $metadata        = $this->getClassMetadata($targetEntityClassName);
        $identifierNames = $metadata->getIdentifierFieldNames($targetEntityClassName);
        if (empty($identifierNames)) {
            return null; // Not really sure what would cause this or how to handle, skipping for now
        }

        $identifierValues = [];
        foreach ($identifierNames as $identifierName) {
            if (! isset($data[$identifierName]) || empty($data[$identifierName])) {
                continue; // Should mean we are working with a new entity to be created
            }
            $identifierValues[$identifierName] = $data[$identifierName];
        }

        // Investigate if we are performing an update or creating a new entity based on identifiers
        $entity = false;
        if (count($identifierValues) === count($identifierNames)) {
            $entity = $this->getObjectManager()->find($targetEntityClassName, $identifierValues);
        }

        if (! $entity) {
            $entity = new $targetEntityClassName;
        }

        $hydrator = $this->getEntityHydrator($targetEntityClassName, $this->getObjectManager());
        $hydrator->hydrate($data, $entity);
        $this->getObjectManager()->persist($entity);

        return $entity;
    }

    /**
     * Retrieve the Doctrine MetaData for whichever entity we are currently processing
     *
     * @param $entity
     * @return ClassMetadata
     */
    protected function getClassMetadata($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }
        if (! array_key_exists($entity, $this->classMetadataMap)) {
            $metadata = $this->getObjectManager()->getClassMetadata($entity);
            if (! $metadata || ! $metadata instanceof ClassMetadata) {
                throw new InvalidArgumentException('Metadata could not be found for requested entity');
            }

            $this->classMetadataMap[$entity] = $metadata;
        }

        return $this->classMetadataMap[$entity];
    }

    /**
     * @param $entity
     * @param null|array $data
     * @param bool $stripEmptyAssociations
     * @return ArrayObject
     */
    protected function getEntityCollectionValuedAssociations($entity, $data = null, $stripEmptyAssociations = false)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }
        if (! array_key_exists($entity, $this->entityCollectionValuedAssociations)) {
            $collectionValuedAssociations = [];
            $metadata                     = $this->getClassMetadata($entity);
            $associations                 = $metadata->getAssociationNames();

            foreach ($associations as $association) {
                if ($metadata->isCollectionValuedAssociation($association)) {
                    $collectionValuedAssociations[] = $association;
                }
            }

            $this->entityCollectionValuedAssociations[$entity] = new ArrayObject($collectionValuedAssociations);
        }

        if ($stripEmptyAssociations === true && ! empty($data) && is_array($data)) {
            return $this->stripEmptyAssociations($this->entityCollectionValuedAssociations[$entity], $data);
        }

        return $this->entityCollectionValuedAssociations[$entity];
    }

    /**
     * @param ArrayObject $associations
     * @param array $data
     * @return ArrayObject
     */
    protected function stripEmptyAssociations(ArrayObject $associations, $data)
    {
        $associationsArray = $associations->getArrayCopy();
        foreach ($associationsArray as $key => $association) {
            if (! $this->validateAssociationData($association, $data)) {
                unset($associationsArray[$key]);
            }
        }

        return new ArrayObject($associationsArray);
    }

    /**
     * @param $association
     * @param $data
     * @return bool
     */
    protected function validateAssociationData($association, $data)
    {
        return ! empty($data[$association])
           && (is_array($data[$association]) || $data[$association] instanceof \Traversable);
    }

    /**
     * @param $association
     * @param $inputFilter
     * @return InputFilterInterface
     */
    protected function getAssociatedEntityInputFilter($association, InputFilterInterface $inputFilter)
    {
        // Skip handling associations that aren't in the data
        // Ensure the collection value has an input filter
        if (! $inputFilter->has($association)) {
            /*
             * Value must not have been in the inputFilter and wasn't stripped out.
             * Treat as hostile and stop execution.
             */
            throw new InvalidArgumentException('Non-validated input detected: ' . $association);
        }

        $childInputFilter = $inputFilter->get($association);
        if ($childInputFilter instanceof CollectionInputFilter) {
            return $childInputFilter->getInputFilter();
        }

        return $childInputFilter;
    }

    /**
     * @param $entityClass
     * @param $objectManager
     * @return AbstractHydrator|DoctrineObject
     */
    protected function getEntityHydrator($entityClass, $objectManager)
    {
        $hydrator    = false;
        $hydratorMap = $this->getEntityHydratorMap();
        if ($hydratorMap !== false && array_key_exists($entityClass, $hydratorMap)) {
            if ($hydratorMap[$entityClass] instanceof HydratorInterface) {
                return $hydratorMap[$entityClass];
            } else {
                $hydratorManager = $this->getServiceManager()->get('HydratorManager');
                if ($hydratorManager && $hydratorManager instanceof ServiceLocatorInterface) {
                    $hydrator = $hydratorManager->get($this->getEntityHydratorMap()[$entityClass]);
                }
            }
        }

        // If no hydrator returned from hydrator manager, boot the standard and cross your fingers...
        if ($hydrator === false || ! $hydrator instanceof HydratorInterface) {
            $hydrator = new DoctrineObject($objectManager);
        }

        // Store the booted object for next pass
        $this->entityHydratorMap[$entityClass] = $hydrator;

        return $this->entityHydratorMap[$entityClass];
    }

    /**
     * @return array|bool|null
     */
    protected function getEntityHydratorMap()
    {
        if ($this->entityHydratorMap === null) {
            $config = $this->getServiceManager()->get('config');
            $config = $config[DoctrineHydratorFactory::FACTORY_NAMESPACE];

            if (! empty($config)) {
                $this->entityHydratorMap = [];
                foreach ($config as $hydratorKey => $configParams) {
                    $this->entityHydratorMap[$configParams['entity_class']] = $hydratorKey;
                }
            }
        }

        // If still null mark it as bad
        if ($this->entityHydratorMap === null) {
            $this->entityHydratorMap = false;
        }

        return $this->entityHydratorMap;
    }

    /**
     * @return InputFilterInterface
     */
    public function getInputFilter()
    {
        return $this->inputFilter;
    }

    /**
     * @param InputFilterInterface $inputFilter
     * @return $this
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;

        return $this;
    }

    /**
     * @return array
     */
    public function getObjectData()
    {
        return $this->objectData;
    }

    /**
     * @param array $objectData
     * @return $this
     */
    public function setObjectData($objectData)
    {
        $this->objectData = $objectData;

        return $this;
    }

    /**
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @param ObjectManager $objectManager
     * @return $this
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRootEntity()
    {
        return $this->rootEntity;
    }

    /**
     * @param mixed $rootEntity
     * @return $this
     */
    public function setRootEntity($rootEntity)
    {
        $this->rootEntity = $rootEntity;

        return $this;
    }

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @return $this
     */
    public function setServiceManager(ServiceLocatorInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }
}
