<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\DependencyIndicatorInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceResource;

class Module implements
    ConfigProviderInterface,
    AutoloaderProviderInterface,
    ServiceProviderInterface,
    DependencyIndicatorInterface
{
    /**
     * Return an array for passing to Zend\Loader\AutoloaderFactory.
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * Returns configuration to merge with application configuration
     *
     * @return array|\Traversable
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/admin.config.php';
    }

    /**
     * Expected to return \Zend\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array|\Zend\ServiceManager\Config
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                // This resource pulls the object manager dynamically
                // so it needs access to the service manager
                'ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceResource' => function ($services) {
                    $instance = new DoctrineMetadataServiceResource();
                    $instance->setServiceManager($services);

                    return $instance;
                },
                'ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel' => function ($services) {
                    if (!$services->has('Config')) {
                        // @codeCoverageIgnoreStart
                        throw new ServiceNotCreatedException(
                            'Cannot create ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel
                            service because Config service is not present'
                        );
                        // @codeCoverageIgnoreEnd
                    }
                    $config = $services->get('Config');
                    $model= new Model\DoctrineAutodiscoveryModel($config);
                    $model->setServiceLocator($services);
                    return $model;
                },
                'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory' => function ($services) {
                    if (!$services->has('ZF\Apigility\Admin\Model\ModulePathSpec')
                        || !$services->has('ZF\Configuration\ConfigResourceFactory')
                        || !$services->has('ZF\Apigility\Admin\Model\ModuleModel')
                        || !$services->has('SharedEventManager')
                    ) {
                        // @codeCoverageIgnoreStart
                        throw new ServiceNotCreatedException(
                            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory is missing one'
                            . ' or more dependencies from ZF\Configuration'
                        );
                        // @codeCoverageIgnoreEnd
                    }
                    $moduleModel   = $services->get('ZF\Apigility\Admin\Model\ModuleModel');
                    $modulePathSpec = $services->get('ZF\Apigility\Admin\Model\ModulePathSpec');
                    $configFactory = $services->get('ZF\Configuration\ConfigResourceFactory');
                    $sharedEvents  = $services->get('SharedEventManager');

                    // Wire Doctrine-Connected fetch listener
                    $sharedEvents->attach(
                        __NAMESPACE__ . '\Model\DoctrineRestServiceModel',
                        'fetch',
                        __NAMESPACE__ . '\Model\DoctrineRestServiceModel::onFetch'
                    );

                    $instance = new Model\DoctrineRestServiceModelFactory(
                        $modulePathSpec,
                        $configFactory,
                        $sharedEvents,
                        $moduleModel
                    );
                    $instance->setServiceManager($services);

                    return $instance;
                },
                'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource' => function ($services) {
                    if (!$services->has('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory')) {
                        // @codeCoverageIgnoreStart
                        throw new ServiceNotCreatedException(
                            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource is missing one or more'
                            . ' dependencies'
                        );
                        // @codeCoverageIgnoreEnd
                    }
                    if (!$services->has('ZF\Apigility\Admin\Model\InputFilterModel')) {
                        // @codeCoverageIgnoreStart
                        throw new ServiceNotCreatedException(
                            'ZF\Apigility\Admin\Model\RestServiceResource is missing one or more dependencies'
                        );
                        // @codeCoverageIgnoreEnd
                    }
                    $factory = $services->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory');
                    $inputFilterModel = $services->get('ZF\Apigility\Admin\Model\InputFilterModel');
                    $documentationModel = $services->get('ZF\Apigility\Admin\Model\DocumentationModel');

                    return new Model\DoctrineRestServiceResource($factory, $inputFilterModel, $documentationModel);
                },

                'ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceModelFactory' => function ($services) {
                    if (!$services->has('ZF\Apigility\Admin\Model\ModulePathSpec')
                        || !$services->has('ZF\Configuration\ConfigResourceFactory')
                        || !$services->has('ZF\Apigility\Admin\Model\ModuleModel')
                        || !$services->has('SharedEventManager')
                    ) {
                        // @codeCoverageIgnoreStart
                        throw new ServiceNotCreatedException(
                            'ZF\Apigility\Admin\Model\RpcServiceModelFactory is missing one or more dependencies'
                            . ' from ZF\Configuration'
                        );
                        // @codeCoverageIgnoreEnd
                    }
                    $moduleModel   = $services->get('ZF\Apigility\Admin\Model\ModuleModel');
                    $configFactory = $services->get('ZF\Configuration\ConfigResourceFactory');
                    $modulePathSpec = $services->get('ZF\Apigility\Admin\Model\ModulePathSpec');
                    $sharedEvents  = $services->get('SharedEventManager');

                    return new Model\DoctrineRpcServiceModelFactory(
                        $modulePathSpec,
                        $configFactory,
                        $sharedEvents,
                        $moduleModel
                    );
                },

                'ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource' => function ($services) {
                    // @codeCoverageIgnoreStart
                    if (!$services->has('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceModelFactory')) {
                        throw new ServiceNotCreatedException(
                            'ZF\Apigility\Admin\Model\RpcServiceResource is missing RpcServiceModelFactory dependency'
                        );
                    }
                    if (!$services->has('ZF\Apigility\Admin\Model\InputFilterModel')) {
                        throw new ServiceNotCreatedException(
                            'ZF\Apigility\Admin\Model\RpcServiceResource is missing InputFilterModel dependency'
                        );
                    }
                    if (!$services->has('ControllerManager')) {
                        throw new ServiceNotCreatedException(
                            'ZF\Apigility\Admin\Model\RpcServiceResource is missing ControllerManager dependency'
                        );
                    }
                    // @codeCoverageIgnoreEnd

                    $factory = $services->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceModelFactory');
                    $inputFilterModel = $services->get('ZF\Apigility\Admin\Model\InputFilterModel');
                    $controllerManager = $services->get('ControllerManager');

                    return new Model\DoctrineRpcServiceResource($factory, $inputFilterModel, $controllerManager);
                },
            )
        );
    }

    /**
     * Expected to return an array of modules on which the current one depends on
     *
     * @return array
     */
    public function getModuleDependencies()
    {
        return array('ZF\Apigility\Admin');
    }
}
