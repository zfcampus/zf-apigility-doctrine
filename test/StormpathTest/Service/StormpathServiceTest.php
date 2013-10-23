<?php

namespace StormpathTest\Service;

use StormpathTest\Bootstrap;
use PHPUnit_Framework_TestCase;
use Stormpath\Service\StormpathService;
use Stormpath\Http\Client\Adapter\Digest;
use Stormpath\Http\Client\Adapter\Basic;
use Zend\Http\Client;

class StormpathServiceTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $client = new Client();
        $adapter = new Digest();
        $client->setAdapter($adapter);
        StormpathService::setHttpClient($client);
    }

    public function testFetchResourceManager()
    {
        $this->assertTrue(StormpathService::getResourceManager() instanceof \Stormpath\Persistence\ResourceManager);
    }
}
