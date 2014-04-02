<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Admin;

use Zend\Loader\AutoloaderFactory;
use RuntimeException;

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

        // Create testing modules
        $run = "rm -rf " . __DIR__ . "/assets/module/Db";
        exec($run);

        $run = "rm -rf " . __DIR__ . "/assets/module/DbApi";
        exec($run);

        mkdir(__DIR__ . '/assets/module/Db');
        mkdir(__DIR__ . '/assets/module/DbApi');

        $run = 'rsync -a ' . __DIR__ . '/assets/module/DbOriginal/* ' . __DIR__ . '/assets/module/Db';
        exec($run);

        $run = 'rsync -a ' . __DIR__ . '/assets/module/DbApiOriginal/* ' . __DIR__ . '/assets/module/DbApi';
        exec($run);

        // Create testing modules
        $run = "rm -rf " . __DIR__ . "/assets/module/DbMongo";
        exec($run);

        $run = "rm -rf " . __DIR__ . "/assets/module/DbMongoApi";
        exec($run);

        mkdir(__DIR__ . '/assets/module/DbMongo');
        mkdir(__DIR__ . '/assets/module/DbMongoApi');

        $run = 'rsync -a ' . __DIR__ . '/assets/module/DbMongoOriginal/* ' . __DIR__ . '/assets/module/DbMongo';
        exec($run);

        $run = 'rsync -a ' . __DIR__ . '/assets/module/DbMongoApiOriginal/* ' . __DIR__ . '/assets/module/DbMongoApi';
        exec($run);
    }

    protected static function initAutoloader()
    {
        $vendorPath = static::findParentPath('vendor');

        if (is_readable($vendorPath . '/autoload.php')) {
            $loader = include $vendorPath . '/autoload.php';

            return;
        }

        $zf2Path = getenv('ZF2_PATH') ?: (defined('ZF2_PATH') ? ZF2_PATH : (is_dir($vendorPath . '/ZF2/library') ? $vendorPath . '/ZF2/library' : false));

        if (!$zf2Path) {
            throw new RuntimeException('Unable to load ZF2. Run `php composer.phar install` or define a ZF2_PATH environment variable.');
        }

        if (isset($loader)) {
            $loader->add('Zend', $zf2Path . '/Zend');
        } else {
            include $zf2Path . '/Zend/Loader/AutoloaderFactory.php';
            AutoloaderFactory::factory(array(
                'Zend\Loader\StandardAutoloader' => array(
                    'autoregister_zf' => true,
                    'namespaces' => array(
                        'ZF\Apigility\Doctrine' => __DIR__ . '/../src',
                        __NAMESPACE__ => __DIR__,
                        'Test' => __DIR__ . '/../vendor/Test/',
                    ),
                ),
            ));
        }
    }

    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) return false;
            $previousDir = $dir;
        }

        return $dir . '/' . $path;
    }
}

Bootstrap::init();
