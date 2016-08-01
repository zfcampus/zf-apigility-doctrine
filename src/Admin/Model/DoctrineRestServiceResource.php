<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use RuntimeException;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use ZF\Rest\Exception\CreationException;
use ZF\Rest\Exception\PatchException;

class DoctrineRestServiceResource extends AbstractResourceListener
{
    /**
     * @var DoctrineRestServiceModel
     */
    protected $model;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var DoctrineRestServiceModelFactory
     */
    protected $restFactory;

    /**
     * Constructor
     *
     * @param DoctrineRestServiceModelFactory $restFactory
     */
    public function __construct(DoctrineRestServiceModelFactory $restFactory)
    {
        $this->restFactory = $restFactory;
    }

    /**
     * Set module name
     *
     * @param string $moduleName
     * @return DoctrineRestServiceResource
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    /**
     * Get module name
     *
     * @return string
     * @throws RuntimeException if module name is not present in route matches
     */
    public function getModuleName()
    {
        if (null !== $this->moduleName) {
            return $this->moduleName;
        }

        $moduleName = $this->getEvent()->getRouteParam('name', false);
        if (! $moduleName) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf(
                    '%s cannot operate correctly without a "name" segment in the route matches',
                    __CLASS__
                )
            );
            // @codeCoverageIgnoreEnd
        }
        $this->moduleName = $moduleName;

        return $moduleName;
    }

    /**
     * @param string $type
     * @return DoctrineRestServiceModel
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
     * @return DoctrineRestServiceEntity
     * @throws CreationException
     */
    public function create($data)
    {
        if (is_object($data)) {
            // @codeCoverageIgnoreStart
            $data = (array) $data;
        }
            // @codeCoverageIgnoreEnd

        $type = DoctrineRestServiceModelFactory::TYPE_DEFAULT;
        $creationData = new NewDoctrineServiceEntity();

        $creationData->exchangeArray($data);

        $model = $this->getModel($type);

        try {
            $service = $model->createService($creationData);
        } catch (\Exception $e) {
            // @codeCoverageIgnoreStart
            throw new CreationException('Unable to create REST service', $e->getCode(), $e);
            // @codeCoverageIgnoreEnd
        }

        return $service;
    }

    /**
     * Fetch REST metadata
     *
     * @param  string $id
     * @return DoctrineRestServiceEntity|ApiProblem
     */
    public function fetch($id)
    {
        $service = $this->getModel()->fetch($id);
        if (! $service instanceof DoctrineRestServiceEntity) {
            // @codeCoverageIgnoreStart
            return new ApiProblem(404, 'REST service not found');
        }
            // @codeCoverageIgnoreEnd
        return $service;
    }

    /**
     * Fetch metadata for all REST services
     *
     * @param  array $params
     * @return DoctrineRestServiceEntity[]
     */
    public function fetchAll($params = [])
    {
        $version = $this->getEvent()->getQueryParam('version', null);

        return $this->getModel()->fetchAll($version);
    }

    /**
     * Update an existing REST service
     *
     * @param  string       $id
     * @param  object|array $data
     * @return ApiProblem|DoctrineRestServiceEntity
     * @throws PatchException               if unable to update configuration
     */
    public function patch($id, $data)
    {
        // @codeCoverageIgnoreStart
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (! is_array($data)) {
            return new ApiProblem(400, 'Invalid data provided for update');
        }

        if (empty($data)) {
            return new ApiProblem(400, 'No data provided for update');
        }
        // @codeCoverageIgnoreEnd

        // Make sure we have an entity first
        $model  = $this->getModel();
        $entity = $model->fetch($id);

        $entity->exchangeArray($data);

        $updated = $model->updateService($entity);

        return $updated;
    }

    /**
     * Delete a service
     *
     * @param mixed $id
     * @return bool
     * @throws \Exception
     */
    public function delete($id)
    {
        // Make sure we have an entity first
        $model  = $this->getModel();
        $entity = $model->fetch($id);

        $request   = $this->getEvent()->getRequest();
        $recursive = $request->getQuery('recursive', false);

        try {
            switch (true) {
                case ($entity instanceof DoctrineRestServiceEntity):
                default:
                    $model->deleteService($entity->controllerServiceName, $recursive);
            }
        } catch (\Exception $e) {
            // @codeCoverageIgnoreStart
            throw new \Exception('Error deleting REST service', 500, $e);
        }
            // @codeCoverageIgnoreEnd
        return true;
    }
}
