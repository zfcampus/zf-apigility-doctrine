<?php

namespace ZF\Apigility\Doctrine\Server\Query\CreateFilter;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;

/**
 * Class DefaultCreateFilter
 *
 * @package ZF\Apigility\Doctrine\Server\Query\CreateFilter
 */
class DefaultCreateFilter extends AbstractCreateFilter
{
    /**
     * @param string $entityClass
     * @param array  $data
     *
     * @return array
     */
    public function filter(ResourceEvent $event, $entityClass, $data)
    {
        return $data;
    }
}
