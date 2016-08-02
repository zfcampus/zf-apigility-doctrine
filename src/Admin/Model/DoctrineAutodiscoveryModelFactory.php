<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Zf\Apigility\Doctrine\Admin\Model;

use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class DoctrineAutodiscoveryModelFactory
{
    /**
     * @param ConfigInterface $container
     * @return DoctrineAutodiscoveryModel
     */
    public function __invoke(ConfigInterface $container)
    {
        if (! $container->has('config')) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create %s service because Config service is not present',
                DoctrineAutodiscoveryModel::class
            ));
        }

        $config = $container->get('config');
        $model = new DoctrineAutodiscoveryModel($config);
        $model->setServiceLocator($container);

        return $model;
    }
}
