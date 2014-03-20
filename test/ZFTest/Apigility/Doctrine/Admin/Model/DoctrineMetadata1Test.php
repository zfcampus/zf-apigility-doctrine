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
use Zend\Filter\FilterChain;


class DoctrineMetadata1Test extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
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
    public function testDoctrineMetadataResource()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));

        $this->dispatch('/apigility/api/doctrine/doctrine.entitymanager.orm_default/metadata/Db%5CEntity%5CArtist', Request::METHOD_GET);
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertArrayHasKey('name', $body);
        $this->assertEquals('Db\Entity\Artist', $body['name']);

        $this->dispatch('/apigility/api/doctrine/doctrine.entitymanager.orm_default/metadata', Request::METHOD_GET);
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertArrayHasKey('_embedded', $body);
        $this->assertEquals('Db\Entity\Album', $body['_embedded']['doctrine-metadata'][0]['name']);
        $this->assertEquals('Db\Entity\Artist', $body['_embedded']['doctrine-metadata'][1]['name']);
    }

    public function testDoctrineService()
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


        $this->resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');
        $this->resource->setModuleName('DbApi');

        $entity = $this->resource->create($resourceDefinition);

        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $entity);
        $controllerServiceName = $entity->controllerServiceName;
        $this->assertNotEmpty($controllerServiceName);
        $this->assertContains('DbApi\V1\Rest\Artist\Controller', $controllerServiceName);

        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
               ->attachByName('StringToLower');

        $em = $serviceManager->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $em->getMetadataFactory();
        $entityMetadata = $metadataFactory->getMetadataFor("Db\\Entity\\Artist");

        foreach ($entityMetadata->associationMappings as $mapping) {
            switch ($mapping['type']) {
                case 4:
                    $rpcServiceResource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource');
                    $rpcServiceResource->setModuleName('DbApi');
                    $rpcServiceResource->create(array(
                        'service_name' => 'Artist' . $mapping['fieldName'],
                        'route' => '/db-test/artist[/:parent_id]/' . $filter($mapping['fieldName']) . '[/:child_id]',
                        'http_methods' => array(
                            'GET',
                        ),
                        'options' => array(
                            'target_entity' => $mapping['targetEntity'],
                            'source_entity' => $mapping['sourceEntity'],
                            'field_name' => $mapping['fieldName'],
                        ),
                    ));
                    break;
                default:
                    break;
            }
        }

    }
}
