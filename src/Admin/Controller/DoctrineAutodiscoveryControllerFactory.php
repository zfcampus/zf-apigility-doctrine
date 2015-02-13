<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DoctrineAutodiscoveryControllerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $controllers
     * @return AutodiscoveryController
     */
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        /** @var \ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel $model */
        $model = $services->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel');
        return new DoctrineAutodiscoveryController($model);
    }

}