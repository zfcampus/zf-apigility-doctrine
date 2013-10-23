<?php

namespace StormpathTest\Service;

use StormpathTest\Bootstrap;
use PHPUnit_Framework_TestCase;
use Stormpath\Service\StormpathService;
use Stormpath\Http\Client\Adapter\Digest;
use Stormpath\Http\Client\Adapter\Basic;
use Zend\Config\Reader\Ini as ConfigReader;
use Zend\Http\Client;

class BasicTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        StormpathService::getHttpClient()->setAdapter(new Basic(null, array('keepalive' => true)));
    }

    protected function tearDown()
    {
        StormpathService::getHttpClient()->setAdapter(new Basic(null, array('keepalive' => true)));
    }

    public function testBasicAuthentication()
    {
        $resourceManager = StormpathService::getResourceManager();
        $tenant = $resourceManager->find('Stormpath\Resource\Tenant', 'current');
    }

}
