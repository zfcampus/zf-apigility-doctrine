<?php
namespace ZF\Apigility\Doctrine\Server\Delegator;

use Phpro\DoctrineHydrationModule\Hydrator\DoctrineHydrator;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Apigility\Doctrine\Server\Hydrator\Strategy\CollectionExtract;

class HydrationDelegator implements DelegatorFactoryInterface
{
    private $zoom = array();

    /**
     * A factory that creates delegates of a given hydrator
     *
     * @param ServiceLocatorInterface $serviceLocator the service locator which requested the service
     * @param string $name the normalized service name
     * @param string $requestedName the requested service name
     * @param callable $callback the callback that is responsible for creating the service
     *
     * @return mixed
     */
    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        $hydrator = call_user_func($callback);

        if (!$hydrator instanceof DoctrineHydrator) {
            throw new \InvalidArgumentException("Can't use zoom with a non-doctrine hydrator");
        }

        foreach ($this->zoom as $collectionName) {
            if ($hydrator->getExtractService()->hasStrategy($collectionName)) {
                $hydrator->getExtractService()->removeStrategy($collectionName);
                $hydrator->getExtractService()->addStrategy($collectionName, new CollectionExtract());
            }
        }

        return $hydrator;
    }

    public function setZoom(array $zoom)
    {
        $this->zoom = $zoom;
    }
}