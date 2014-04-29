<?php

namespace General\Listener;

use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * Class EventCatcher
 *
 * @package DbMongo\Listener
 */
class EventCatcher
    implements  SharedListenerAggregateInterface
{

    const EVENT_IDENTIFIER = 'ZF\Apigility\Doctrine\DoctrineResource';

    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * @var array
     */
    protected $caughtEvents = [];

    /**
     * {@inheritDoc}
     */
    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(self::EVENT_IDENTIFIER, '*', array($this, 'listen'));
    }

    /**
     * {@inheritDoc}
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->listeners as $listener) {
            $events->detach(self::EVENT_IDENTIFIER, $listener);
        }
        unset($this->listeners);
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
