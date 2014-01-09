<?php

namespace ZF\Apigility\Doctrine\Server\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

abstract class RpcController extends AbstractActionController
{
    public function indexAction()
    {
        $parentId = $this->params()->fromRoute('parent_id');
        if (!$parentId) {
            return new ApiProblemResponse(
                new ApiProblem(400, "Parent ID is required")
            );
        }
        $childId = $this->params()->fromRoute('child_id');

        $config = $this->getServiceLocator()->get('Config');
        $zfRpcDoctrineControllerArrayKey = array_search(get_class($this), $config['controllers']['invokables']);

        $associationConfig = $config['zf-rpc-doctrine-controller'][$zfRpcDoctrineControllerArrayKey];
        $metadataConfig = $config['zf-hal']['metadata_map'][$associationConfig['source_entity']];
        $hydratorConfig = $config['zf-rest-doctrine-hydrator'][$metadataConfig['hydrator']];

        $objectManager = $this->getServiceLocator()->get($hydratorConfig['object_manager']);
        $metadataFactory = $objectManager->getMetadataFactory();

        // Find target entity controller to dispatch
        foreach ($config['zf-rest'] as $controllerName => $controllerConfig) {
            if ($associationConfig['target_entity'] == $controllerConfig['entity_class']) {
                $targetRouteParam = $controllerConfig['route_identifier_name'];
                break;
            }
        }

        // Find source entity field name for target
        $sourceMetadata = $metadataFactory->getMetadataFor($associationConfig['source_entity']);
        foreach ($sourceMetadata->associationMappings as $mapping) {
            if ($mapping['sourceEntity'] == $associationConfig['source_entity']
                and $mapping['targetEntity'] == $associationConfig['target_entity']
                and $mapping['fieldName'] == $associationConfig['field_name']) {
                $sourceField = $mapping['mappedBy'];
                break;
            }
        }

        $query = (array)$this->getRequest()->getQuery()->get('query');
        $orderBy = $this->getRequest()->getQuery()->get('orderBy');

        if ($childId) {
            // Verify child is a child of parent
            $child = $objectManager->getRepository($associationConfig['target_entity'])->findOneBy(array(
                'id' => $childId,
                $sourceField => $parentId,
            ));

            if ($child) {
                $this->getRequest()->setMethod('GET');
                $hal = $this->forward()->dispatch($controllerName, array(
                    $targetRouteParam => $childId,
                ));
                $renderer = $this->getServiceLocator()->get('ZF\Hal\JsonRenderer');
                $data = json_decode($renderer->render($hal), true);

                return $data;
            } else {
                return new ApiProblemResponse(
                    new ApiProblem(400, 'Resource not found.')
                );
            }

        } else {
            $query[] = array('type' => 'eq', 'field' => $sourceField, 'value' => $parentId);

            $this->getRequest()->setMethod('GET');
            $hal = $this->forward()->dispatch($controllerName, array(
                'query' => $query,
                'orderBy' => $orderBy,
            ));
            $renderer = $this->getServiceLocator()->get('ZF\Hal\JsonRenderer');
            $data = json_decode($renderer->render($hal), true);

            return $data;
        }
    }
}
