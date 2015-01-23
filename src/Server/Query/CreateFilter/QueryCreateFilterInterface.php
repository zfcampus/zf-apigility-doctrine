<?php

namespace ZF\Apigility\Doctrine\Server\Query\CreateFilter;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use ZF\Rest\ResourceEvent;

interface QueryCreateFilterInterface extends ObjectManagerAwareInterface
{
    /**
     * @param string $entityClass
     * @param array  $data
     *
     * @return array
     */
    public function filter(ResourceEvent $event, $entityClass, $data);
}
