<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

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
