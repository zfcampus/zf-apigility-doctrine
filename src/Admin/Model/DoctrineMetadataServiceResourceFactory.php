<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Interop\Container\ContainerInterface;

class DoctrineMetadataServiceResourceFactory
{
    /**
     * @param ContainerInterface $container
     * @return DoctrineMetadataServiceResource
     */
    public function __invoke(ContainerInterface $container)
    {
        $instance = new DoctrineMetadataServiceResource();
        $instance->setServiceManager($container);

        return $instance;
    }
}
