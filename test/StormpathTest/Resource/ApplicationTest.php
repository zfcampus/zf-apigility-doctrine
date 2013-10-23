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

class ApplicationTest extends \PHPUnit_Framework_TestCase
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
        if ($this->application) $resourceManager->remove($this->application);
        $resourceManager->flush();
    }

    public function testAutoCreateDirectory()
    {
        $resourceManager = StormpathService::getResourceManager();

        $app = new Application;

        $app->setName(md5(rand()));
        $app->setDescription('phpunit test application');
        $app->setStatus('ENABLED');
        $directoryName = md5(rand());
        $app->setAutoCreateDirectory($directoryName);

        $resourceManager->persist($app);
        $resourceManager->flush();

        $this->assertEquals($directoryName, $app->getDefaultAccountStoreMapping()->getAccountStore()->getName());
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

    /**
     * We need detailed unit tests for all these
     */
    public function testGetters()
    {
        $this->application->getTenant();
        $this->application->getAccounts();
        $this->application->getGroups();
        $this->application->getLoginAttempts();
        $this->application->getPasswordResetTokens();
        $this->application->getAccountStoreMappings();
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

        $account1 = new Account;
        $account1->setUsername($username);
        $account1->setEmail($email);
        $account1->setPassword($password);
        $account1->setGivenName('Test');
        $account1->setMiddleName('User');
        $account1->setSurname('One');
        $account1->setApplication($this->application);
        $account1->setStatus('ENABLED');

        $resourceManager->persist($account1);
        $resourceManager->flush();

        // Test login attempt
        $loginAttempt = new LoginAttempt;
        $loginAttempt->setUsername($email);
        $loginAttempt->setPassword($password);
        $loginAttempt->setApplication($this->application);

        $resourceManager->persist($loginAttempt);
        $resourceManager->flush();

        $this->assertTrue($loginAttempt->getAccount() instanceof Account);
        $this->assertEquals($account1->getId(), $loginAttempt->getAccount()->getId());


        // Test login attempt expand resources
        # Currently failing due to resource expansion not returning from stormpath
        $resourceManager->setExpandReferences(false); // FIXME: Expand references not supported
        $loginAttempt2 = new LoginAttempt;
        $loginAttempt2->setUsername($email);
        $loginAttempt2->setPassword($password);
        $loginAttempt2->setApplication($this->application);

        $resourceManager->persist($loginAttempt2);
        $resourceManager->flush();

        $this->assertTrue($loginAttempt2->getAccount() instanceof Account);
        $this->assertEquals($account1->getId(), $loginAttempt2->getAccount()->getId());

        $resourceManager->remove($account1);
        $resourceManager->remove($accountStoreMapping);
        $resourceManager->remove($directory);
        $resourceManager->flush();
    }


    public function testDefaultAccountStoreMapping()
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

        $app = $this->application;
        $resourceManager->refresh($app);
        $default = $app->getDefaultAccountStoreMapping();

        $this->assertEquals($accountStoreMapping->getId(), $default->getId());

        $this->application = $app;
        $resourceManager->remove($accountStoreMapping);
        $resourceManager->remove($directory);
        $resourceManager->flush();
    }
}
