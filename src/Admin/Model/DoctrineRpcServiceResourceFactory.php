<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use ZF\Apigility\Admin\Model\DocumentationModel;
use ZF\Apigility\Admin\Model\InputFilterModel;

class DoctrineRpcServiceResourceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(DoctrineRpcServiceModelFactory::class)
            || ! $container->has(InputFilterModel::class)
            || ! $container->has('ControllerManager')
            || ! $container->has(DocumentationModel::class)
        ) {
            throw new ServiceNotCreatedException(sprintf(
                '%s is missing one or more dependencies from ZF\Configuration',
                DoctrineRpcServiceResource::class
            ));
        }

        return new DoctrineRpcServiceResource(
            $container->get(DoctrineRpcServiceModelFactory::class),
            $container->get(InputFilterModel::class),
            $container->get('ControllerManager'),
            $container->get(DocumentationModel::class)
        );
    }
}
