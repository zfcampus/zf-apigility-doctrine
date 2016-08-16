<?php

namespace ZF\Apigility\Doctrine\Server\Resource\EntityFactory;

/**
 * Defines a factory that creates Doctrine entity instances on behalf of DoctrineResource
 */
interface EntityFactoryInterface
{
    /**
     * Creates a Doctrine entity
     *
     * @param string $entityClass
     * @param array $data Request data before DoctrineResourceEvent is triggered (caution!).
     * @return object
     */
    public function createEntity($entityClass, $data = []);
}
