<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Zend\ServiceManager\ServiceManager;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceResource;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceResourceFactory;

class DoctrineMetadataServiceResourceFactoryTest extends TestCase
{
    /**
     * @var ProphecyInterface|ServiceManager
     */
    private $container;

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->prophesize(ServiceManager::class);
    }

    public function testFactoryReturnsDoctrineMetadataServiceResource()
    {
        $factory = new DoctrineMetadataServiceResourceFactory();

        $resource = $factory($this->container->reveal());

        $this->assertInstanceOf(DoctrineMetadataServiceResource::class, $resource);
        $this->assertSame($this->container->reveal(), $resource->getServiceManager());
    }
}
