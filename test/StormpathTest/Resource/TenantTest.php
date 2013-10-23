<?php

namespace StormpathTest\Resource;

use StormpathTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Zend\Config\Reader\Ini as ConfigReader;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceManager;
use Stormpath\Service\StormpathService;
use Stormpath\Http\Client\Adapter\Digest;
use Stormpath\Http\Client\Adapter\Basic;
use Zend\Http\Client;

class TenantTest extends \PHPUnit_Framework_TestCase
{
    protected $serviceManager;

    protected function setUp()
    {
        $reader = new ConfigReader();
        $config = $reader->fromFile($_SERVER['HOME'] . '/.stormpath/apiKey.ini');

        $this->assertNull( StormpathService::configure($config['apiKey']['id'], $config['apiKey']['secret']));

        $client = new Client();
        $adapter = new Basic();
        $client->setAdapter($adapter);
        StormpathService::setHttpClient($client);
    }

    public function testGetCurrentTenant()
    {
        $resourceManager = StormpathService::getResourceManager();

        $tenant = $resourceManager->find('Stormpath\Resource\Tenant', 'current');

        $this->assertNotEmpty($tenant->getHref());
        $this->assertNotEmpty($tenant->getName());
        $this->assertNotEmpty($tenant->getKey());

        $tenantArray = $tenant->getArrayCopy();

        $tenant->getApplications();
        $tenant->getDirectories();

    }

    /**
     * NOT A UNIT TEST
     *
     * This is a helper function to delete all applications from the current tenant
     */
    public function XtestDeleteAllApplicationsFromTenant()
    {
        $resourceManager = StormpathService::getResourceManager();

        $tenant = $resourceManager->find('Stormpath\Resource\Tenant', 'current');

        $count = 0;
        foreach ($tenant->getApplications() as $application)
        {
            $resourceManager->remove($application);
            $count ++;
        }

        $resourceManager->flush();

        die("deleted $count applications");

    }
}
