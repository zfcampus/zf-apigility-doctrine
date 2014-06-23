<?php
namespace ZF\Apigility\Doctrine\Server\Resource\Query;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

interface ApigilityFetchQuery extends ServiceLocatorAwareInterface, ObjectManagerAwareInterface
{
    public function createQuery($entityClass, $id, $parameters);
}