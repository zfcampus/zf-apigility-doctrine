<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use ZF\Apigility\Admin\Model\ModuleModel;
use ZF\Apigility\Admin\Model\ModulePathSpec;
use ZF\Configuration\ConfigResourceFactory;

class DoctrineRpcServiceModelFactoryFactory
{
    /**
     * @param ContainerInterface $container
     * @return DoctrineRpcServiceModelFactory
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(ModulePathSpec::class)
            || ! $container->has(ModuleModel::class)
            || ! $container->has(ConfigResourceFactory::class)
            || ! $container->has('SharedEventManager')
        ) {
            throw new ServiceNotCreatedException(sprintf(
                '%s is missing one or more dependencies from ZF\Configuration',
                DoctrineRpcServiceModelFactory::class
            ));
        }

        $moduleModel    = $container->get(ModuleModel::class);
        $configFactory  = $container->get(ConfigResourceFactory::class);
        $modulePathSpec = $container->get(ModulePathSpec::class);
        $sharedEvents   = $container->get('SharedEventManager');

        return new DoctrineRpcServiceModelFactory($modulePathSpec, $configFactory, $sharedEvents, $moduleModel);
    }
}
