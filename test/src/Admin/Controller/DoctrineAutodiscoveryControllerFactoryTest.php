<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Controller;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Zend\ServiceManager\AbstractPluginManager;
use ZF\Apigility\Doctrine\Admin\Controller\DoctrineAutodiscoveryController;
use ZF\Apigility\Doctrine\Admin\Controller\DoctrineAutodiscoveryControllerFactory;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;

class DoctrineAutodiscoveryControllerFactoryTest extends TestCase
{
    /**
     * @var ProphecyInterface|ContainerInterface
     */
    private $container;

    /**
     * @var DoctrineAutodiscoveryModel
     */
    private $model;

    protected function setUp()
    {
        parent::setUp();

        $this->model = $this->prophesize(DoctrineAutodiscoveryModel::class)->reveal();
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get(DoctrineAutodiscoveryModel::class)->willReturn($this->model);
    }

    public function testInvokableFactoryReturnsDoctrineAutodiscoveryController()
    {
        $factory = new DoctrineAutodiscoveryControllerFactory();
        $controller = $factory($this->container->reveal(), DoctrineAutodiscoveryController::class);

        $this->assertInstanceOf(DoctrineAutodiscoveryController::class, $controller);
        $this->assertAttributeSame($this->model, 'model', $controller);
    }

    public function testLegacyFactoryReturnsDoctrineAutodiscoveryController()
    {
        $controllers = $this->prophesize(AbstractPluginManager::class);
        $controllers->getServiceLocator()->willReturn($this->container->reveal());

        $factory = new DoctrineAutodiscoveryControllerFactory();
        $controller = $factory->createService($controllers->reveal());

        $this->assertInstanceOf(DoctrineAutodiscoveryController::class, $controller);
        $this->assertAttributeSame($this->model, 'model', $controller);
    }
}
