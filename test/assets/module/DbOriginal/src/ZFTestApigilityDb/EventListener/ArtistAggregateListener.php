<?php

namespace ZFTestApigilityDb\EventListener;

use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;

class ArtistAggregateListener implements ListenerAggregateInterface
{
    protected $listeners = array();

    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            DoctrineResourceEvent::EVENT_CREATE_POST,
            array($this, 'createPost')
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
