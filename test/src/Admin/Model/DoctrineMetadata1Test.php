<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Filter\FilterChain;
use Zend\Http\Request;
use ZFTest\Apigility\Doctrine\TestCase;

class DoctrineMetadata1Test extends TestCase
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

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);

        $this->dispatch(
            '/apigility/api/doctrine/doctrine.entitymanager.orm_default/metadata/Db%5CEntity%5CArtist',
            Request::METHOD_GET
        );
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertArrayHasKey('name', $body);
        $this->assertEquals('Db\Entity\Artist', $body['name']);

        $this->dispatch('/apigility/api/doctrine/doctrine.entitymanager.orm_default/metadata', Request::METHOD_GET);
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertArrayHasKey('_embedded', $body);
    }

    public function testDoctrineService()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $res = $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        // Create DB
        $resourceDefinition = [
            "objectManager" => "doctrine.entitymanager.orm_default",
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
                    $rpcServiceResource = $serviceManager->get(
                        'ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource'
                    );
                    $rpcServiceResource->setModuleName('DbApi');
                    $rpcServiceResource->create([
                        'service_name' => 'Artist' . $mapping['fieldName'],
                        'route' => '/db-test/artist[/:parent_id]/' . $filter($mapping['fieldName']) . '[/:child_id]',
                        'http_methods' => [
                            'GET',
                            'PUT',
                            'POST',
                        ],
                        'options' => [
                            'target_entity' => $mapping['targetEntity'],
                            'source_entity' => $mapping['sourceEntity'],
                            'field_name' => $mapping['fieldName'],
                        ],
                        'selector' => 'custom selector',
                    ]);
                    break;
                default:
                    break;
            }
        }
    }
}
