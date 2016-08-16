<?php

namespace ZF\Apigility\Doctrine\Server\Resource\EntityFactory;

/**
 * Creates an entity class using reflection to disable the class constructor
 */
final class DisabledConstructorEntityFactory implements EntityFactoryInterface
{
    /**
     * Creates a Doctrine entity
     *
     * @param string|object $entityClass
     * @param array $data Request data before DoctrineResourceEvent is triggered.
     * @return object
     */
    public function createEntity($entityClass, $data = [])
    {
        $reflectionClass = new \ReflectionClass($entityClass);
        // do nothing with $data

        return $reflectionClass->newInstanceWithoutConstructor();
    }
}
