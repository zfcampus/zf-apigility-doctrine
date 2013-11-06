<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace SoliantConsulting\Apigility\Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZF\Configuration\ConfigResource;
use Zend\Config\Writer\PhpArray as PhpArrayWriter;

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
/*
        $metadata = $moduleResource->create(array(
            'name' =>  $moduleName,
        ));

        // Set renderer defaults
        $patchConfig = array('zf-hal' => array(
            'renderer' => array (
              'default_hydrator' => 'ArraySerializable',
              'render_embedded_resources' => false,
            ),
        ));

        $config = $this->getServiceLocator()->get('Config');
        $writer = new PhpArrayWriter();
        $moduleConfig = new ConfigResource($config, 'module/' . $moduleName . '/config/module.config.php', $writer);

        $moduleConfig->patch($patchConfig, true);

        die($moduleName . ' created');
//    */
        $objectManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $objectManager->getMetadataFactory();

        $serviceResource = $this->getServiceLocator()->get('SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceResource');
        $serviceResource->setModuleName($moduleName);

        foreach ($metadataFactory->getAllMetadata() as $entityMetadata) {
            $resourceName = substr($entityMetadata->name, strlen($entityMetadata->namespace) + 1);

//        echo "create $resourceName\n";

            $serviceResource->setModuleName($moduleName);
            $serviceResource->create(array(
                'resourcename' => $resourceName,
                'entityClass' => $entityMetadata->name,
                'pageSizeParam' => 'page',
                'identifierName' => 'id',
                'routeMatch' => strtolower(substr($resourceName, 0, 1)) . substr($resourceName, 1),
            ));
        }


#        print_r(get_class_methods($serviceResource));
        die('API Created');


        return $viewModel;
    }

    public function buildAction()
    {
        if ($this->getRequest()->getPost()->get('run')) die ('no run');

        die('run');
    }
}
