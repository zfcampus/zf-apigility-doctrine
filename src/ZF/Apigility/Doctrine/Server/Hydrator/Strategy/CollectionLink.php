<?php

namespace ZF\Apigility\Doctrine\Server\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;
use DoctrineModule\Stdlib\Hydrator\Strategy\AbstractCollectionStrategy;
use ZF\Hal\Link\Link;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Filter\FilterChain;

/**
 * A field-specific hydrator for collecitons
 *
 * @returns Hal\Link
 */
class CollectionLink extends AbstractCollectionStrategy
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

        return $link;
    }

    public function hydrate($value)
    {
        // Hydration is not supported for collections.
        // A call to PATCH will use hydration to extract then hydrate
        // an entity.  In this process a collection will be included
        // so no error is thrown here.
    }
}
