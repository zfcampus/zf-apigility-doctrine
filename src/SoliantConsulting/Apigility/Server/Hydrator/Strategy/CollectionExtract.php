<?php

namespace SoliantConsulting\Apigility\Server\Hydrator\Strategy;

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
        throw new \Exception('Hydration of collection ' . $this->getCollectionName() . ' is not supported');
    }
}