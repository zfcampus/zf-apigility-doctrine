<?php

namespace ZFTestApigilityGeneral\Listener;

use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use ZF\Apigility\Doctrine\DoctrineResource;

class EventCatcher
{
    const EVENT_IDENTIFIER = DoctrineResource::class;

    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * @var array
     */
    protected $caughtEvents = [];

    /**
     * @param SharedEventManagerInterface $events
     */
    public function attachShared(SharedEventManagerInterface $events)
    {
        $listener = $events->attach(self::EVENT_IDENTIFIER, '*', [$this, 'listen']);

        if (! $listener) {
            $listener = [$this, 'listen'];
        }

        $this->listeners[] = $listener;
    }

    /**
     * @param SharedEventManagerInterface $events
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        $eventManagerVersion = method_exists($events, 'getEvents') ? 2 : 3;

        foreach ($this->listeners as $index => $listener) {
            switch ($eventManagerVersion) {
                case 2:
                    if ($events->detach(self::EVENT_IDENTIFIER, $listener)) {
                        unset($this->listeners[$index]);
                    }
                    break;
                case 3:
                    if ($events->detach($listener, self::EVENT_IDENTIFIER, '*')) {
                        unset($this->listeners[$index]);
                    }
                    break;
            }
        }
    }

    /**
     * @param Event $e
     */
    public function listen(Event $e)
    {
        $this->caughtEvents[] = $e->getName();
        array_unique($this->caughtEvents);
    }

    /**
     * @return array
     */
    public function getCaughtEvents()
    {
        return $this->caughtEvents;
    }
}
