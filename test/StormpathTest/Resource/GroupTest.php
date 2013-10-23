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

class GroupTest extends \PHPUnit_Framework_TestCase
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

        $resourceManager->persist($group1);
        $resourceManager->flush();

        $group1->getAccountMemberships();
        $group1->getAccounts();
        $group1->getTenant();

        $group2 = $resourceManager->find('Stormpath\Resource\Group', $group1->getId(), true);

        // Clean Up
        $resourceManager->remove($group1);
        $resourceManager->flush();
    }
}
