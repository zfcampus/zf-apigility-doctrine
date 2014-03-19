<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
use Zend\Config\Writer\PhpArray;
use ZF\Apigility\Admin\Model\ModuleEntity;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModel;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModelFactory;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource;
use ZF\Configuration\ResourceFactory;
use ZF\Configuration\ModuleUtils;
use ZFTest\Util\ServiceManagerFactory;
use Doctrine\ORM\Tools\SchemaTool;

use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Application\Controller\IndexController;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;

class DoctrineMetadata extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
                include __DIR__ . '/../../../../../config/application.config.php'
        );
        parent::setUp();
    }

    public function tearDown()
    {
        # FIXME: Drop database from in-memory
    }

    /**
     * @see https://github.com/zfcampus/zf-apigility/issues/18
     */
    public function testCreateResource()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $this->dispatch('/apigility/api/doctrine/doctrine.entitymanager.orm_default/metadata/Db%5CEntity%5CArtist');

        print_r($this->getResponse());

#        $this->assertInstanceOf('

    }
}
