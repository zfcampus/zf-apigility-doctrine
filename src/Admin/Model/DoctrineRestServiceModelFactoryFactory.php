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

class DoctrineRestServiceModelFactoryFactory
{
    /**
     * @param ContainerInterface $container
     * @return DoctrineRestServiceModelFactory
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
                DoctrineRestServiceModelFactory::class
            ));
        }

        $moduleModel    = $container->get(ModuleModel::class);
        $modulePathSpec = $container->get(ModulePathSpec::class);
        $configFactory  = $container->get(ConfigResourceFactory::class);
        $sharedEvents   = $container->get('SharedEventManager');

        // Wire Doctrine-Connected fetch listener
        $sharedEvents->attach(
            DoctrineRestServiceModel::class,
            'fetch',
            [DoctrineRestServiceModel::class, 'onFetch']
        );

        $instance = new DoctrineRestServiceModelFactory($modulePathSpec, $configFactory, $sharedEvents, $moduleModel);
        $instance->setServiceManager($container);

        return $instance;
    }
}
