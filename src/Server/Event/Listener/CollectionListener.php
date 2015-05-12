<?php

namespace ZF\Apigility\Doctrine\Server\Event\Listener;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Phpro\DoctrineHydrationModule\Service\DoctrineHydratorFactory;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;
use ZF\Apigility\Doctrine\Server\Exception\InvalidArgumentException;

/**
 * Class CollectionListener
 *
 * The purpose of this listener is to handle toMany relationships that were supplied in the request method. Historically
 * only entity identifiers should have been passed in which was used to establish a relationship with the entity. This
 * listener will create or update the embedded entities and strip out the additional data allowing related entities to
 * also be created or updated with the parent, preventing multiple calls. Downstream, the relationships will continue to
 * be managed by the hydrator and whatever strategies are defined on it
 *
 * @package ZF\Apigility\Doctrine\Server\Event\Listener
 */
class CollectionListener implements ListenerAggregateInterface
{
    protected $listeners = array();
    protected $entityHydratorMap = null;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceManager;

    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            DoctrineResourceEvent::EVENT_UPDATE_PRE,
            array( $this, 'handleCollections' )
        );

        $this->listeners[] = $events->attach(
            DoctrineResourceEvent::EVENT_CREATE_PRE,
            array( $this, 'handleCollections' )
        );
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset( $this->listeners[$index] );
            }
        }
    }

    public function handleCollections(DoctrineResourceEvent $event)
    {
        $objectManager = $event->getObjectManager();
        $entity        = $event->getEntity();
        $originalData  = (array) $event->getData();
        $inputFilter   = $event->getResourceEvent()->getInputFilter();

        $this->setServiceManager($event->getTarget()->getServiceManager());
        $metadata = $objectManager->getClassMetadata(get_class($entity));

        $associations = $metadata->getAssociationNames();

        foreach ($associations as $association) {
            if ($metadata->isCollectionValuedAssociation($association)) {
                // Skip handling associations that aren't in the data
                if (array_key_exists($association, $originalData)
                    && !empty( $originalData[$association] )
                    && ( is_array($originalData[$association])
                         || $originalData[$association] instanceof \Traversable )
                ) {
                    // Ensure the collection value has an input filter
                    if (!$inputFilter->has($association)) {
                        /*
                         * If we got here it means the value wasn't in the input filter and wasn't stripped out.
                         * Treat as hostile and stop execution.
                         */
                        throw new InvalidArgumentException('Non-validated input detected');
                    }


                    foreach ($originalData[$association] as &$subEntityData) {
                        $target          = $metadata->getAssociationTargetClass($association);
                        $identifierNames = $metadata->getIdentifierFieldNames($target);
                        if (empty( $identifierNames )) {
                            continue;
                        }

                        $identifierValues = [ ];
                        foreach ($identifierNames as $identifierName) {
                            if (!isset( $subEntityData[$identifierName] ) || empty( $subEntityData[$identifierName] )) {
                                continue; // Should mean we are working with a new entity to be created
                            }
                            $identifierValues[$identifierName] = $subEntityData[$identifierName];
                        }

                        $subEntity = false;
                        if (count($identifierValues) === count($identifierNames)) {
                            $subEntity = $objectManager->find($target, $identifierValues);
                        }

                        if (!$subEntity) {
                            $subEntity = new $target;
                        }

                        $hydrator = $this->getEntityHydrator($target, $objectManager);
                        $hydrator->hydrate($subEntityData, $subEntity);
                        $objectManager->persist($subEntity);

                        // Replace the data with the entity and let the downstream parent hydrator handle persistence
                        $subEntityData = $subEntity;
                    }
                }
            }
        }

        $event->setData($originalData);

        return $originalData;
    }

    /**
     * @param $entityClass
     *
     * @param $objectManager
     *
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
        if ($hydrator === false || !$hydrator instanceof HydratorInterface) {
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
            $config = $this->getServiceManager()->get('Config');
            $config = $config[DoctrineHydratorFactory::FACTORY_NAMESPACE];

            if (!empty( $config )) {
                $this->entityHydratorMap = [ ];
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
     * @return ServiceLocatorInterface
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     *
     * @return $this
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }
}
