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

class PasswordResetTokenTest extends \PHPUnit_Framework_TestCase
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


    public function testPasswordResetTokenSuccess()
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

        // Test OK password reset token
        $passwordResetToken = new PasswordResetToken;
        $passwordResetToken->setEmail($account1->getEmaiL());
        $passwordResetToken->setApplication($this->application);
        $resourceManager->persist($passwordResetToken);

        try {
            $resourceManager->flush();
            $account = $passwordResetToken->getAccount();
            $this->assertEquals($account1->getId(), $account->getId());
        } catch (ApiException $e) {
            throw \Exception('Error sending password reset token');
        }

        $resourceManager->remove($account1);
        $resourceManager->remove($accountStoreMapping);
        $resourceManager->remove($directory);
        $resourceManager->flush();
    }

    public function testPasswordResetTokenEmailNotFound()
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

        // Test OK password reset token
        $passwordResetToken = new PasswordResetToken;
        $passwordResetToken->setEmail('invalid' . $account1->getEmaiL());
        $passwordResetToken->setApplication($this->application);
        $resourceManager->persist($passwordResetToken);

        try {
            $resourceManager->flush();
            throw new \Exception('Account found for invalid email');
            $account = $passwordResetToken->getAccount();
        } catch (ApiException $e) {
            $userMessage = $e->getMessage();
            // insert exception test
            $e->getStatus();
            $e->getDeveloperMessage();
            $e->getMoreInfo();

            if ($e->getCode() == 400) {
                $this->assertEquals('There is no account with that email address.', $userMessage);
            }

            if ($e->getCode() == 404) {
                $this->assertEquals('The requested resource does not exist.', $userMessage);
            }
        }

        $resourceManager->detach($passwordResetToken);
        $resourceManager->remove($account1);
        $resourceManager->remove($accountStoreMapping);
        $resourceManager->remove($directory);
        $resourceManager->flush();
    }

}

