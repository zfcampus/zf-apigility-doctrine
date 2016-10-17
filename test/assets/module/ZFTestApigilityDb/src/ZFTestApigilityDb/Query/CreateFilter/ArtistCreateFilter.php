<?php

namespace ZFTestApigilityDb\Query\CreateFilter;

use ZF\Apigility\Doctrine\Server\Query\CreateFilter\AbstractCreateFilter;
use ZF\Rest\ResourceEvent;

class ArtistCreateFilter extends AbstractCreateFilter
{
    /**
     * @param ResourceEvent $event
     * @param string $entityClass
     * @param array $data
     * @return array
     */
    public function filter(ResourceEvent $event, $entityClass, $data)
    {
        $this->getObjectManager();
        return $data;
    }
}
