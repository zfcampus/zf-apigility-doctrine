<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server\Resource;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use RuntimeException;
use Zend\Hydrator\HydratorInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Apigility\Doctrine\Server\Query\CreateFilter\QueryCreateFilterInterface;
use ZF\Hal\Plugin\Hal;

class DoctrineResourceFactory implements AbstractFactoryInterface
{
    /**
     * Can this factory create the requested service?
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        if (! isset($config['zf-apigility']['doctrine-connected'])
            || ! is_array($config['zf-apigility']['doctrine-connected'])
        ) {
            return false;
        }

        $config = $config['zf-apigility']['doctrine-connected'];//[$requestedName];

        if (! isset($config[$requestedName])
            || ! is_array($config[$requestedName])
            || ! $this->isValidConfig($config[$requestedName], $requestedName, $container)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Can this factory create the requested service? (v2)
     *
     * Provided for backwards compatiblity; proxies to canCreate().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this->canCreate($container, $requestedName);
    }

    /**
     * Create and return the doctrine-connected resource.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return DoctrineResource
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $doctrineConnectedConfig = $config['zf-apigility']['doctrine-connected'][$requestedName];
        $doctrineHydratorConfig = $config['doctrine-hydrator'];

        $restConfig = null;
        foreach ($config['zf-rest'] as $restControllerConfig) {
            if ($restControllerConfig['listener'] == $requestedName) {
                $restConfig = $restControllerConfig;
                break;
            }
        }

        if (is_null($restConfig)) {
            throw new RuntimeException(
                sprintf('No zf-rest configuration found for resource %s', $requestedName)
            );
        }

        $resourceClass = $this->getResourceClassFromConfig($doctrineConnectedConfig, $requestedName);
        $objectManager = $container->get($doctrineConnectedConfig['object_manager']);

        $hydrator = $this->loadHydrator($container, $doctrineConnectedConfig, $doctrineHydratorConfig);
        $queryProviders = $this->loadQueryProviders($container, $doctrineConnectedConfig, $objectManager);
        $queryCreateFilter = $this->loadQueryCreateFilter($container, $doctrineConnectedConfig, $objectManager);
        $configuredListeners = $this->loadConfiguredListeners($container, $doctrineConnectedConfig);

        /** @var DoctrineResource $listener */
        $listener = new $resourceClass();
        $listener->setSharedEventManager($container->get('Application')->getEventManager()->getSharedManager());
        $listener->setObjectManager($objectManager);
        $listener->setHydrator($hydrator);
        $listener->setQueryProviders($queryProviders);
        $listener->setQueryCreateFilter($queryCreateFilter);
        $listener->setEntityIdentifierName($restConfig['entity_identifier_name']);
        $listener->setRouteIdentifierName($restConfig['route_identifier_name']);

        if ($configuredListeners) {
            $events = $listener->getEventManager();
            foreach ($configuredListeners as $configuredListener) {
                $configuredListener->attach($events);
            }
        }

        return $listener;
    }

    /**
     * Retrieve the resource class based on the provided configuration.
     *
     * Defaults to ZF\Apigility\Doctrine\Server\Resource\DoctrineResource.
     *
     * @param array $config
     * @param string $requestedName
     * @return string
     * @throws ServiceNotCreatedException if the discovered resource class
     *     does not exist or is not a subclass of DoctrineResource.
     */
    protected function getResourceClassFromConfig($config, $requestedName)
    {
        $defaultClass = DoctrineResource::class;

        $resourceClass = isset($config['class']) ? $config['class'] : $requestedName;
        $resourceClass = $this->normalizeClassname($resourceClass);

        if (! class_exists($resourceClass) || ! is_subclass_of($resourceClass, $defaultClass)) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create instance for service "%s"; resource class "%s" cannot be found or does not extend %s',
                $requestedName,
                $resourceClass,
                $defaultClass
            ));
        }

        return $resourceClass;
    }

    /**
     * Tests if the configuration is valid
     *
     * If the configuration has a "object_manager" key, and that service exists,
     * then the configuration is valid.
     *
     * @param array $config
     * @param string $requestedName
     * @param ContainerInterface $container
     * @return bool
     */
    protected function isValidConfig(array $config, $requestedName, ContainerInterface $container)
    {
        if (! isset($config['object_manager'])
            || ! $container->has($config['object_manager'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Create and return the doctrine-connected resource (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @return DoctrineResource
     */
    public function createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this($container, $requestedName);
    }

    /**
     * @param string $className
     * @return string
     */
    protected function normalizeClassname($className)
    {
        return '\\' . ltrim($className, '\\');
    }

    /**
     * @param ContainerInterface $container
     * @param array $doctrineConnectedConfig
     * @param array $doctrineHydratorConfig
     * @return HydratorInterface
     */
    protected function loadHydrator(
        ContainerInterface $container,
        array $doctrineConnectedConfig,
        array $doctrineHydratorConfig
    ) {
        if (! isset($doctrineConnectedConfig['hydrator'])) {
            return null;
        }

        if (! $container->has('HydratorManager')) {
            return null;
        }

        $hydratorManager = $container->get('HydratorManager');
        if (! $hydratorManager->has($doctrineConnectedConfig['hydrator'])) {
            return null;
        }

        // Set the hydrator for the entity for this resource to the hydrator
        // configured for the resource.  This removes per-entity hydrator configuration
        // allowing multiple hydrators per resource.
        if (isset($doctrineConnectedConfig['hydrator'])) {
            $entityClass = $doctrineHydratorConfig[$doctrineConnectedConfig['hydrator']]['entity_class'];
            $viewHelpers  = $container->get('ViewHelperManager');
            /** @var Hal $hal */
            $hal = $viewHelpers->get('Hal');
            $hal->getEntityHydratorManager()->addHydrator($entityClass, $doctrineConnectedConfig['hydrator']);
        }


        return $hydratorManager->get($doctrineConnectedConfig['hydrator']);
    }

    /**
     * @param ContainerInterface $container
     * @param array $config
     * @param ObjectManager $objectManager
     * @return QueryCreateFilterInterface
     */
    protected function loadQueryCreateFilter(ContainerInterface $container, array $config, $objectManager)
    {
        $createFilterManager = $container->get('ZfApigilityDoctrineQueryCreateFilterManager');
        $filterManagerAlias = isset($config['query_create_filter']) ? $config['query_create_filter'] : 'default';

        /** @var QueryCreateFilterInterface $queryCreateFilter */
        $queryCreateFilter = $createFilterManager->get($filterManagerAlias);

        // Set object manager for all query providers
        $queryCreateFilter->setObjectManager($objectManager);

        return $queryCreateFilter;
    }

    /**
     * @param ContainerInterface $serviceLocator
     * @param array $config
     * @param ObjectManager $objectManager
     * @return array
     * @throws ServiceNotCreatedException
     */
    protected function loadQueryProviders(ContainerInterface $serviceLocator, array $config, $objectManager)
    {
        $queryProviders = [];
        $queryManager = $serviceLocator->get('ZfApigilityDoctrineQueryProviderManager');

        // Load default query provider
        if (class_exists(EntityManager::class)
            && $objectManager instanceof EntityManager
        ) {
            $queryProviders['default'] = $queryManager->get('default_orm');
        } elseif (class_exists(DocumentManager::class)
            && $objectManager instanceof DocumentManager
        ) {
            $queryProviders['default'] = $queryManager->get('default_odm');
        } else {
            throw new ServiceNotCreatedException('No valid doctrine module is found for objectManager.');
        }

        // Load custom query providers
        if (isset($config['query_providers'])) {
            foreach ($config['query_providers'] as $method => $plugin) {
                $queryProviders[$method] = $queryManager->get($plugin);
            }
        }

        // Set object manager for all query providers
        foreach ($queryProviders as $provider) {
            $provider->setObjectManager($objectManager);
        }

        return $queryProviders;
    }

    /**
     * @param ContainerInterface $container
     * @param array $config
     * @return array
     */
    protected function loadConfiguredListeners(ContainerInterface $container, array $config)
    {
        if (! isset($config['listeners'])) {
            return [];
        }

        $listeners = [];
        foreach ($config['listeners'] as $listener) {
            $listeners[] = $container->get($listener);
        }

        return $listeners;
    }
}
