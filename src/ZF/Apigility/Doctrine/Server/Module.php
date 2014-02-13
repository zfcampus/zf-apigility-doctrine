<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server;

use Zend\Config\Writer\PhpArray as PhpArrayWriter;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
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
        return include __DIR__ . '/../../../../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array('factories' => array(
            'ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceResource' => function ($services) {

                $resource = new Admin\Model\DoctrineMetadataServiceResource();

                return $resource;
            },

            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory' => function ($services) {
                if (!$services->has('ZF\Configuration\ModuleUtils')
                    || !$services->has('ZF\Configuration\ConfigResourceFactory')
                    || !$services->has('ZF\Apigility\Admin\Model\ModuleModel')
                    || !$services->has('SharedEventManager')
                ) {
                    throw new ServiceNotCreatedException(
                        'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory is missing one or more dependencies from ZF\Configuration'
                    );
                }
                $moduleModel   = $services->get('ZF\Apigility\Admin\Model\ModuleModel');
                $moduleUtils   = $services->get('ZF\Configuration\ModuleUtils');
                $configFactory = $services->get('ZF\Configuration\ConfigResourceFactory');
                $sharedEvents  = $services->get('SharedEventManager');

                // Wire DB-Connected fetch listener
                $sharedEvents->attach(__NAMESPACE__ . '\Admin\Model\DoctrineRestServiceModel', 'fetch', 'ZF\Apigility\Admin\Model\DbConnectedRestServiceModel::onFetch');

                return new Admin\Model\DoctrineRestServiceModelFactory($moduleUtils, $configFactory, $sharedEvents, $moduleModel);
            },
            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource' => function ($services) {
                if (!$services->has('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory')) {
                    throw new ServiceNotCreatedException(
                        'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource is missing one or more dependencies'
                    );
                }
                if (!$services->has('ZF\Apigility\Admin\Model\InputFilterModel')) {
                    throw new ServiceNotCreatedException(
                        'ZF\Apigility\Admin\Model\RestServiceResource is missing one or more dependencies'
                    );
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
                    throw new ServiceNotCreatedException(
                        'ZF\Apigility\Admin\Model\RpcServiceModelFactory is missing one or more dependencies from ZF\Configuration'
                    );
                }
                $moduleModel   = $services->get('ZF\Apigility\Admin\Model\ModuleModel');
                $moduleUtils   = $services->get('ZF\Configuration\ModuleUtils');
                $configFactory = $services->get('ZF\Configuration\ConfigResourceFactory');
                $sharedEvents  = $services->get('SharedEventManager');
                return new Admin\Model\DoctrineRpcServiceModelFactory($moduleUtils, $configFactory, $sharedEvents, $moduleModel);
            },

            'ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource' => function ($services) {
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
                $factory = $services->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceModelFactory');
                $inputFilterModel = $services->get('ZF\Apigility\Admin\Model\InputFilterModel');
                $controllerManager = $services->get('ControllerManager');
                return new Admin\Model\DoctrineRpcServiceResource($factory, $inputFilterModel, $controllerManager);
            },
        ));
    }
}
