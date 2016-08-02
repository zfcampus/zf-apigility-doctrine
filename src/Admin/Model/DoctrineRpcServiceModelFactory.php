<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Zend\EventManager\SharedEventManagerInterface;
use ZF\Apigility\Admin\Model\ModuleModel;
use ZF\Apigility\Admin\Model\ModulePathSpec;
use ZF\Configuration\ResourceFactory as ConfigResourceFactory;

class DoctrineRpcServiceModelFactory
{
    /**
     * @var ConfigResourceFactory
     */
    protected $configFactory;

    /**
     * Already created model instances
     *
     * @var array
     */
    protected $models = [];

    /**
     * @var ModuleModel
     */
    protected $moduleModel;

    /**
     * @var ModulePathSpec
     */
    protected $modules;

    /**
     * @var SharedEventManagerInterface
     */
    protected $sharedEventManager;

    /**
     * @param ModulePathSpec $modules
     * @param ConfigResourceFactory $configFactory
     * @param SharedEventManagerInterface $sharedEvents
     * @param ModuleModel $moduleModel
     */
    public function __construct(
        ModulePathSpec $modules,
        ConfigResourceFactory $configFactory,
        SharedEventManagerInterface $sharedEvents,
        ModuleModel $moduleModel
    ) {
        $this->modules            = $modules;
        $this->configFactory      = $configFactory;
        $this->sharedEventManager = $sharedEvents;
        $this->moduleModel        = $moduleModel;
    }

    /**
     * @param string $module
     * @return DoctrineRpcServiceModel
     */
    public function factory($module)
    {
        if (isset($this->models[$module])) {
            return $this->models[$module];
        }

        $moduleName   = $this->normalizeModuleName($module);
        $moduleEntity = $this->moduleModel->getModule($moduleName);
        $config       = $this->configFactory->factory($module);

        $this->models[$module] = new DoctrineRpcServiceModel($moduleEntity, $this->modules, $config);

        return $this->models[$module];
    }

    /**
     * @param string $name
     * @return string
     */
    protected function normalizeModuleName($name)
    {
        return str_replace('.', '\\', $name);
    }
}
