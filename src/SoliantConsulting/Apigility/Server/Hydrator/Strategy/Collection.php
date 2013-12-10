<?php

namespace SoliantConsulting\Apigility\Server\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use DoctrineModule\Stdlib\Hydrator\Strategy\AbstractCollectionStrategy;
use ZF\Hal\Collection as HalCollection;
use ZF\Hal\Link\Link;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class Collection extends AbstractCollectionStrategy
    implements StrategyInterface, ServiceManagerAwareInterface //, ObjectManagerAwareInterface
{
    use ProvidesObjectManager;

    protected $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function extract($value)
    {
        $config = $this->getServiceManager()->get('Config');
        if (!method_exists($value, 'getTypeClass') or !isset($config['zf-hal']['metadata_map'][$value->getTypeClass()->name])) {
            return;
        }

        $config = $config['zf-hal']['metadata_map'][$value->getTypeClass()->name];

        $link = new Link($this->getCollectionName());
        $link->setRoute($config['route_name']);
        $link->setRouteParams(array('id' => null));

        $mapping = $value->getMapping();

        $link->setRouteOptions(array(
            'query' => array(
                'query' => array(
                    array('field' =>$mapping['mappedBy'], 'type'=>'eq', 'value' => $value->getOwner()->getId()),
                ),
            ),
        ));

        return $link;
    }

    public function hydrate($value)
    {
        // hydrate
        die('hydrate apigility collection');
    }
}