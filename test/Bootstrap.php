<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin;

use Zend\Loader\AutoloaderFactory;
use RuntimeException;
use ZFTest\Util\ServiceManagerFactory;
use Doctrine\ORM\Tools\SchemaTool;


error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
 *
 * @subpackage UnitTest
 */
class Bootstrap
{
    protected static $serviceManager;

    public static function init()
    {
        static::initAutoloader();

        if (file_exists(__DIR__ . '/TestConfiguration.php')) {
            $config = require __DIR__ . '/TestConfiguration.php';
        } else {
            $config = require __DIR__ . '/TestConfiguration.php.dist';
        }

        ServiceManagerFactory::setConfig($config);

        $em = ServiceManagerFactory::getServiceManager()->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $res = $tool->createSchema($em->getMetadataFactory()->getAllMetadata());


#        $res = $tool->getUpdateSchemaSql($em->getMetadataFactory()->getAllMetadata());
#print_r($res);

    }

    protected static function initAutoloader()
    {
        $files = array(__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php');

        foreach ($files as $file) {
            if (file_exists($file)) {
                $loader = require $file;

                break;
            }
        }

        if (! isset($loader)) {
            throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
        }

        /* @var $loader \Composer\Autoload\ClassLoader */
        $loader->add('ZFTest', __DIR__);
        $loader->add('ZF\\Apigility\\Doctrine\\Admin', __DIR__ . '/../src/ZF/Apigility/Doctrine/Admin');
    }
}

Bootstrap::init();
