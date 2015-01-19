<?php

namespace ZF\Apigility\Doctrine\Server\Event;

use Zend\EventManager\Event;
use ZF\Rest\ResourceEvent;

/**
 * Class DoctrineResourceEvent
 *
 * @package ZF\Apigility\Doctrine\Server\Event
 */
class DoctrineResourceEvent extends Event
{
    const EVENT_FETCH_POST     = 'fetch.post';
    const EVENT_FETCH_ALL_PRE  = 'fetch-all.pre';
    const EVENT_FETCH_ALL_POST = 'fetch-all.post';
    const EVENT_CREATE_PRE     = 'create.pre';
    const EVENT_CREATE_POST    = 'create.post';
    const EVENT_UPDATE_PRE     = 'update.pre';
    const EVENT_UPDATE_POST    = 'update.post';
    const EVENT_PATCH_PRE      = 'patch.pre';
    const EVENT_PATCH_POST     = 'patch.post';
    const EVENT_DELETE_PRE     = 'delete.pre';
    const EVENT_DELETE_POST    = 'delete.post';

    /**
     * @var ResourceEvent
     */
    protected $resourceEvent;

    /**
     * @var mixed
     */
    protected $entity;

    /**
     * @var mixed
     */
    protected $collection;

    /**
     * @var Doctrine\ORM\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @param Doctrine\ORM\QueryBuilder $queryBuilder
     */
    public function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param mixed $collection
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param \ZF\Rest\ResourceEvent $resourceEvent
     */
    public function setResourceEvent($resourceEvent)
    {
        $this->resourceEvent = $resourceEvent;

        return $this;
    }

    /**
     * @return \ZF\Rest\ResourceEvent
     */
    public function getResourceEvent()
    {
        return $this->resourceEvent;
    }
}
