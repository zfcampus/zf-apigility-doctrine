<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine;

use Zend\Mvc\Application;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class TestCase extends AbstractHttpControllerTestCase
{
    public function setApplicationConfig($config)
    {
        $r = (new \ReflectionClass(Application::class))->getConstructor();
        $appVersion = $r->getNumberOfRequiredParameters() === 2 ? 2 : 3;

        if ($appVersion === 3) {
            array_unshift($config['modules'], 'Zend\Router', 'Zend\Hydrator');
        }

        parent::setApplicationConfig($config);
    }
}
