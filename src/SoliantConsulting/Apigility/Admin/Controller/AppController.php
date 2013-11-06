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

        $moduleResource = $this->getServiceLocator()->get('ZF\Apigility\Admin\Model\ModuleResource');
        $moduleResource->setModulePath(realpath(__DIR__ . '/../../../../../../../../'));

/*
        print_r(($metadata));die();
*/


        $moduleName = 'DoctrineApi';

        $serviceResource = $this->getServiceLocator()->get('SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceResource');
        $serviceResource->setModuleName($moduleName);

        $metadata = $moduleResource->create(array(
            'name' =>  $moduleName,
        ));

        die($moduleName . ' created');
/*
*/
        $objectManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $objectManager->getMetadataFactory();


        foreach ($metadataFactory->getAllMetadata() as $entityMetadata) {

            $resourceName = substr($entityMetadata->name, strlen($entityMetadata->namespace) + 1);
            $serviceResource->create(array(
                'resourcename' => $resourceName,
                'entityClass' => $entityMetadata->name,
            ));
        }

#        print_r(get_class_methods($serviceResource));
        die('API Created');


        die('DoctrineApi created');


//        $this->moduleManager = $this->getServiceLocator()->get('Zend\ModuleManager\ModuleManager');
#        $this->moduleManager->expects($this->any())
#                            ->method('getLoadedModules')
#                            ->will($this->returnValue($modules));

        $model = new ModuleModel($this->moduleManager, $restConfig = array(), $rpcConfig = array());
        $resource = new ModuleResource($model);

        $moduleName = uniqid('Foo');
        $module = $this->resource->create(array(
            'name' => $moduleName,
        ));


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
