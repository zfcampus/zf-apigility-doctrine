<?php
namespace ZFTestApigilityGeneral;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use ZF\Apigility\Provider\ApigilityProviderInterface;
use ZFTestApigilityGeneral\Listener\EventCatcher;

class Module implements ApigilityProviderInterface, BootstrapListenerInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__,
                ],
            ],
        ];
    }

    /**
     * Add the event catcher
     *
     * @param EventInterface $e
     *
     * @return array
     */
    public function onBootstrap(EventInterface $e)
    {
        $application = $e->getApplication();
        $serviceManager = $application->getServiceManager();
        $eventManager = $application->getEventManager();
        $sharedEvents = $eventManager->getSharedManager();

        /** @var EventCatcher $eventCatcher */
        $eventCatcher = $serviceManager->get(EventCatcher::class);
        $eventCatcher->attachShared($sharedEvents);
    }
}
