<?php

namespace SoliantConsulting\Apigility\Server\Resource;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractDoctrineResourceFactory
 *
 * @package SoliantConsulting\Apigility\Server\Resource
 */
class DoctrineResourceFactory implements AbstractFactoryInterface
{

    const FACTORY_NAMESPACE = 'zf-rest-doctrine-resource';

    /**
     * Cache of canCreateServiceWithName lookups
     * @var array
     */
    protected $lookupCache = array();

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return bool
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (array_key_exists($requestedName, $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        if (!$serviceLocator->has('Config')) {
            return false;
        }

        // Validate object is set
        $config = $serviceLocator->get('Config');
        $namespace = self::FACTORY_NAMESPACE;
        if (!isset($config[$namespace]) || !is_array($config[$namespace]) || !isset($config[$namespace][$requestedName])) {
            $this->lookupCache[$requestedName] = false;
            return false;
        }

        // Validate if class a valid DoctrineResource
        $className = isset($config['class']) ? $config['class'] : $requestedName;
        $className = $this->normalizeClassname($className);
        $reflection = new \ReflectionClass($className);
        if (!$reflection->isSubclassOf('\SoliantConsulting\Apigility\Server\Resource\DoctrineResource')) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid DoctrineResource "class" is specified for listener %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        // Validate object manager
        $config = $config[$namespace];
        if (!isset($config[$requestedName]) || !isset($config[$requestedName]['object_manager'])) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "object_manager" is specified for listener %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        $this->lookupCache[$requestedName] = true;
        return true;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return DoctrineResource
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config   = $serviceLocator->get('Config');
        $config   = $config[self::FACTORY_NAMESPACE][$requestedName];

        $className = isset($config['class']) ? $config['class'] : $requestedName;
        $className = $this->normalizeClassname($className);
        $listener = new $className();

        $listener->setObjectManager($this->loadObjectManager($serviceLocator, $config));
        $listener->setHydrator($this->loadHydrator($serviceLocator, $config));

        return $listener;
    }

    /**
     * @param $className
     *
     * @return string
     */
    protected function normalizeClassname($className)
    {
        return '\\' . ltrim($className, '\\');
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     *
     * @return ObjectManager
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function loadObjectManager(ServiceLocatorInterface $serviceLocator, $config)
    {
        if ($serviceLocator->has($config['object_manager'])) {
            $objectManager = $serviceLocator->get($config['object_manager']);
        } else {
            throw new ServiceNotCreatedException('The object_manager could not be found.');
        }
        return $objectManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     *
     * @return HydratorInterface
     */
    protected function loadHydrator(ServiceLocatorInterface $serviceLocator, $config)
    {
        if (!isset($config['hydrator'])) {
            return null;
        }

        if ($serviceLocator->has($config['hydrator'])) {
            return null;
        }

        return $serviceLocator->get($config['hydrator']);
    }

}