<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace SoliantConsulting\Apigility;

use SoliantConsulting\Apigility\Server\Hydrator\DoctrineHydratorManager;
use Zend\Config\Writer\PhpArray as PhpArrayWriter;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use ZF\Configuration\ConfigResource;
use ZF\Hal\Link\Link;
use ZF\Hal\Link\LinkCollection;
use ZF\Hal\Resource;
use ZF\Hal\View\HalJsonModel;

class Module
    implements BootstrapListenerInterface
{
    /**
     * @var \Closure
     */
    protected $urlHelper;

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $sm;

    /**
     * Listen to the bootstrap event
     *
     * @param EventInterface $e
     *
     * @return array
     */
    public function onBootstrap(EventInterface $e)
    {
        $app      = $e->getTarget();
        $services = $app->getServiceManager();

        if ($services->has('HydratorManager')) {
            // Create doctrine hydrator manager
            $doctrineHydratorManager = new DoctrineHydratorManager();
            $doctrineHydratorManager->setServiceLocator($services);

            // Add as peering service
            $hydratorManager = $services->get('HydratorManager');
            $hydratorManager->addPeeringServiceManager($doctrineHydratorManager);
            $hydratorManager->setRetrieveFromPeeringManagerFirst(true);
        }
    }

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
        return include __DIR__ . '/../../../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array('factories' => array(
            'SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceModelFactory' => function ($services) {
                if (!$services->has('ZF\Configuration\ModuleUtils')
                    || !$services->has('ZF\Configuration\ConfigResourceFactory')
                    || !$services->has('ZF\Apigility\Admin\Model\ModuleModel')
                    || !$services->has('SharedEventManager')
                ) {
                    throw new ServiceNotCreatedException(
                        'SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceModelFactory is missing one or more dependencies from ZF\Configuration'
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
            'SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceResource' => function ($services) {
                if (!$services->has('SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceModelFactory')) {
                    throw new ServiceNotCreatedException(
                        'SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceResource is missing one or more dependencies'
                    );
                }
                $factory = $services->get('SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceModelFactory');
                return new Admin\Model\DoctrineRestServiceResource($factory);
            },
        ));
    }
}
