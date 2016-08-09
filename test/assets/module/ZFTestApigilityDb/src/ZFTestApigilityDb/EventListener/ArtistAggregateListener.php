<?php

namespace ZFTestApigilityDb\EventListener;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;

class ArtistAggregateListener implements ListenerAggregateInterface
{
    protected $listeners = [];

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            DoctrineResourceEvent::EVENT_CREATE_POST,
            [$this, 'createPost']
        );
    }

    public function createPost(DoctrineResourceEvent $event)
    {
        $objectManager = $event->getObjectManager();

        $event->getEntity();
        $event->getData();
        $event->getResourceEvent();
        $event->getEntityClassName();
        $event->getEntityId();
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }
}
