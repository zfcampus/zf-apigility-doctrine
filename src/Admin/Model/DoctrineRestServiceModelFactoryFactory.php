<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Interop\Container\ContainerInterface;
use Zend\EventManager\SharedEventManagerInterface;
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

        $sharedEvents = $container->get('SharedEventManager');
        $this->attachSharedListeners($sharedEvents);

        $instance = new DoctrineRestServiceModelFactory(
            $container->get(ModulePathSpec::class),
            $container->get(ConfigResourceFactory::class),
            $sharedEvents,
            $container->get(ModuleModel::class)
        );
        $instance->setServiceManager($container);

        return $instance;
    }

    /**
     * Attach shared listeners to the DoctrineRestServiceModel.
     *
     * @param SharedEventManagerInterface $sharedEvents
     * @return void
     */
    private function attachSharedListeners(SharedEventManagerInterface $sharedEvents)
    {
        $sharedEvents->attach(
            DoctrineRestServiceModel::class,
            'fetch',
            [DoctrineRestServiceModel::class, 'onFetch']
        );
    }
}
