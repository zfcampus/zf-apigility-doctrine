<?php

namespace StormpathTest\Service;

use StormpathTest\Bootstrap;
use PHPUnit_Framework_TestCase;
use Stormpath\Service\StormpathService;
use Stormpath\Exception\ApiException;
use Stormpath\Http\Client\Adapter\Digest;
use Stormpath\Http\Client\Adapter\Basic;
use Zend\Http\Client;
use Stormpath\Resource\Application;
use Stormpath\Resource\Directory;
use Stormpath\Resource\Account;

class ResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function testDetach() {
        $resourceManager = StormpathService::getResourceManager();

        $app = new Application;

        $app->setName(md5(rand()));
        $app->setDescription('phpunit test application');
        $app->setStatus('ENABLED');

        $resourceManager->persist($app);
        $resourceManager->detach($app);
        $resourceManager->flush();

        $this->assertEquals($app->getId(), null);

        $resourceManager->persist($app);
        $resourceManager->flush();

        $resourceManager->remove($app);
        $resourceManager->detach($app);
        $resourceManager->flush();

        $appCopy = $resourceManager->find('Stormpath\Resource\Application', $app->getId());

        $this->assertEquals($app->getId(), $appCopy->getId());

        $app->setDescription('changed');

        $resourceManager->persist($app);
        $resourceManager->detach($app);
        $resourceManager->flush();

        $appCopy2 = $resourceManager->find('Stormpath\Resource\Application', $app->getId());

        $this->assertEquals($app->getId(), $appCopy->getId());

        // Clean up
        $resourceManager->remove($app);
        $resourceManager->flush();
    }
}
