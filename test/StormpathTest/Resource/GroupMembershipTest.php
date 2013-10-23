<?php

namespace StormpathTest\Resource;

use PHPUnit_Framework_TestCase;
use Stormpath\Service\StormpathService;
use Stormpath\Resource\Application;
use Stormpath\Resource\Account;
use Stormpath\Resource\PasswordResetToken;
use Stormpath\Resource\Directory;
use Stormpath\Resource\Group;
use Stormpath\Resource\GroupMembership;
use Stormpath\Resource\AccountStoreMapping;
use Stormpath\Resource\LoginAttempt;
use Stormpath\Exception\ApiException;

class GroupMembershipTest extends \PHPUnit_Framework_TestCase
{
    protected $application;

    protected function setUp()
    {
        $resourceManager = StormpathService::getResourceManager();

        $dir = new Directory;
        $dir->setName(md5(rand()));
        $dir->setDescription('phpunit test directory');
        $dir->setStatus('ENABLED');

        $resourceManager->persist($dir);
        $resourceManager->flush();

        $this->directory = $dir;
    }

    protected function tearDown()
    {
        $resourceManager = StormpathService::getResourceManager();
        $resourceManager->remove($this->directory);
        $resourceManager->flush();
    }

    public function testExpandReferences()
    {
        $resourceManager = StormpathService::getResourceManager();

        $group1 = new Group;
        $group1->setName(md5(rand()));
        $group1->setDescription('Test Group One');
        $group1->setStatus('ENABLED');
        $group1->setDirectory($this->directory);

        $username = md5(rand());
        $password = md5(rand()) . strtoupper(md5(rand()));

        $account1 = new Account;
        $account1->setUsername($username);
        $account1->setEmail(md5(rand()) . '@test.stormpath.com');
        $account1->setPassword($password);
        $account1->setGivenName('Test');
        $account1->setMiddleName('User');
        $account1->setSurname('One');
        $account1->setDirectory($this->directory);

        $resourceManager->persist($group1);
        $resourceManager->persist($account1);
        $resourceManager->flush();

        $groupMembership = new GroupMembership();
        $groupMembership->setGroup($group1);
        $groupMembership->setAccount($account1);

        $resourceManager->persist($groupMembership);
        $resourceManager->flush();

        $groupMembership1 = $resourceManager->find('Stormpath\Resource\GroupMembership', $groupMembership->getId(), true);

        // Clean Up
        $resourceManager->remove($groupMembership);
        $resourceManager->remove($group1);
        $resourceManager->remove($account1);
        $resourceManager->flush();
    }
}
