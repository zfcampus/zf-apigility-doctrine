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

class CacheTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function testCacheHit()
    {
        $resourceManager = StormpathService::getResourceManager();

        $dir = new Directory;

        $dir->setName(md5(rand()));
        $dir->setDescription('phpunit test directory');
        $dir->setStatus('ENABLED');

        $resourceManager->persist($dir);
        $resourceManager->flush();

        $cachedJson = $resourceManager->getCache()->getItem(get_class($dir) . $dir->getId(), $success);
        $this->assertTrue($success);

        // Update resource to update cache
        $dir->setDescription('phpunit changed description');

        $resourceManager->persist($dir);
        $resourceManager->flush();

        $cachedJson = $resourceManager->getCache()->getItem(get_class($dir) . $dir->getId(), $success);
        $this->assertTrue($success);

        $dirCopy = $resourceManager->find('Stormpath\Resource\Directory', $dir->getId());

        $cachedJson = $resourceManager->getCache()->getItem(get_class($dir) . $dir->getId(), $success);
        $this->assertTrue($success);

        // Clean Up
        $resourceManager->remove($dir);
        $resourceManager->flush();

        $cachedJson = $resourceManager->getCache()->getItem(get_class($dir) . $dir->getId(), $success);
        $this->assertFalse($success);
    }
}
