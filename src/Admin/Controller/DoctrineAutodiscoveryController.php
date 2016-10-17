<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;
use ZF\ContentNegotiation\ViewModel;

class DoctrineAutodiscoveryController extends AbstractActionController
{
    /**
     * @var DoctrineAutodiscoveryModel
     */
    protected $model;

    /**
     * Constructor
     *
     * @param DoctrineAutodiscoveryModel $model
     */
    public function __construct(DoctrineAutodiscoveryModel $model)
    {
        $this->model = $model;
    }

    public function discoverAction()
    {
        $module = $this->params()->fromRoute('name');
        $version = $this->params()->fromRoute('version');
        $adapter = $this->params()->fromRoute('object_manager_alias');
        $data = $this->model->fetchFields($module, $version, $adapter);

        return new ViewModel(['payload' => $data]);
    }
}
