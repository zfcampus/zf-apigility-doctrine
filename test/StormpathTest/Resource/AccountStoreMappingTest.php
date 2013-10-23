<?php

namespace StormpathTest\Resource;

use PHPUnit_Framework_TestCase;
use Stormpath\Service\StormpathService;
use Stormpath\Resource\Application;
use Stormpath\Resource\Account;
use Stormpath\Resource\PasswordResetToken;
use Stormpath\Resource\Directory;
use Stormpath\Resource\Group;
use Stormpath\Resource\AccountStoreMapping;
use Stormpath\Resource\LoginAttempt;
use Stormpath\Exception\ApiException;

class AccountStoreMappingTest extends \PHPUnit_Framework_TestCase
{
    protected $application;

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

    public function testUpdate()
    {
        $resourceManager = StormpathService::getResourceManager();

        $originalDescription = $this->application->getDescription();

        $newDescription = md5(rand());
        $this->application->setDescription($newDescription);
        $resourceManager->persist($this->application);
        $resourceManager->flush();

        $resourceManager->refresh($this->application);

        $this->assertEquals($newDescription, $this->application->getDescription());

        $this->application->setDescription($originalDescription);
        $resourceManager->persist($this->application);
        $resourceManager->flush();
    }


    public function testLoginAttempt()
    {
        $resourceManager = StormpathService::getResourceManager();

        $username = md5(rand());
        $password = md5(rand()) . strtoupper(md5(rand()));
        $email = md5(rand()) . '@test.stormpath.com';

        // Create directory and AccountStoreMapping
        $directory = new Directory;
        $directory->setName(md5(rand()));
        $directory->setDescription('phpunit test directory');
        $directory->setStatus('ENABLED');

        $resourceManager->persist($directory);
        $resourceManager->flush();

        $accountStoreMapping = new AccountStoreMapping;
        $accountStoreMapping->setApplication($this->application);
        $accountStoreMapping->setAccountStore($directory);
        $accountStoreMapping->setIsDefaultAccountStore(true);

        $resourceManager->persist($accountStoreMapping);
        $resourceManager->flush();

        $this->assertTrue($accountStoreMapping->getApplication()->getId() == $this->application->getId());

        $resourceManager->remove($accountStoreMapping);
        $resourceManager->remove($directory);
        $resourceManager->flush();
    }

    public function testGroupAccountStore()
    {
        $resourceManager = StormpathService::getResourceManager();

        $username = md5(rand());
        $password = md5(rand()) . strtoupper(md5(rand()));
        $email = md5(rand()) . '@test.stormpath.com';


        // Create directory and AccountStoreMapping
        $directory = new Directory;
        $directory->setName(md5(rand()));
        $directory->setDescription('phpunit test directory');
        $directory->setStatus('ENABLED');

        $group = new Group;
        $group->setName(md5(rand()));
        $group->setDescription('phpunit test directory');
        $group->setStatus('ENABLED');
        $group->setDirectory($directory);

        $resourceManager->persist($directory);
        $resourceManager->persist($group);
        $resourceManager->flush();

        $accountStoreMapping = new AccountStoreMapping;
        $accountStoreMapping->setApplication($this->application);
        $accountStoreMapping->setAccountStore($group);
        $accountStoreMapping->setIsDefaultAccountStore(true);

        $resourceManager->persist($accountStoreMapping);
        $resourceManager->flush();

        $this->assertTrue($accountStoreMapping->getApplication()->getId() == $this->application->getId());

        $resourceManager->remove($accountStoreMapping);
        $resourceManager->remove($group);
        $resourceManager->flush();
    }

    public function testInvalidAccountStoreType()
    {
        $account1 = new Account;
        $accountStoreMapping = new AccountStoreMapping;

        try {
            $accountStoreMapping->setAccountStore($account1);
            throw new \Exception('Invalid account store test failed');
        } catch (\Exception $e) {
            $this->assertEquals('Account store is neither a Group nor Directory resource.', $e->getMessage());
        }
    }
}

