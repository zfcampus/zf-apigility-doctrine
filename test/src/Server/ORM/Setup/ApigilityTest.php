<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

// Because of the code-generating of Apigility this script
// is used to setup the tests.  Use ~/test/bin/reset-tests
// to reset the output of this test if the unit tests
// fail the application.

namespace ZFTest\Apigility\Doctrine\Server\ORM\Setup;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Filter\FilterChain;
use ZFTest\Apigility\Doctrine\TestCase;

class ApigilityTest extends TestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../config/ORM/application.config.php'
        );
        parent::setUp();
    }

    public function testBuildOrmApi()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        // Create DB
        $resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');

        $artistResourceDefinition = [
            "objectManager" => "doctrine.entitymanager.orm_default",
            "serviceName" => "Artist",
            "entityClass" => "ZFTestApigilityDb\\Entity\\Artist",
            "routeIdentifierName" => "artist_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/test/rest/artist",
            "collectionHttpMethods" => [
                0 => 'GET',
                1 => 'POST',
                2 => 'PATCH',
                3 => 'DELETE',
            ],
        ];

        $artistResourceDefinitionWithNonKeyIdentifer = [
            "objectManager" => "doctrine.entitymanager.orm_default",
            "serviceName" => "ArtistByName",
            "entityClass" => "ZFTestApigilityDb\\Entity\\Artist",
            "routeIdentifierName" => "artist_name",
            "entityIdentifierName" => "name",
            "routeMatch" => "/test/rest/artist-by-name",
            "collectionHttpMethods" => [
                0 => 'GET',
            ],
        ];

        // This route is what should be an rpc service, but an user could do
        $albumResourceDefinition = [
            "objectManager" => "doctrine.entitymanager.orm_default",
            "serviceName" => "Album",
            "entityClass" => "ZFTestApigilityDb\\Entity\\Album",
            "routeIdentifierName" => "album_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/test/rest[/artist/:artist_id]/album[/:album_id]",
            "collectionHttpMethods" => [
                0 => 'GET',
                1 => 'POST',
                2 => 'PATCH',
                3 => 'DELETE',
            ],
        ];

        $this->setModuleName($resource, 'ZFTestApigilityDbApi');
        $artistEntity = $resource->create($artistResourceDefinition);
        $artistEntity = $resource->create($artistResourceDefinitionWithNonKeyIdentifer);
        $albumEntity = $resource->create($albumResourceDefinition);

        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $artistEntity);
        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $albumEntity);

        // Build relation
        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
            ->attachByName('StringToLower');

        $em = $serviceManager->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $em->getMetadataFactory();
        $entityMetadata = $metadataFactory->getMetadataFor("ZFTestApigilityDb\\Entity\\Artist");

        $rpcServiceResource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource');
        $this->setModuleName($rpcServiceResource, 'ZFTestApigilityDbApi');

        foreach ($entityMetadata->associationMappings as $mapping) {
            switch ($mapping['type']) {
                case 4:
                    $rpcServiceResource->create([
                        'service_name' => 'Artist' . $mapping['fieldName'],
                        'route_match' => '/test/artist[/:parent_id]/' . $filter($mapping['fieldName']) . '[/:child_id]',
                        'http_methods' => [
                            'GET',
                            'PUT',
                            'POST',
                        ],
                        'options' => [
                            'target_entity' => $mapping['targetEntity'],
                            'source_entity' => $mapping['sourceEntity'],
                            'field_name'    => $mapping['fieldName'],
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
