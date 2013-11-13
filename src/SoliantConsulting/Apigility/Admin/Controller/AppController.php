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
use Zend\Filter\FilterChain;

class AppController extends AbstractActionController
{
    public function indexAction()
    {
        $viewModel = new ViewModel;
        $viewModel->setTemplate('soliant-consulting/apigility/admin/app/index.phtml');

        $objectManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $objectManager->getMetadataFactory();

        $viewModel->setVariable('allMetadata', $metadataFactory->getAllMetadata());

        return $viewModel;
    }

    public function createModuleAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->plugin('redirect')->toRoute('soliantconsulting-apigility-admin');
        }

        $moduleName = $this->getRequest()->getPost()->get('moduleName');
        if (!$moduleName) {
            throw new \Exception('Invalid or missing module name');
        }

        $moduleResource = $this->getServiceLocator()->get('ZF\Apigility\Admin\Model\ModuleResource');
        $moduleResource->setModulePath(realpath(__DIR__ . '/../../../../../../../../'));

        $metadata = $moduleResource->create(array(
            'name' =>  $moduleName,
        ));

        // Set renderer defaults
        $patchConfig = array(
            'zf-hal' => array(
                'renderer' => array (
                  'default_hydrator' => 'ArraySerializable',
                  'render_embedded_resources' => false,
                ),
            )
        );

        $config = $this->getServiceLocator()->get('Config');
        $writer = new PhpArrayWriter();
        $moduleConfig = new ConfigResource($config, 'module/' . $moduleName . '/config/module.config.php', $writer);

        $moduleConfig->patch($patchConfig, true);

        $this->plugin('redirect')->toRoute('soliantconsulting-apigility-admin-select-entities', array('moduleName' => $moduleName));
    }

    public function selectEntitiesAction()
    {
        $moduleName = $this->params()->fromRoute('moduleName');
        if (!$moduleName) {
            throw new \Exception('Invalid or missing module name');
        }

        $viewModel = new ViewModel;
        $viewModel->setTemplate('soliant-consulting/apigility/admin/app/select-entities.phtml');

        $objectManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $objectManager->getMetadataFactory();

        $viewModel->setVariable('allMetadata', $metadataFactory->getAllMetadata());
        $viewModel->setVariable('moduleName', $moduleName);

        return $viewModel;
    }

    public function createResourcesAction()
    {
        $moduleName = $this->params()->fromRoute('moduleName');
        if (!$moduleName) {
            throw new \Exception('Invalid or missing module name');
        }

        $entitiyClassNames = $this->params()->fromPost('entityClassName');
        if (!sizeof($entitiyClassNames)) {
            throw new \Exception('No entities selected to Apigility-enable');
        }

        $objectManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $objectManager->getMetadataFactory();

        $serviceResource = $this->getServiceLocator()->get('SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceResource');

        foreach ($metadataFactory->getAllMetadata() as $entityMetadata) {
            if (!in_array($entityMetadata->name, $entitiyClassNames)) continue;

            $resourceName = substr($entityMetadata->name, strlen($entityMetadata->namespace) + 1);

            if (sizeof($entityMetadata->identifier) !== 1) {
                throw new \Exception($entityMetadata->name . " does not have exactly one identifier and cannot be generated");
            }

            $filter = new FilterChain();
            $filter->attachByName('WordCamelCaseToUnderscore')
                   ->attachByName('StringToLower');

            $serviceResource->setModuleName($moduleName);
            $serviceResource->create(array(
                'resourcename' => $resourceName,
                'entityClass' => $entityMetadata->name,
                'pageSizeParam' => 'page',
                'identifierName' => array_pop($entityMetadata->identifier),

                'routeMatch' => '/api/' . $filter($resourceName),
            ));
        }

        $this->plugin('redirect')->toRoute('soliantconsulting-apigility-admin-done', array('moduleName' => $moduleName));
    }

    public function doneAction() {
        $moduleName = $this->params()->fromRoute('moduleName');
        if (!$moduleName) {
            throw new \Exception('Invalid or missing module name');
        }

        $viewModel = new ViewModel;
        $viewModel->setTemplate('soliant-consulting/apigility/admin/app/done.phtml');
        $viewModel->setVariable('moduleName', $moduleName);

        $objectManager = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $objectManager->getMetadataFactory();

        $viewModel->setVariable('allMetadata', $metadataFactory->getAllMetadata());

        return $viewModel;
    }
}
