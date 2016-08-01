<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server\Query\CreateFilter;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Doctrine\Common\Persistence\ObjectManager;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;

/**
 * Class DefaultCreateFilter
 *
 * @package ZF\Apigility\Doctrine\Server\Query\CreateFilter
 */
abstract class AbstractCreateFilter implements ObjectManagerAwareInterface, QueryCreateFilterInterface
{
    /**
     * @param string $entityClass
     * @param array  $data
     *
     * @return array
     */
    abstract public function filter(ResourceEvent $event, $entityClass, $data);

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Get the object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }
}
