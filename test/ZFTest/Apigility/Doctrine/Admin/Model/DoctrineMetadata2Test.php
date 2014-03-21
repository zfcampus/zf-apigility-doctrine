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

class DoctrineMetadata2Test extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
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

    public function testDoctrineService()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));

        $this->dispatch('/apigility/api/module/DbApi/doctrine/DbApi%5CV1%5CRest%5CArtist%5CController', Request::METHOD_GET);
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertArrayHasKey('controller_service_name', $body);
        $this->assertEquals('DbApi\V1\Rest\Artist\Controller', $body['controller_service_name']);

        $this->dispatch('/apigility/api/module/DbApi/doctrine?version=1', Request::METHOD_GET);
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('DbApi\V1\Rest\Artist\Controller', $body['_embedded']['doctrine'][0]['controller_service_name']);

        $this->dispatch('/apigility/api/module/DbApi/doctrine', Request::METHOD_GET);
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('DbApi\V1\Rest\Artist\Controller', $body['_embedded']['doctrine'][0]['controller_service_name']);

        $this->resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');
        $this->resource->setModuleName('DbApi');

        $this->rpcResource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource');
        $this->rpcResource->setModuleName('DbApi');
        $this->rpcResource->patch('DbApi\\V1\\Rpc\\Artistalbum\\Controller', array(
            'routematch' => '/doctrine-rpc-changed/test',
            'httpmethods' => array('GET', 'POST', 'PUT'),
            'selector' => 'new selector',
            'accept_whitelist' => array('new whitelist'),
            'content_type_whitelist' => array('new content whitelist'),
        ));

        // Test get model returns cached model
        $this->assertEquals($this->rpcResource->getModel(), $this->rpcResource->getModel());
        $this->assertEquals($this->rpcResource->getModuleName(), $this->rpcResource->getModuleName());


        foreach ($body['_embedded']['doctrine'] as $service) {
            $this->resource->delete($service['controller_service_name']);
        }
        $this->dispatch('/apigility/api/module/DbApi/doctrine-rpc?version=1', Request::METHOD_GET);
        $this->dispatch('/apigility/api/module/DbApi/doctrine-rpc', Request::METHOD_GET);
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('DbApi\V1\Rpc\Artistalbum\Controller', $body['_embedded']['doctrine-rpc'][0]['controller_service_name']);

        foreach ($body['_embedded']['doctrine-rpc'] as $rpc) {
            $this->rpcResource->delete($rpc['controller_service_name']);
        }

    }
}
