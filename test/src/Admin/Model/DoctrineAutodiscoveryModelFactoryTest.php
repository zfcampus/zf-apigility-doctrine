<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use Interop\Container\ContainerInterface;
use Prophecy\Prophecy\ProphecyInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModelFactory;

class DoctrineAutodiscoveryModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProphecyInterface|ServiceLocatorInterface|ContainerInterface
     */
    private $container;

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->prophesize(ServiceLocatorInterface::class);
        $this->container->willImplement(ContainerInterface::class);
    }

    public function testFactoryRaisesExceptionIfConfigServiceIsMissing()
    {
        $factory = new DoctrineAutodiscoveryModelFactory();

        $this->container->has('config')->willReturn(false);

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('config service is not present');
        $factory($this->container->reveal());
    }

    public function testFactoryReturnsDoctrineAutodiscoveryModelComposingConfigAndContainer()
    {
        $factory = new DoctrineAutodiscoveryModelFactory();

        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([]);

        $model = $factory($this->container->reveal());

        $this->assertInstanceOf(DoctrineAutodiscoveryModel::class, $model);
        $this->assertAttributeEquals([], 'config', $model);
        $this->assertSame($this->container->reveal(), $model->getServiceLocator());
    }
}
