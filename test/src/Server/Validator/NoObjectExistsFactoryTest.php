<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Server\Validator;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use DoctrineModule\Validator\NoObjectExists as NoObjectExistsOrigin;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\ServiceManager\ServiceManager;
use Zend\Validator\ValidatorPluginManager;
use ZF\Apigility\Doctrine\Server\Validator\NoObjectExists;

class NoObjectExistsFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectProphecy|ServiceManager
     */
    private $serviceManager;

    /**
     * @var ValidatorPluginManager
     */
    private $validators;

    /**
     * @var ObjectProphecy|ObjectRepository
     */
    private $objectRepository;

    protected function setUp()
    {
        parent::setUp();

        $config = include __DIR__ . '/../../../../config/server.config.php';
        $validatorsConfig = $config['validators'];

        $this->objectRepository = $this->prophesize(ObjectRepository::class);
        $this->serviceManager = $this->prophesize(ServiceManager::class);

        $this->validators = new ValidatorPluginManager($this->serviceManager->reveal(), $validatorsConfig);
    }

    public function testCreate()
    {
        $validator = $this->validators->get(
            NoObjectExists::class,
            [
                'object_repository' => $this->objectRepository->reveal(),
                'fields' => 'foo',
            ]
        );

        $this->assertInstanceOf(NoObjectExistsOrigin::class, $validator);
    }

    public function testCreateWithEntityClassProvided()
    {
        $entityManager = $this->prophesize(EntityManager::class);
        $entityManager->getRepository('MyEntity')->willReturn($this->objectRepository->reveal());

        $this->serviceManager->has('MvcTranslator')->willReturn(false);
        $this->serviceManager->get(EntityManager::class)->willReturn($entityManager->reveal());

        $validator = $this->validators->get(
            NoObjectExists::class,
            [
                'entity_class' => 'MyEntity',
                'fields' => 'foo',
            ]
        );

        $this->assertInstanceOf(NoObjectExistsOrigin::class, $validator);
    }
}
