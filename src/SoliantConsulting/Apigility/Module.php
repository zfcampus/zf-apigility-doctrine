<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace SoliantConsulting\Apigility;

use Zend\Config\Writer\PhpArray as PhpArrayWriter;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use ZF\Configuration\ConfigResource;
use ZF\Hal\Link\Link;
use ZF\Hal\Link\LinkCollection;
use ZF\Hal\Resource;
use ZF\Hal\View\HalJsonModel;

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

    public function onBootstrap(MvcEvent $e)
    {
#        $app      = $e->getApplication();
#        $this->sm = $app->getServiceManager();
#        $events   = $app->getEventManager();
#        $events->attach('render', array($this, 'onRender'), 100);
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
}
