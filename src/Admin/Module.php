<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin;

use ZF\Hal\Resource;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use ZF\Apigility\Admin\Model\RestServiceResource;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource;

class Module
{
    /**
     * @var \Closure
     */
    protected $urlHelper;

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $sm;

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

    public function getConfig()
    {
        return include __DIR__ . '/../../config/admin.config.php';
    }

    public function getServiceConfig()
    {
        return array('factories' => array(
            'ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceResource' => function ($services) {

                $resource = new Model\DoctrineMetadataServiceResource();

                return $resource;
            },

            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory' => function ($services) {
                if (!$services->has('ZF\Configuration\ModuleUtils')
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
                $moduleUtils   = $services->get('ZF\Configuration\ModuleUtils');
                $configFactory = $services->get('ZF\Configuration\ConfigResourceFactory');
                $sharedEvents  = $services->get('SharedEventManager');

                // Wire Doctrine-Connected fetch listener
                $sharedEvents->attach(
                    __NAMESPACE__ . '\Admin\Model\DoctrineRestServiceModel',
                    'fetch',
                    'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModel::onFetch'
                );

                return new Model\DoctrineRestServiceModelFactory(
                    $moduleUtils,
                    $configFactory,
                    $sharedEvents,
                    $moduleModel
                );
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

                return new DoctrineRestServiceResource($factory, $inputFilterModel, $documentationModel);
            },

            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceModelFactory' => function ($services) {
                if (!$services->has('ZF\Configuration\ModuleUtils')
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
                $moduleUtils   = $services->get('ZF\Configuration\ModuleUtils');
                $configFactory = $services->get('ZF\Configuration\ConfigResourceFactory');
                $sharedEvents  = $services->get('SharedEventManager');

                return new Model\DoctrineRpcServiceModelFactory(
                    $moduleUtils,
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
        ));
    }
}
