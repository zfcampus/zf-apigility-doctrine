<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server\Resource;

use Doctrine\Common\Persistence\ObjectManager;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Hydrator\HydratorInterface;
use RuntimeException;

/**
 * Class AbstractDoctrineResourceFactory
 *
 * @package ZF\Apigility\Doctrine\Server\Resource
 */
class DoctrineResourceFactory implements AbstractFactoryInterface
{
    /**
     * Cache of canCreateServiceWithName lookups
     * @var array
     */
    protected $lookupCache = [];

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     *
     * @return bool
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (array_key_exists($requestedName, $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        if (! $serviceLocator->has('Config')) {
            // @codeCoverageIgnoreStart

            return false;
        }
            // @codeCoverageIgnoreEnd

        // Validate object is set
        $config = $serviceLocator->get('Config');

        if (! isset($config['zf-apigility']['doctrine-connected'])
            || ! is_array($config['zf-apigility']['doctrine-connected'])
            || ! isset($config['zf-apigility']['doctrine-connected'][$requestedName])
        ) {
            $this->lookupCache[$requestedName] = false;

            return false;
        }

        // Validate if class a valid DoctrineResource
        $className = isset($config['class']) ? $config['class'] : $requestedName;
        $className = $this->normalizeClassname($className);
        $reflection = new \ReflectionClass($className);
        if (! $reflection->isSubclassOf('\ZF\Apigility\Doctrine\Server\Resource\DoctrineResource')) {
            // @codeCoverageIgnoreStart
            throw new ServiceNotFoundException(
                sprintf(
                    '%s requires that a valid DoctrineResource "class" is specified for listener %s; no service found',
                    __METHOD__,
                    $requestedName
                )
            );
        }
        // @codeCoverageIgnoreEnd

        // Validate object manager
        $config = $config['zf-apigility']['doctrine-connected'];
        if (! isset($config[$requestedName]) || ! isset($config[$requestedName]['object_manager'])) {
            // @codeCoverageIgnoreStart
            throw new ServiceNotFoundException(
                sprintf(
                    '%s requires that a valid "object_manager" is specified for listener %s; no service found',
                    __METHOD__,
                    $requestedName
                )
            );
        }
            // @codeCoverageIgnoreEnd

        $this->lookupCache[$requestedName] = true;

        return true;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     *
     * @return DoctrineResource
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $serviceLocator->get('Config');
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
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                'No zf-rest configuration found for resource ' . $requestedName
            );
        }
            // @codeCoverageIgnoreEnd

        $className = isset($doctrineConnectedConfig['class']) ? $doctrineConnectedConfig['class'] : $requestedName;
        $className = $this->normalizeClassname($className);

        $objectManager = $this->loadObjectManager($serviceLocator, $doctrineConnectedConfig);
        $hydrator = $this->loadHydrator(
            $serviceLocator,
            $doctrineConnectedConfig,
            $doctrineHydratorConfig,
            $objectManager
        );
        $queryProviders = $this->loadQueryProviders($serviceLocator, $doctrineConnectedConfig, $objectManager);
        $queryCreateFilter = $this->loadQueryCreateFilter($serviceLocator, $doctrineConnectedConfig, $objectManager);
        $configuredListeners = $this->loadConfiguredListeners($serviceLocator, $doctrineConnectedConfig);

        /** @var DoctrineResource $listener */
        $listener = new $className();
        $listener->setSharedEventManager($serviceLocator->get('Application')->getEventManager()->getSharedManager());
        $listener->setObjectManager($objectManager);
        $listener->setHydrator($hydrator);
        $listener->setQueryProviders($queryProviders);
        $listener->setQueryCreateFilter($queryCreateFilter);
        $listener->setEntityIdentifierName($restConfig['entity_identifier_name']);
        $listener->setRouteIdentifierName($restConfig['route_identifier_name']);

        if (count($configuredListeners)) {
            foreach ($configuredListeners as $configuredListener) {
                $listener->getEventManager()->attach($configuredListener);
            }
        }

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
            // @codeCoverageIgnoreStart
            throw new ServiceNotCreatedException('The object_manager could not be found.');
        }
        // @codeCoverageIgnoreEnd

        return $objectManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     *
     * @return HydratorInterface
     */
    protected function loadHydrator(
        ServiceLocatorInterface $serviceLocator,
        array $doctrineConnectedConfig,
        array $doctrineHydratorConfig,
        $objectManager
    ) {

        // @codeCoverageIgnoreStart
        if (! isset($doctrineConnectedConfig['hydrator'])) {
            return null;
        }

        if (! $serviceLocator->has('HydratorManager')) {
            return null;
        }

        $hydratorManager = $serviceLocator->get('HydratorManager');
        if (! $hydratorManager->has($doctrineConnectedConfig['hydrator'])) {
            return null;
        }

        // Set the hydrator for the entity for this resource to the hydrator
        // configured for the resource.  This removes per-entity hydrator configuration
        // allowing multiple hydrators per resource.
        if (isset($doctrineConnectedConfig['hydrator'])) {
            $entityClass = $doctrineHydratorConfig[$doctrineConnectedConfig['hydrator']]['entity_class'];
            $viewHelpers  = $serviceLocator->get('ViewHelperManager');
            $hal = $viewHelpers->get('Hal');
            $hal->getEntityHydratorManager()->addHydrator($entityClass, $doctrineConnectedConfig['hydrator']);
        }

        // @codeCoverageIgnoreEnd

        return $hydratorManager->get($doctrineConnectedConfig['hydrator']);
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     * @param                         $objectManager
     *
     * @return ZF\Apigility\Doctrine\Query\Provider\FetchAll\FetchAllQueryProviderInterface
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function loadQueryCreateFilter(ServiceLocatorInterface $serviceLocator, $config, $objectManager)
    {
        $createFilterManager = $serviceLocator->get('ZfApigilityDoctrineQueryCreateFilterManager');
        $filterManagerAlias = (isset($config['query_create_filter'])) ? $config['query_create_filter'] : 'default';

        $queryCreateFilter = $createFilterManager->get($filterManagerAlias);

        // Set object manager for all query providers
        $queryCreateFilter ->setObjectManager($objectManager);

        return $queryCreateFilter;
    }


    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     * @param                         $objectManager
     *
     * @return ZF\Apigility\Doctrine\Query\Provider\FetchAll\FetchAllQueryProviderInterface
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function loadQueryProviders(ServiceLocatorInterface $serviceLocator, $config, $objectManager)
    {
        $queryProviders = [];
        $queryManager = $serviceLocator->get('ZfApigilityDoctrineQueryProviderManager');

        // Load default query provider
        if (class_exists('\\Doctrine\\ORM\\EntityManager')
            && $objectManager instanceof \Doctrine\ORM\EntityManager
        ) {
            $queryProviders['default'] = $queryManager->get('default_orm');
        } elseif (class_exists('\\Doctrine\\ODM\\MongoDB\\DocumentManager')
            && $objectManager instanceof \Doctrine\ODM\MongoDB\DocumentManager
        ) {
            $queryProviders['default'] = $queryManager->get('default_odm');
        } else {
            // @codeCoverageIgnoreStart
            throw new ServiceNotCreatedException('No valid doctrine module is found for objectManager.');
        }
        // @codeCoverageIgnoreEnd

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
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     *
     * @return array
     */
    protected function loadConfiguredListeners(ServiceLocatorInterface $serviceLocator, $config)
    {
        if (! isset($config['listeners'])) {
            return [];
        }

        $listeners = [];
        foreach ($config['listeners'] as $listener) {
            $listeners[] = $serviceLocator->get($listener);
        }

        return $listeners;
    }
}
