<?php

namespace StormpathTest\Resource;

use PHPUnit_Framework_TestCase;
use Stormpath\Service\StormpathService;
use Stormpath\Resource\Directory;
use Stormpath\Resource\Account;
use Stormpath\Resource\Group;
use Stormpath\Resource\GroupMembership;
use Stormpath\Resource\LoginAttempt;
use Stormpath\Resource\Application;
use Stormpath\Resource\AccountStoreMapping;
use Stormpath\Resource\Exception;

class DirectoryTest extends \PHPUnit_Framework_TestCase
{
    protected $directory;
    protected $tenant;

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

    public function testUpdate()
    {
        $resourceManager = StormpathService::getResourceManager();

        $originalDescription = $this->directory->getDescription();

        $newDescription = md5(rand());
        $this->directory->setDescription($newDescription);
        $resourceManager->persist($this->directory);
        $resourceManager->flush();

        $resourceManager->refresh($this->directory);

        $this->assertEquals($newDescription, $this->directory->getDescription());

        $this->directory->setDescription($originalDescription);
        $resourceManager->persist($this->directory);
        $resourceManager->flush();
    }

    public function testAddAccounts()
    {
        $resourceManager = StormpathService::getResourceManager();

        $account1 = new Account;
        $account1->setUsername(md5(rand()));
        $account1->setEmail(md5(rand()) . '@test.stormpath.com');
        $account1->setPassword(md5(rand()) . strtoupper(md5(rand())));
        $account1->setGivenName('Test');
        $account1->setMiddleName('User');
        $account1->setSurname('One');
        $account1->setDirectory($this->directory);
        $account1->setStatus('ENABLED');

        $account2 = new Account;
        $account2->setUsername(md5(rand()));
        $account2->setEmail(md5(rand()) . '@test.stormpath.com');
        $account2->setPassword(md5(rand()) . strtoupper(md5(rand())));
        $account2->setGivenName('Test');
        $account2->setMiddleName('User');
        $account2->setSurname('Two');
        $account2->setDirectory($this->directory);
        $account2->setStatus('ENABLED');

        $account3 = new Account;
        $account3->setUsername(md5(rand()));
        $account3->setEmail(md5(rand()) . '@test.stormpath.com');
        $account3->setPassword(md5(rand()) . strtoupper(md5(rand())));
        $account3->setGivenName('Test');
        $account3->setMiddleName('User');
        $account3->setSurname('Three');
        $account3->setDirectory($this->directory);
        $account3->setStatus('ENABLED');

        $resourceManager->persist($account1);
        $resourceManager->persist($account2);
        $resourceManager->persist($account3);
        $resourceManager->flush();

        $this->assertEquals(3, sizeof($this->directory->getAccounts()));

        // Clean Up
        $resourceManager->remove($account1);
        $resourceManager->remove($account2);
        $resourceManager->remove($account3);
        $resourceManager->flush();
    }

    public function testAddGroups()
    {
        $resourceManager = StormpathService::getResourceManager();

        $groups = array();
        for ($i = 0; $i <= 2; $i++) {
            $group = new Group;
            $group->setName(md5(rand()));
            $group->setDescription('Test Group ' . $i);
            $group->setStatus('ENABLED');
            $group->setDirectory($this->directory);

            $groups[] = $group;
            $resourceManager->persist($group);
        }

        $resourceManager->flush();

        $this->assertEquals(3, sizeof($this->directory->getGroups()));

        // Clean Up
        foreach ($groups as $group) {
            $resourceManager->remove($group);
        }
        $resourceManager->flush();

    }

    public function testAssignAccountToGroup()
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

        // Clean Up
        $resourceManager->detach($group1);
        $resourceManager->remove($groupMembership);
        $resourceManager->remove($group1);
        $resourceManager->remove($account1);
        $resourceManager->flush();
    }

    public function testIsDefaultGroupStore()
    {
        $resourceManager = StormpathService::getResourceManager();
        $app = new Application;

        $app->setName(md5(rand()));
        $app->setDescription('phpunit test application');
        $app->setStatus('ENABLED');

        $resourceManager->persist($app);
        $resourceManager->flush();

        $groupStoreMapping = new AccountStoreMapping;
        $groupStoreMapping->setApplication($app);
        $groupStoreMapping->setAccountStore($this->directory);
        $groupStoreMapping->setIsDefaultGroupStore(true);

        $resourceManager->persist($groupStoreMapping);
        $resourceManager->flush();

        $resourceManager->refresh($app);

        $accountStores = $app->getAccountStoreMappings();
        $found = false;
        foreach ($accountStores as $as) {
            if ($as->getId() == $groupStoreMapping->getId()) $found = true;
        }

        $this->assertTrue($found);

        $resourceManager->refresh($app);

        /**
         * App doesn't seem to be including defaultGroupStoreMapping; blank string
         */
        $default = $app->getDefaultGroupStoreMapping();
        if (!$default) $this->assertTrue(false);

        $this->assertEquals($groupStoreMapping->getId(), $default->getId());


        $resourceManager->remove($groupStoreMapping);
        $resourceManager->remove($app);
        $resourceManager->flush();
    }


    public function testExpandReferences()
    {
        $resourceManager = StormpathService::getResourceManager();
        $directory2 = $resourceManager->find('Stormpath\Resource\Directory', $this->directory->getId(), true);
        $directory2->getTenant();
    }

}
