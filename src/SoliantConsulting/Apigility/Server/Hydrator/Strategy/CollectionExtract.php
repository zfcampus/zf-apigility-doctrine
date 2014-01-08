<?php

namespace SoliantConsulting\Apigility\Server\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;
use DoctrineModule\Stdlib\Hydrator\Strategy\AbstractCollectionStrategy;
use ZF\Hal\Collection as HalCollection;
use ZF\Hal\Link\Link;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Filter\FilterChain;
use ZF\Hal\Collection;

/**
 * A field-specific hydrator for collecitons
 *
 * @returns Hal\Link
 */
class CollectionExtract extends AbstractCollectionStrategy
    implements StrategyInterface, ServiceManagerAwareInterface
{
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
        $halCollection = new Collection($value);
        return $halCollection;

#            $this->serviceManager->get(

        die($this->getCollectionName());
#        die(get_class($value));
        return array('testing' => 'asdf');
        return $value;

        print_r(sizeof($value));die('extract');

        $config = $this->getServiceManager()->get('Config');
        if (!method_exists($value, 'getTypeClass') or !isset($config['zf-hal']['metadata_map'][$value->getTypeClass()->name])) {
            return;
        }

        $config = $config['zf-hal']['metadata_map'][$value->getTypeClass()->name];
        $mapping = $value->getMapping();

        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
               ->attachByName('StringToLower');

        $link = new Link($filter($mapping['fieldName']));
        $link->setRoute($config['route_name']);
        $link->setRouteParams(array('id' => null));

        $link->setRouteOptions(array(
            'query' => array(
                'query' => array(
                    array('field' => $mapping['mappedBy'], 'type'=>'eq', 'value' => $value->getOwner()->getId()),
                ),
            ),
        ));
        die('extracted field');
#print_r($link);#die();
        return $link;
    }

    public function hydrate($value)
    {
        throw new \Exception('Hydration of collection ' . $this->getCollectionName() . ' is not supported');
    }
}