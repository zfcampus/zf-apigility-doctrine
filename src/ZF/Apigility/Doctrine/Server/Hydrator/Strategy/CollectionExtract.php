<?php

namespace ZF\Apigility\Doctrine\Server\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;
use DoctrineModule\Stdlib\Hydrator\Strategy\AbstractCollectionStrategy;
use ZF\Hal\Collection;

/**
 * A field-specific hydrator for collecitons
 *
 * @returns HalCollection
 */
class CollectionExtract extends AbstractCollectionStrategy
    implements StrategyInterface
{
    public function extract($value)
    {
        $halCollection = new Collection($value);
        return $halCollection;
    }

    public function hydrate($value)
    {
        // Hydration is not supported for collections.
        // A call to PATCH will use hydration to extract then hydrate
        // an entity.  In this process a collection will be included
        // so no error is thrown here.
    }
}