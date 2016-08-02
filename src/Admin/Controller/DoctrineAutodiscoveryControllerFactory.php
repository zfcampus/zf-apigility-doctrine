<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Controller;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;

class DoctrineAutodiscoveryControllerFactory implements FactoryInterface
{
    /**
     * Create and return DoctrineAutodiscoveryController instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return DoctrineAutodiscoveryController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DoctrineAutodiscoveryModel $model */
        $model = $container->get(DoctrineAutodiscoveryModel::class);

        return new DoctrineAutodiscoveryController($model);
    }

    /**
     * Create and return DoctrineAutodiscoveryController instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DoctrineAutodiscoveryController
     */
    public function createService(ServiceLocatorInterface $container)
    {
        if ($container instanceof AbstractPluginManager) {
            $container = $container->getServiceLocator() ?: $container;
        }

        return $this($container, DoctrineAutodiscoveryController::class);
    }
}
