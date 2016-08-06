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

class DoctrineRestServiceResourceFactory
{
    /**
     * @param ContainerInterface $container
     * @return DoctrineRestServiceResource
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(DoctrineRestServiceModelFactory::class)
            || ! $container->has(InputFilterModel::class)
            || ! $container->has(DocumentationModel::class)
        ) {
            throw new ServiceNotCreatedException(sprintf(
                '%s is missing one or more dependencies',
                DoctrineRestServiceResource::class
            ));
        }

        return new DoctrineRestServiceResource(
            $container->get(DoctrineRestServiceModelFactory::class),
            $container->get(InputFilterModel::class),
            $container->get(DocumentationModel::class)
        );
    }
}
