<?php

namespace SoliantConsulting\Apigility\Server\Hydrator;

use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;

/**
 * Class AbstractDoctrineResourceFactory
 *
 * @package SoliantConsulting\Apigility\Server\Resource
 */
class DoctrineHydratorFactory implements AbstractFactoryInterface
{

    const FACTORY_NAMESPACE = 'zf-rest-doctrine-hydrator';

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

        // Validate object manager
        $config = $config[$namespace];
        if (!isset($config[$requestedName]) || !isset($config[$requestedName]['object_manager'])) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "object_manager" is specified for hydrator %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        // Validate object class
        if (!isset($config[$requestedName]['entity_class'])) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "entity_class" is specified for hydrator %s; no service found',
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
     * @return DoctrineHydrator
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config   = $serviceLocator->get('Config');
        $config   = $config[self::FACTORY_NAMESPACE][$requestedName];

        $objectManager = $this->loadObjectManager($serviceLocator, $config);
        $entityHydrator = $this->loadEntityHydrator($serviceLocator, $config, $objectManager);
        $doctrineModuleHydrator = $this->loadDoctrineModuleHydrator($serviceLocator, $config, $objectManager);

        $hydrator = new DoctrineHydrator();
        $hydrator->setHydrateService($entityHydrator ? $entityHydrator : $doctrineModuleHydrator);
        $hydrator->setExtractService($doctrineModuleHydrator);

        return $hydrator;
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
     * @param ObjectManager $objectManager
     *
     * @return mixed
     */
    protected function loadEntityHydrator(ServiceLocatorInterface $serviceLocator, $config, $objectManager)
    {
        if (!$config['doctrine_hydrator']) {
            return null;
        }

        // Load hydrator data:
        $unitOfWork = $objectManager->getUnitOfWork();
        $flags = $objectManager->getClassMetadata($config['entity_class']);

        // Create hydrator
        $className = $this->normalizeClassname($config['doctrine_hydrator']);
        $reflection = new \ReflectionClass($className);
        $hydrator = $reflection->newInstance($objectManager, $unitOfWork, $flags);

        return $hydrator;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     * @param ObjectManager $objectManager
     *
     * @return HydratorInterface
     */
    protected function loadDoctrineModuleHydrator(ServiceLocatorInterface $serviceLocator, $config, $objectManager)
    {
        return new Hydrator\DoctrineObject($objectManager, $config['entity_class']);
    }

}