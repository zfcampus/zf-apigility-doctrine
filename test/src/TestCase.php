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
    private $enabledModules = [];

    public function setApplicationConfig($config)
    {
        $r = (new \ReflectionClass(Application::class))->getConstructor();
        $appVersion = $r->getNumberOfRequiredParameters() === 2 ? 2 : 3;

        if ($appVersion === 3) {
            array_unshift($config['modules'], 'Zend\Router', 'Zend\Hydrator');
        }

        $this->enabledModules = $config['module_listener_options']['module_paths'];
        $this->clearAssets();

        parent::setApplicationConfig($config);
    }

    protected function tearDown()
    {
        $this->clearAssets();

        return parent::tearDown();
    }

    private function removeDir($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        return rmdir($dir);
    }

    private function clearAssets()
    {
        foreach ($this->enabledModules as $module => $path) {
            $configPath = sprintf('%s/config/', $path);
            foreach (glob(sprintf('%s/src/%s/V*', $path, $module)) as $dir) {
                $this->removeDir($dir);
            }
            copy($configPath . '/module.config.php.dist', $configPath . '/module.config.php');
        }
    }

    protected function setModuleName($resource, $moduleName)
    {
        $r = new \ReflectionObject($resource);
        $prop = $r->getProperty('moduleName');
        $prop->setAccessible(true);
        $prop->setValue($resource, $moduleName);
    }
}
