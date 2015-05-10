<?php

namespace ZF\Apigility\Doctrine\Server\Event\Listener;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Stdlib\Hydrator\HydratorAwareInterface;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;

/**
 * Class CollectionListener
 *
 * @package ZF\Apigility\Doctrine\Server\Event\Listener
 */
class CollectionListener implements ListenerAggregateInterface
{
    protected $listeners = array();

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

    public function handleCollections(DoctrineResourceEvent $event)
    {
        $objectManager = $event->getObjectManager();
        $entity        = $event->getEntity();
        $originalData  = (array) $event->getData();

        $metadata = $objectManager->getClassMetadata(get_class($entity));

        $associations = $metadata->getAssociationNames();

        foreach ($associations as $association) {
            // Skip handling associations that arent in the data
            if ($metadata->isCollectionValuedAssociation($association)) {
                if (array_key_exists($association, $originalData)
                    && !empty( $originalData[$association] )
                    && ( is_array($originalData[$association])
                         || $originalData[$association] instanceof \Traversable )
                ) {
                    foreach ($originalData[$association] as &$subEntityData) {
                        $target          = $metadata->getAssociationTargetClass($association);
                        $identifierNames = $metadata->getIdentifierFieldNames($target);
                        if (empty( $identifierNames )) {
                            // TODO Investigate what we should do here, throw exception? for now handle downstream
                            continue;
                        }

                        $identifierValues = [ ];
                        foreach ($identifierNames as $identifierName) {
                            if (!isset( $subEntityData[$identifierName] ) || empty( $subEntityData[$identifierName] )) {
                                continue;
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

                        // TODO This could cause a catastrophe as it wouldnt have the appropriate strategies/listeners
                        // probably should fetch from "hydrator-manager" if possible
                        $hydrator = new DoctrineObject($objectManager);
                        $hydrator->hydrate($subEntityData, $subEntity);
                        $objectManager->persist($subEntity);
                        $objectManager->flush();

                        $subEntityData = $hydrator->extract($subEntity);

                        foreach ($identifierNames as $identifierName) {
                            $identifierValues[$identifierName] = $subEntityData[$identifierName];
                        }
                        $subEntityData = $identifierValues;
                    }
                }
            }
        }

        $event->setData($originalData);

        return $originalData;
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset( $this->listeners[$index] );
            }
        }
    }
}
