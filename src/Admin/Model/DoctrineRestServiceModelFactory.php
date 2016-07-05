<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use ZF\Apigility\Admin\Exception;
use ZF\Apigility\Admin\Model\RpcServiceModelFactory;
use Zend\ServiceManager\ServiceManager;

class DoctrineRestServiceModelFactory extends RpcServiceModelFactory
{
    const TYPE_DEFAULT = 'ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceModel';

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Get service manager
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager
     *
     * @param ServiceManager $serviceManager
     * @return DoctrineRestServiceModelFactory
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }


    /**
     * @param string $module
     * @param string $type
     * @return DoctrineRestServiceModel
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
}
