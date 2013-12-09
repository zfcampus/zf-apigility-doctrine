<?php

namespace SoliantConsulting\Apigility\Server\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use DoctrineModule\Stdlib\Hydrator\Strategy\AbstractCollectionStrategy;
use ZF\Hal\Collection as HalCollection;
use ZF\Hal\Link\Link;

class Collection extends AbstractCollectionStrategy
    implements StrategyInterface, ObjectManagerAwareInterface
{
    use ProvidesObjectManager;

    public function extract($value)
    {
        $link = new Link($this->getCollectionName());

        return $link;
#        $self->setRoute($route);

#        $self->setRouteParams($routeParams);
#        $resource->getLinks()->add($self, true);



#        print_r(get_class_methods($value));

#        print_r(($value->count() . ' count'));
#die();
die($this->getCollectionName());
        return new HalCollection($this->getObject());

        return array(
            '_links' => array(
                'asdf' => 'fdas',
                'asdfasdf' => 'fdasfdas',
            ),
        );
        // extract
        print_r(get_class($value));
        die('extract apigility collection');
    }

    public function hydrate($value)
    {
        // hydrate
        die('hydrate apigility collection');
    }
}