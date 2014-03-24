<?php
// Because of the code-generating of Apigility this script
// is used to setup the tests.  Use ~/test/bin/reset-tests
// to reset the output of this test if the unit tests
// fail the application.

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Filter\FilterChain;

class ApigilitySetupTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
                include __DIR__ . '/../../../../config/application.config.php'
        );
        parent::setUp();

        // Create testing modules
        $run = "rm -rf " . __DIR__ . "/../../../../assets/module/Db";
        $this->assertFalse((bool)exec($run));

        $run = "rm -rf " . __DIR__ . "/../../../../assets/module/DbApi";
        $this->assertFalse((bool)exec($run));

        $this->assertTrue(mkdir(__DIR__ . '/../../../../assets/module/Db'));
        $this->assertTrue(mkdir(__DIR__ . '/../../../../assets/module/DbApi'));

        $run = 'rsync -a ' . __DIR__ . '/../../../../assets/module/DbOriginal/* ' . __DIR__ . '/../../../../assets/module/Db';
        $this->assertFalse((bool)exec($run));

        $run = 'rsync -a ' . __DIR__ . '/../../../../assets/module/DbApiOriginal/* ' . __DIR__ . '/../../../../assets/module/DbApi';
        $this->assertFalse((bool)exec($run));
    }

    public function testBuildApi() {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $res = $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        // Create DB
        $this->resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');
        $this->resource->setModuleName('DbApi');

        $artistResourceDefinition = [
            "objectManager"=> "doctrine.entitymanager.orm_default",
            "serviceName" => "Artist",
            "entityClass" => "Db\\Entity\\Artist",
            "routeIdentifierName" => "artist_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/test/artist",
        ];

        $albumResourceDefinition = [
            "objectManager"=> "doctrine.entitymanager.orm_default",
            "serviceName" => "Album",
            "entityClass" => "Db\\Entity\\Album",
            "routeIdentifierName" => "album_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/test/album",
        ];


        $artistEntity = $this->resource->create($artistResourceDefinition);
        $albumEntity = $this->resource->create($albumResourceDefinition);

        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $artistEntity);
        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $albumEntity);

        // Build relation
        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
               ->attachByName('StringToLower');

        $em = $serviceManager->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $em->getMetadataFactory();
        $entityMetadata = $metadataFactory->getMetadataFor("Db\\Entity\\Artist");

        $rpcServiceResource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource');
        $rpcServiceResource->setModuleName('DbApi');

        foreach ($entityMetadata->associationMappings as $mapping) {
            switch ($mapping['type']) {
                case 4:
                    $rpcServiceResource->create(array(
                        'service_name' => 'Artist' . $mapping['fieldName'],
                        'route' => '/db-test/artist[/:parent_id]/' . $filter($mapping['fieldName']) . '[/:child_id]',
                        'http_methods' => array(
                            'GET', 'PUT', 'POST'
                        ),
                        'options' => array(
                            'target_entity' => $mapping['targetEntity'],
                            'source_entity' => $mapping['sourceEntity'],
                            'field_name' => $mapping['fieldName'],
                        ),
                        'selector' => 'custom selector',
                    ));
                    break;
                default:
                    break;
            }
        }

    }
}
