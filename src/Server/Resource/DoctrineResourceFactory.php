<?php

namespace ZF\Apigility\Doctrine\Server\Resource;

use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Stdlib\Hydrator;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZF\Apigility\Doctrine\Server\Collection\Query;

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
            // @codeCoverageIgnoreStart
            return false;
        }
            // @codeCoverageIgnoreEnd

        // Validate object is set
        $config = $serviceLocator->get('Config');

        if (!isset($config['zf-apigility']['doctrine-connected']) || !is_array($config['zf-apigility']['doctrine-connected']) || !isset($config['zf-apigility']['doctrine-connected'][$requestedName])) {
            $this->lookupCache[$requestedName] = false;

            return false;
        }

        // Validate if class a valid DoctrineResource
        $className = isset($config['class']) ? $config['class'] : $requestedName;
        $className = $this->normalizeClassname($className);
        $reflection = new \ReflectionClass($className);
        if (!$reflection->isSubclassOf('\ZF\Apigility\Doctrine\Server\Resource\DoctrineResource')) {
            // @codeCoverageIgnoreStart
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid DoctrineResource "class" is specified for listener %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }
        // @codeCoverageIgnoreEnd

        // Validate object manager
        $config = $config['zf-apigility']['doctrine-connected'];
        if (!isset($config[$requestedName]) || !isset($config[$requestedName]['object_manager'])) {
            // @codeCoverageIgnoreStart
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "object_manager" is specified for listener %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }
            // @codeCoverageIgnoreEnd

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
        $config   = $config['zf-apigility']['doctrine-connected'][$requestedName];

        $className = isset($config['class']) ? $config['class'] : $requestedName;
        $className = $this->normalizeClassname($className);

        $objectManager = $this->loadObjectManager($serviceLocator, $config);
        $hydrator = $this->loadHydrator($serviceLocator, $config, $objectManager);
        $fetchAllQuery = $this->loadQueryProvider($serviceLocator, $config, $objectManager);

        $listener = new $className();
        $listener->setObjectManager($objectManager);
        $listener->setHydrator($hydrator);
        $listener->setFetchAllQuery($fetchAllQuery);
        $listener->setServiceManager($serviceLocator);

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
    protected function loadHydrator(ServiceLocatorInterface $serviceLocator, $config)
    {
        // @codeCoverageIgnoreStart
        if (!isset($config['hydrator'])) {
            return null;
        }

        if (!$serviceLocator->has('HydratorManager')) {
            return null;
        }

        $hydratorManager = $serviceLocator->get('HydratorManager');
        if (!$hydratorManager->has($config['hydrator'])) {
            return null;
        }
        // @codeCoverageIgnoreEnd
        return $hydratorManager->get($config['hydrator']);
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     * @param                         $objectManager
     *
     * @return Query\ApigilityFetchAllQuery
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function loadQueryProvider(ServiceLocatorInterface $serviceLocator, $config, $objectManager)
    {
        $queryManager = $serviceLocator->get('ZfCollectionQueryManager');
        if (class_exists('\\Doctrine\\ORM\\EntityManager') && $objectManager instanceof \Doctrine\ORM\EntityManager) {
            $fetchAllQuery = $queryManager->get('default-orm-query');
            $filterManager = $serviceLocator->get('ZfOrmCollectionFilterManager');
        } elseif (class_exists('\\Doctrine\\ODM\\MongoDB\\DocumentManager') && $objectManager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $fetchAllQuery = $queryManager->get('default-odm-query');
            $filterManager = $serviceLocator->get('ZfOdmCollectionFilterManager');
        } else {
            // @codeCoverageIgnoreStart
            throw new ServiceNotCreatedException('No valid doctrine module is found for objectManager.');
        }
        // @codeCoverageIgnoreEnd

        // Use custom query provider
        if (isset($config['query_provider'])) {
            if (!$queryManager->has($config['query_provider'])) {
                throw new ServiceNotCreatedException(sprintf('Invalid query provider %s.', $config['query_provider']));
            }

            $fetchAllQuery = $queryManager->get($config['query_provider']);
        }

        /** @var $fetchAllQuery Query\ApigilityFetchAllQuery */
        $fetchAllQuery->setObjectManager($objectManager);
        $fetchAllQuery->setFilterManager($filterManager);
        return $fetchAllQuery;
    }

}
