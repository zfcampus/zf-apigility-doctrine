<?php

namespace ZF\Apigility\Doctrine\Server\Hydrator;

use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Stdlib\Hydrator;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;
use Zend\Stdlib\Hydrator\StrategyEnabledInterface;
/**
 * Class AbstractDoctrineResourceFactory
 *
 * @package ZF\Apigility\Doctrine\Server\Resource
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
    public function canCreateServiceWithName(ServiceLocatorInterface $hydratorManager, $name, $requestedName)
    {
        if (array_key_exists($requestedName, $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        $serviceManager = $hydratorManager->getServiceLocator();

        if (!$serviceManager->has('Config')) {
            return false;
        }

        // Validate object is set
        $config = $serviceManager->get('Config');
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
    public function createServiceWithName(ServiceLocatorInterface $hydratorManager, $name, $requestedName)
    {

        $serviceManager = $hydratorManager->getServiceLocator();

        $config   = $serviceManager->get('Config');
        $config   = $config[self::FACTORY_NAMESPACE][$requestedName];

        $objectManager = $this->loadObjectManager($serviceManager, $config);
        $entityHydrator = $this->loadEntityHydrator($serviceManager, $config, $objectManager);
        $doctrineModuleHydrator = $this->loadDoctrineModuleHydrator($serviceManager, $config, $objectManager);

        $hydrator = new DoctrineHydrator();

        if ($entityHydrator) {
            $hydrator->setHydrateService($entityHydrator);
            $hydrator->setExtractService($entityHydrator);

            // Doctrine ODM Hydrators only have hydrate() method, so add fallback to Doctrine Module Hydrator.
            if ($entityHydrator instanceof \Doctrine\ODM\MongoDB\Hydrator\HydratorInterface) {
                $hydrator->setExtractService($doctrineModuleHydrator);
            }
        } else {
            $hydrator->setHydrateService($doctrineModuleHydrator);
            $hydrator->setExtractService($doctrineModuleHydrator);
        }

        return $hydrator;
    }

    /**
     * @param $className
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function normalizeClassname($className)
    {
        return '\\' . ltrim($className, '\\');
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @param                         $config
     *
     * @return ObjectManager
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     */
    protected function loadObjectManager(ServiceLocatorInterface $serviceManager, $config)
    {
        if ($serviceManager->has($config['object_manager'])) {
            $objectManager = $serviceManager->get($config['object_manager']);
        } else {
            // @codeCoverageIgnoreStart
            throw new ServiceNotCreatedException('The object_manager could not be found.');
        }

            // @codeCoverageIgnoreEnd
        return $objectManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @param                         $config
     * @param ObjectManager $objectManager
     *
     * @return mixed
     */
    protected function loadEntityHydrator(ServiceLocatorInterface $serviceManager, $config, $objectManager)
    {
        /** @var Query\ApigilityFetchAllQuery $queryBuilder */
        if (class_exists('\\Doctrine\\ORM\\EntityManager') && $objectManager instanceof \Doctrine\ORM\EntityManager) {

            // Create hydrator
            $className = 'DoctrineORMModule\\Stdlib\\Hydrator\\DoctrineEntity';
            $reflection = new \ReflectionClass($className);
            $hydrator = $reflection->newInstance($objectManager, $config['entity_class'], $config['by_value']);

        } elseif (class_exists('\\Doctrine\\ODM\\MongoDB\\DocumentManager') && $objectManager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $hydratorFactory = $objectManager->getHydratorFactory();
            $hydrator = $hydratorFactory->getHydratorFor($config['entity_class']);

        } else {
            // @codeCoverageIgnoreStart
            return new ApiProblem(500, 'No valid doctrine module is found for objectManager ' . get_class($objectManager));
        }
            // @codeCoverageIgnoreEnd

        // Configure hydrator:
        $this->configureHydratorStrategies($hydrator, $serviceManager, $config, $objectManager);

        return $hydrator;
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @param                         $config
     * @param ObjectManager $objectManager
     *
     * @return HydratorInterface
     */
    protected function loadDoctrineModuleHydrator(ServiceLocatorInterface $serviceManager, $config, $objectManager)
    {
        $hydrator = new Hydrator\DoctrineObject($objectManager, $config['entity_class'], $config['by_value']);
        $this->configureHydratorStrategies($hydrator, $serviceManager, $config, $objectManager);
        return $hydrator;
    }

    /**
     * @param                         $hydrator
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $config
     * @param                         $objectManager
     *
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @return void
     */
    protected function configureHydratorStrategies($hydrator, ServiceLocatorInterface $serviceManager, $config, $objectManager)
    {
        if (!($hydrator instanceof StrategyEnabledInterface) || !isset($config['strategies'])) {
            return;
        }

        foreach ($config['strategies'] as $field => $strategyKey) {
            if (!$serviceManager->has($strategyKey)) {
                if (!class_exists($strategyKey)) {
                    die('no class');
                }
                die('servicce does not have strategy');
                throw new ServiceNotCreatedException(sprintf('Invalid strategy %s for field %s', $strategyKey, $field));
            }

            $strategy = $serviceManager->get($strategyKey);
            if (!$strategy instanceof StrategyInterface) {
                throw new ServiceNotCreatedException(sprintf('Invalid strategy class %s for field %s', get_class($strategy), $field));
            }

            // Attach object manager:
            if ($strategy instanceof ObjectManagerAwareInterface) {
                $strategy->setObjectManager($objectManager);
            }

            $hydrator->addStrategy($field, $strategy);
        }
    }
}
