<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

// Because of the code-generating of Apigility this script
// is used to setup the tests.  Use ~/test/bin/reset-tests
// to reset the output of this test if the unit tests
// fail the application.

namespace ZFTest\Apigility\Doctrine\Server\Model\Server\ODM\Setup;

class SetupTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        if (! extension_loaded('mongo')) {
            $this->markTestSkipped(sprintf('Tests for %s can only be run with the Mongo extension', __CLASS__));
        }

        $this->setApplicationConfig(
            include __DIR__ . '/../../../../config/ODM/application.config.php'
        );

        parent::setUp();
    }

    public function testBuildOdmApi()
    {
        $serviceManager = $this->getApplication()->getServiceManager();

        // Create DB
        $resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');

        $metaResourceDefinition = [
            "objectManager" => "doctrine.documentmanager.odm_default",
            "serviceName" => "Meta",
            "entityClass" => "ZFTestApigilityDbMongo\\Document\\Meta",
            "routeIdentifierName" => "meta_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/test/meta",
        ];

        $resource->setModuleName('ZFTestApigilityDbMongoApi');
        $metaEntity = $resource->create($metaResourceDefinition);

        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $metaEntity);
    }
}
