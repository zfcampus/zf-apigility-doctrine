<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Zend\EventManager\SharedEventManagerInterface;
use ZF\Configuration\ResourceFactory as ConfigResourceFactory;
use ZF\Configuration\ModuleUtils;
use ZF\Apigility\Admin\Model\ModuleModel;

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
    protected $models = array();

    /**
     * @var ModuleModel
     */
    protected $moduleModel;

    /**
     * @var ModuleUtils
     */
    protected $modules;

    /**
     * @var SharedEventManagerInterface
     */
    protected $sharedEventManager;

    /**
     * @param ModuleUtils    $modules
     * @param ConfigResource $config
     */
    public function __construct(ModuleUtils $modules, ConfigResourceFactory $configFactory, SharedEventManagerInterface $sharedEvents, ModuleModel $moduleModel)
    {
        $this->modules            = $modules;
        $this->configFactory      = $configFactory;
        $this->sharedEventManager = $sharedEvents;
        $this->moduleModel        = $moduleModel;
    }

    /**
     * @param  string          $module
     * @return RpcServiceModel
     */
    public function factory($module)
    {
        if (isset($this->models[$module])) {
        // @codeCoverageIgnoreStart
            return $this->models[$module];
        }
        // @codeCoverageIgnoreEnd

        $moduleName   = $this->normalizeModuleName($module);
        $moduleEntity = $this->moduleModel->getModule($moduleName);
        $config       = $this->configFactory->factory($module);

        $this->models[$module] = new DoctrineRpcServiceModel($moduleEntity, $this->modules, $config);

        return $this->models[$module];
    }

    /**
     * @param  string $name
     * @return string
     */
    protected function normalizeModuleName($name)
    {
        return str_replace('.', '\\', $name);
    }
}
