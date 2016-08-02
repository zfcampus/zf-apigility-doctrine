<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

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

        // Create General module
        $run = "rm -rf " . __DIR__ . "/assets/module/General";
        exec($run);

        mkdir(__DIR__ . '/assets/module/General');

        $run = 'rsync -a ' . __DIR__ . '/assets/module/GeneralOriginal/* ' . __DIR__ . '/assets/module/General';
        exec($run);
    }
}

Bootstrap::init();
