<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use RuntimeException;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use ZF\Rest\Exception\CreationException;
use ZF\Rest\Exception\PatchException;
use ZF\Apigility\Doctrine\Admin\Model\NewRestServiceEntity;
use Zend\Filter\FilterChain;

class DoctrineRestServiceResource extends AbstractResourceListener
{
    /**
     * @var RestServiceModel
     */
    protected $model;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var RestServiceModelFactory
     */
    protected $restFactory;

    /**
     * @param  RestServiceModelFactory $restFactory
     */
    public function __construct(DoctrineRestServiceModelFactory $restFactory)
    {
        $this->restFactory = $restFactory;
    }

    public function setModuleName($value)
    {
        $this->moduleName = $value;
        return $this;
    }

    /**
     * @return string
     * @throws RuntimeException if module name is not present in route matches
     */
    public function getModuleName()
    {
        if (null !== $this->moduleName) {
            return $this->moduleName;
        }

        $moduleName = $this->getEvent()->getRouteParam('name', false);
        if (!$moduleName) {
            throw new RuntimeException(sprintf(
                '%s cannot operate correctly without a "name" segment in the route matches',
                __CLASS__
            ));
        }
        $this->moduleName = $moduleName;
        return $moduleName;
    }

    /**
     * @return RestServiceModel
     */
    public function getModel($type = DoctrineRestServiceModelFactory::TYPE_DEFAULT)
    {
        if ($this->model instanceof DoctrineRestServiceModel) {
            return $this->model;
        }
        $moduleName = $this->getModuleName();
        $this->model = $this->restFactory->factory($moduleName, $type);
        return $this->model;
    }

    /**
     * Create a new REST service
     *
     * @param  array|object $data
     * @return RestServiceEntity
     * @throws CreationException
     */
    public function create($data)
    {
/*
    Examples
{
    "objectManager": "doctrine.entitymanager.orm_default",
    "resourceName": "Artist",
    "entityClass": "Db\\Entity\\Artist",
    "pageSizeParam": "limit",
    "routeIdentifierName": "artist_id",
    "entityIdentifierName": "id",
    "routeMatch": "/api/artist",
    "hydratorName": "DbApi\\V1\\Rest\\Artist\\AlbumHydrator",
    "hydrateByValue": true
}

{
    "objectManager": "doctrine.entitymanager.orm_default",
    "resourceName": "Album",
    "entityClass": "Db\\Entity\\Album",
    "pageSizeParam": "limit",
    "routeIdentifierName": "album_id",
    "entityIdentifierName": "id",
    "routeMatch": "/api/album",
    "hydratorName": "DbApi\\V1\\Rest\\Album\\AlbumHydrator",
    "hydrateByValue": true
}

*/
        if (is_object($data)) {
            $data = (array) $data;
        }

        $type = DoctrineRestServiceModelFactory::TYPE_DEFAULT;
        $creationData = new NewRestServiceEntity();

        $creationData->exchangeArray($data);

        $model = $this->getModel($type);

        try {
            $service = $model->createService($creationData);
        } catch (\Exception $e) {
            throw new CreationException('Unable to create REST service', $e->getCode(), $e);
        }

        $objectManager = $this->restFactory->getServiceManager()->get($data['objectManager']);
        $entityMetadata = $objectManager->getMetadataFactory()->getMetadataFor($data['entityClass']);

        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
               ->attachByName('StringToLower');

        foreach ($entityMetadata->associationMappings as $mapping) {
            switch ($mapping['type']) {
                case 4:
                    $rpcServiceResource = $this->restFactory->getServiceManager()->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource');
                    $rpcServiceResource->setModuleName($this->getModuleName());
                    $rpcServiceResource->create(array(
                        'service_name' => $data['resourceName'] . '' . $mapping['fieldName'],
                        'route' => $mappingRoute = $data['routeMatch'] . '[/:parent_id]/' . $filter($mapping['fieldName']) . '[/:child_id]',
                        'http_methods' => array(
                            'GET',
                        ),
                        'options' => array(
                            'target_entity' => $mapping['targetEntity'],
                            'source_entity' => $mapping['sourceEntity'],
                            'field_name' => $mapping['fieldName'],
                        ),
                    ));

                    // Should these be included in the return?
                    break;
                default:
                    break;
            }
        }

        return $service;
    }

    /**
     * Fetch REST metadata
     *
     * @param  string $id
     * @return RestServiceEntity|ApiProblem
     */
    public function fetch($id)
    {
        $service = $this->getModel()->fetch($id);
        if (!$service instanceof DoctrineRestServiceEntity) {
            return new ApiProblem(404, 'REST service not found');
        }
        return $service;
    }

    /**
     * Fetch metadata for all REST services
     *
     * @param  array $params
     * @return RestServiceEntity[]
     */
    public function fetchAll($params = array())
    {
        $version = $this->getEvent()->getQueryParam('version', null);
        return $this->getModel()->fetchAll($version);
    }

    /**
     * Update an existing REST service
     *
     * @param  string $id
     * @param  object|array $data
     * @return ApiProblem|RestServiceEntity
     * @throws PatchException if unable to update configuration
     */
    public function patch($id, $data)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return new ApiProblem(400, 'Invalid data provided for update');
        }

        if (empty($data)) {
            return new ApiProblem(400, 'No data provided for update');
        }

        // Make sure we have an entity first
        $model  = $this->getModel();
        $entity = $model->fetch($id);

        $entity->exchangeArray($data);

        try {
            switch (true) {
                case ($entity instanceof DoctrineRestServiceEntity):
                default:
                    $updated = $model->updateService($entity);
            }
        } catch (\Exception $e) {
            throw new PatchException('Error updating Doctrine REST service', 500, $e);
        }

        return $updated;
    }

    /**
     * Delete a service
     *
     * @param  string $id
     * @return true
     */
    public function delete($id)
    {
        // Make sure we have an entity first
        $model  = $this->getModel();
        $entity = $model->fetch($id);

        try {
            switch (true) {
                case ($entity instanceof DoctrineRestServiceEntity):
                default:
                    $result = $model->deleteService($entity->controllerServiceName);

                    if ($result instanceof ApiProblem) {
                        return $response;
                    }
            }
        } catch (\Exception $e) {
            throw new \Exception('Error deleting Doctrine REST service', 500, $e);
        }

        return true;
    }
}
