<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class DoctrineAutodiscoveryModelFactory
{
    /**
     * @param ContainerInterface $container
     * @return DoctrineAutodiscoveryModel
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create %s service because config service is not present',
                DoctrineAutodiscoveryModel::class
            ));
        }

        $instance = new DoctrineAutodiscoveryModel($container->get('config'));
        $instance->setServiceLocator($container);

        return $instance;
    }
}
