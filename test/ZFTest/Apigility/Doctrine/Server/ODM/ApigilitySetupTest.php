<?php
// Because of the code-generating of Apigility this script
// is used to setup the tests.  Use ~/test/bin/reset-tests
// to reset the output of this test if the unit tests
// fail the application.

namespace ZFTest\Apigility\Doctrine\Admin\Model\Server\ODM;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Filter\FilterChain;

class ApigilitySetupTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../../config/ODM/application.config.php'
        );

        parent::setUp();
    }

    public function testBuildOdmApi() {
        $serviceManager = $this->getApplication()->getServiceManager();
/*
        $dm = $serviceManager->get('doctrine.documentmanager.odm_default');

        foreach ($dm->getRepository('MongoDb\Document\Meta')->findAll() as $meta) {
            $dm->remove($meta);
        }

        $dm->flush();
*/
        // Create DB
        $resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');

        $metaResourceDefinition = [
            "objectManager"=> "doctrine.documentmanager.odm_default",
            "serviceName" => "Meta",
            "entityClass" => "DbMongo\\Document\\Meta",
            "routeIdentifierName" => "meta_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/test/meta",
        ];

        $resource->setModuleName('DbMongoApi');
        $metaEntity = $resource->create($metaResourceDefinition);

        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $metaEntity);
   }
}
