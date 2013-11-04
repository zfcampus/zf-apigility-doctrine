<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace SoliantConsulting\Apigility\Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZF\Configuration\ConfigResource;

class AppController extends AbstractActionController
{
    public function indexAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('soliantconsulting/apigility/admin/app/index.phtml');
        $viewModel->setTerminal(true);

        $objectManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $objectManager->getMetadataFactory();
        $entityMetadata = $metadataFactory->getAllMetadata();



        foreach ($entityMetadata as $classMetadata) {
            print_r(($classMetadata));
            die();
        }

//        $viewModel->setVariable('entities', $entityList);

        return $viewModel;
    }

    public function buildAction()
    {
        if ($this->getRequest()->getPost()->get('run')) die ('no run');

        die('run');
    }
}
