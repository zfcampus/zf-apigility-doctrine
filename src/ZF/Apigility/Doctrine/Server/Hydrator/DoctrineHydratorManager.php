<?php

namespace ZF\Apigility\Doctrine\Server\Hydrator;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;
use Zend\Stdlib\Hydrator\HydratorInterface;

/**
 * Class DoctrineHydratorManager
 *
 * @package ZF\Apigility\Doctrine\Server\Hydrator
 */
class DoctrineHydratorManager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     */
    public function has($name, $checkAbstractFactories = true, $usePeeringServiceManagers = true)
    {
        $serviceManager = $this->getServiceLocator();
        if (!$serviceManager->has('HydratorManager')) {
            return false;
        }

        $hydratorManager = $serviceManager->get('HydratorManager');
        $result = $hydratorManager->has($name, $checkAbstractFactories, $usePeeringServiceManagers);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function get($name, $options = array(), $usePeeringServiceManagers = true)
    {
        // Check if it is a global service
        $serviceManager = $this->getServiceLocator();
        if (!$serviceManager->has($name)) {
            return null;
        }

        // Load hydrator from doctrine hydrator factory
        $hydrator = $serviceManager->get($name, $options, $usePeeringServiceManagers);
        if (!$hydrator instanceof HydratorInterface) {
            return null;
        }

        return $hydrator;
    }

    /**
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof HydratorInterface) {
            // we're okay
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement Zend\Stdlib\Hydrator\HydratorInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin))
        ));
    }

}