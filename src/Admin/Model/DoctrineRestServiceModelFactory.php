<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use ZF\Apigility\Admin\Exception;
use ZF\Apigility\Admin\Model\RpcServiceModelFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class DoctrineRestServiceModelFactory extends RpcServiceModelFactory implements ServiceManagerAwareInterface
{
    const TYPE_DEFAULT      = 'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModel';

    /**
     * @param  string $module
     * @return RestServiceModel
     */
    public function factory($module, $type = self::TYPE_DEFAULT)
    {
        if (isset($this->models[$type])
            && isset($this->models[$type][$module])
        ) {
            // @codeCoverageIgnoreStart
            return $this->models[$type][$module];
        }
            // @codeCoverageIgnoreEnd

        $moduleName   = $this->normalizeModuleName($module);
        $config       = $this->configFactory->factory($module);
        $moduleEntity = $this->moduleModel->getModule($moduleName);

        $restModel = new DoctrineRestServiceModel($moduleEntity, $this->modules, $config);
        $restModel->getEventManager()->setSharedManager($this->sharedEventManager);
        $restModel->setServiceManager($this->getServiceManager());

        switch ($type) {
            case self::TYPE_DEFAULT:
                $this->models[$type][$module] = $restModel;

                return $restModel;
            // @codeCoverageIgnoreStart
            default:
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'Model of type "%s" does not exist or cannot be handled by this factory',
                        $type
                    )
                );
        }
            // @codeCoverageIgnoreEnd
    }

    protected $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }
}
