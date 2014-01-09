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

        $config = $this->getServiceLocator()->get('Config');
        $writer = new PhpArrayWriter();
        $moduleConfig = new ConfigResource($config, 'module/' . $moduleName . '/config/module.config.php', $writer);

        $moduleConfig->patch(array(), true);

        $this->plugin('redirect')->toRoute('soliantconsulting-apigility-admin-select-entities',
            array(
                'moduleName' => $moduleName,
                'objectManagerAlias' => 'doctrine.entitymanager.orm_default'
            )
        );
    }

    public function selectEntitiesAction()
    {
        $moduleName = $this->params()->fromRoute('moduleName');
        $objectManagerAlias = $this->params()->fromRoute('objectManagerAlias');
        if (!$moduleName or !$objectManagerAlias) {
            throw new \Exception('Invalid or missing module name or objectManagerAlias');
        }

        $viewModel = new ViewModel;
        $viewModel->setTemplate('soliant-consulting/apigility/admin/app/select-entities.phtml');

        try {
            $objectManager = $this->getServiceLocator()->get($objectManagerAlias);
            $metadataFactory = $objectManager->getMetadataFactory();

            $viewModel->setVariable('allMetadata', $metadataFactory->getAllMetadata());
        } catch (\Exception $e) {
            $viewModel->setVariable('invalidObjectManager', true);
        }

        $viewModel->setVariable('moduleName', $moduleName);
        $viewModel->setVariable('objectManagerAlias', $objectManagerAlias);

        return $viewModel;
    }

    public function createResourcesAction()
    {
        $moduleName = $this->params()->fromRoute('moduleName');
        $objectManagerAlias = $this->params()->fromPost('objectManagerAlias');
        if (!$moduleName or !$objectManagerAlias) {
            throw new \Exception('Invalid or missing module name or object manager alias');
        }

        $entitiyClassNames = $this->params()->fromPost('entityClassName');
        if (!sizeof($entitiyClassNames)) {
            throw new \Exception('No entities selected to Apigility-enable');
        }

        // Get the route prefix and remove any / from ends of string
        $routePrefix = $this->params()->fromPost('routePrefix');
        if (!$routePrefix) {
            $routePrefix = '';
        } else {
            while(substr($routePrefix, 0, 1) == '/') {
                $routePrefix = substr($routePrefix, 1);
            }

            while(substr($routePrefix, strlen($routePrefix) - 1) == '/') {
                $routePrefix = substr($routePrefix, 0, strlen($routePrefix) - 1);
            }
        }

        if ($routePrefix) {
            $routePrefix = '/' . $routePrefix;
        }

        $useEntityNamespacesForRoute = (boolean)$this->params()->fromPost('useEntityNamespacesForRoute');
        $hydrateByValue = (boolean)$this->params()->fromPost('hydrateByValue');

        $objectManager = $this->getServiceLocator()->get($objectManagerAlias);
        $metadataFactory = $objectManager->getMetadataFactory();

        $serviceResource = $this->getServiceLocator()->get('SoliantConsulting\Apigility\Admin\Model\DoctrineRestServiceResource');

        // Generate a session id for results on next page
        session_start();
        $results = md5(uniqid());

        foreach ($metadataFactory->getAllMetadata() as $entityMetadata) {
            if (!in_array($entityMetadata->name, $entitiyClassNames)) continue;

            $resourceName = substr($entityMetadata->name, strlen($entityMetadata->namespace) + 1);

            if (sizeof($entityMetadata->identifier) !== 1) {
                throw new \Exception($entityMetadata->name . " does not have exactly one identifier and cannot be generated");
            }

            $filter = new FilterChain();
            $filter->attachByName('WordCamelCaseToUnderscore')
                   ->attachByName('StringToLower');

            if ($useEntityNamespacesForRoute) {
                $route = $routePrefix . '/' . $filter(str_replace('\\', '/', $entityMetadata->name));
            } else {
                $route = $routePrefix . '/' . $filter($resourceName);
            }

            $hydratorName = substr($entityMetadata->name, strlen($entityMetadata->namespace) + 1);
            $hydratorName = $moduleName . '\\V1\\Rest\\' . $resourceName . '\\' . $resourceName . 'Hydrator';

            $serviceResource->setModuleName($moduleName);
            $serviceResource->create(array(
                'objectManager' => $objectManagerAlias,
                'resourcename' => $resourceName,
                'entityClass' => $entityMetadata->name,
                'pageSizeParam' => 'limit',
                'routeIdentifierName' => $filter($resourceName) . '_id',
                'entityIdentifierName' => array_pop($entityMetadata->identifier),
                'routeMatch' => $route,
                'hydratorName' => $hydratorName,
                'hydrateByValue' => $hydrateByValue,
            ));

            $_SESSION[$results][$entityMetadata->name] = $route;


            foreach ($entityMetadata->associationMappings as $mapping) {
                switch ($mapping['type']) {
                    case 4:
                        $rpcServiceResource = $this->getServiceLocator()->get('SoliantConsulting\Apigility\Admin\Model\DoctrineRpcServiceResource');
                        $rpcServiceResource->setModuleName($moduleName);
                        $rpcServiceResource->create(array(
                            'service_name' => $resourceName . '' . $mapping['fieldName'],
                            'route' => $mappingRoute = $route . '[/:parent_id]/' . $filter($mapping['fieldName']) . '[/:child_id]',
                            'http_methods' => array(
                                'GET',
                            ),
                            'options' => array(
                                'target_entity' => $mapping['targetEntity'],
                                'source_entity' => $mapping['sourceEntity'],
                                'field_name' => $mapping['fieldName'],
                            ),
                        ));

                        $_SESSION[$results][$entityMetadata->name . $mapping['fieldName']] = $mappingRoute;

                        break;
                    default:
                        break;
                }
            }
        }

#print_r($_SESSION[$results]);die('asdf');
        return $this->plugin('redirect')->toRoute('soliantconsulting-apigility-admin-done', array('moduleName' => $moduleName, 'results' => $results));
    }

    public function doneAction() {
        $moduleName = $this->params()->fromRoute('moduleName');
        if (!$moduleName) {
            throw new \Exception('Invalid or missing module name');
        }

        session_start();
        $results = $this->params()->fromRoute('results');

        $viewModel = new ViewModel;
        $viewModel->setTemplate('soliant-consulting/apigility/admin/app/done.phtml');
        $viewModel->setVariable('moduleName', $moduleName);

        $viewModel->setVariable('results', $_SESSION[$results]);

        return $viewModel;
    }
}
