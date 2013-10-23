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

class ExpandReferencesTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $resourceManager = StormpathService::getResourceManager();

        $app = new Application;

        $app->setName(md5(rand()));
        $app->setDescription('phpunit test application');
        $app->setStatus('ENABLED');

        $resourceManager->persist($app);
        $resourceManager->flush();

        $this->application = $app;
    }

    protected function tearDown()
    {
        $resourceManager = StormpathService::getResourceManager();
        $resourceManager->remove($this->application);
        $resourceManager->flush();
    }

    /**
     * Fetch the current application with expanding (multiple) references
     */
    public function testFetchAccountWithExpandResources()
    {
        $resourceManager = StormpathService::getResourceManager();

        $directory = new Directory;
        $directory->setName(md5(rand()));
        $directory->setDescription('phpunit test directory');
        $directory->setStatus('ENABLED');
        $resourceManager->persist($directory);
        $resourceManager->flush();
        

        # FIXME: Assign user to directory

        $account1 = new Account;
        $account1->setUsername(md5(rand()));
        $account1->setEmail(md5(rand()) . '@test.stormpath.com');
        $account1->setPassword(md5(rand()) . strtoupper(md5(rand())));
        $account1->setGivenName('Test');
        $account1->setMiddleName('User');
        $account1->setSurname('One');
        $account1->setDirectory($directory);
        $account1->setStatus('ENABLED');

        $resourceManager->persist($account1);
        $resourceManager->flush();

        $account = $resourceManager->find('Stormpath\Resource\Account', $account1->getId(), true);

        $this->assertEquals($account->getDirectory()->getHref(), $directory->getHref());
        $this->assertEquals($account->getTenant()->getHref(),
            $resourceManager->find('Stormpath\Resource\Tenant', 'current')->getHref());
       
        // Clean Up
        $resourceManager->remove($account1);
        $resourceManager->clear($directory);
        $resourceManager->remove($directory);
        $resourceManager->flush();
    }


    public function testFetchApplicationWithExpandResources()
    {
        $resourceManager = StormpathService::getResourceManager();

        try {
            $application = $resourceManager->find('Stormpath\Resource\Application', $this->application->getId(), true);
        } catch (ApiException $e) {
            die('post expand');
        }

        $href = $application->getTenant()->getHref();

        $this->assertEquals($href,  $resourceManager->find('Stormpath\Resource\Tenant', 'current')->getHref());
    }

    public function testFetchDirectoryWithExpandResources()
    {
         $resourceManager = StormpathService::getResourceManager();
         $directory = new Directory;
         $directory->setName(md5(rand()));
         $directory->setDescription('phpunit test directory');
         $directory->setStatus('ENABLED');
        
         $resourceManager->persist($directory);
         $resourceManager->flush();
    }

}
