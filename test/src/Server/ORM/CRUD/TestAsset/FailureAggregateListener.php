<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Server\ORM\CRUD\TestAsset;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;
use ZF\ApiProblem\ApiProblem;

class FailureAggregateListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /** @var string */
    private $eventName;

    /**
     * @param string $eventName
     */
    public function __construct($eventName)
    {
        $this->eventName = $eventName;
    }

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach($this->eventName, [$this, 'failure']);
    }

    /**
     * @param DoctrineResourceEvent $event
     * @return ApiProblem
     */
    public function failure(DoctrineResourceEvent $event)
    {
        $event->stopPropagation();
        return new ApiProblem(400, sprintf('ZFTestFailureAggregateListener: %s', $event->getName()));
    }
}
