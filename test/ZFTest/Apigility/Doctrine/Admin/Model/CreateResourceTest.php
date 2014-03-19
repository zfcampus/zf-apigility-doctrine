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

class CreateResourceTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase //TestCase
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

        $tool = new SchemaTool($em);
        $res = $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        // Create DB
        $resourceDefinition = [
            "objectManager"=> "doctrine.entitymanager.orm_default",
            "serviceName" => "Artist",
            "entityClass" => "Db\\Entity\\Artist",
            "routeIdentifierName" => "artist_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/db-test/artist",
        ];

        // Verify ORM is working
        $artist = new \Db\Entity\Artist;
        $artist->setName('TestInsert');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();
        $found = $em->getRepository('Db\Entity\Artist')->find($artist->getId());
        $this->assertInstanceOf('Db\Entity\Artist', $found);

        $this->resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');
        $this->resource->setModuleName('DbApi');

        $entity = $this->resource->create($resourceDefinition);
        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $entity);
        $controllerServiceName = $entity->controllerServiceName;
        $this->assertNotEmpty($controllerServiceName);
        $this->assertContains('DbApi\V1\Rest\Artist\Controller', $controllerServiceName);

        $this->resource->delete($controllerServiceName);
    }
}
