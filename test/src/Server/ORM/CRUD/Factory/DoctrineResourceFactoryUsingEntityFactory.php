<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Server\ORM\CRUD\Factory;

use Cube\DoctrineEntityFactory\EntityFactoryInterface;
use Interop\Container\ContainerInterface;
use ZF\Apigility\Doctrine\Server\Resource\DoctrineResource;
use ZF\Apigility\Doctrine\Server\Resource\DoctrineResourceFactory;

class DoctrineResourceFactoryUsingEntityFactory
{
    private $entityFactory;

    /**
     * DoctrineResourceFactoryWithMockEntityFactory constructor.
     * @param EntityFactoryInterface $entityFactory
     */
    public function __construct(EntityFactoryInterface $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    /**
     * Create and return the doctrine-connected resource.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return DoctrineResource
     */
    public function __invoke(ContainerInterface $container, $requestedName)
    {
        $doctrineResourceFactory = new DoctrineResourceFactory();
        $args = func_get_args();
        if (count($args) == 3 && $args[2] !== null) {
            // compatibility with old ServiceManager
            unset($args[1]); // we don't need the one in the middle
        }
        $listener = call_user_func_array($doctrineResourceFactory, $args);
        $listener->setEntityFactory($this->entityFactory);

        return $listener;
    }
}
