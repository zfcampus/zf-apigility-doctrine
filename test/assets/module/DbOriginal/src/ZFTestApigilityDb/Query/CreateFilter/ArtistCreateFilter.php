<?php

namespace ZFTestApigilityDb\Query\CreateFilter;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;
use ZF\Apigility\Doctrine\Server\Query\CreateFilter\AbstractCreateFilter;

class ArtistCreateFilter extends AbstractCreateFilter
{
    /**
     * @param string $entityClass
     * @param array  $data
     *
     * @return array
     */
    public function filter(ResourceEvent $event, $entityClass, $data)
    {
        $this->getObjectManager();
        return $data;
    }
}
