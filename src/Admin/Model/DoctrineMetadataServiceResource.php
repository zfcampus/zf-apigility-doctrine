<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use Zend\ServiceManager\ServiceManager;
use Exception;

class DoctrineMetadataServiceResource extends AbstractResourceListener
{
    protected $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @codeCoverageIgnore
     */
    public function create($data)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Fetch REST metadata
     *
     * @param  string $id
     * @return RestServiceEntity|ApiProblem
     */
    public function fetch($entityClassName)
    {
        $objectManagerAlias = $this->getEvent()->getRouteParam('object_manager_alias');

        if (!$objectManagerAlias) {
            // @codeCoverageIgnoreStart
            return new ApiProblem(500, 'No objectManager manager specificed in request.');
            // @codeCoverageIgnoreEnd
        }

        $objectManager = $this->getServiceManager()->get($objectManagerAlias);
        $metadataFactory = $objectManager->getMetadataFactory();

        $metadata = $metadataFactory->getMetadataFor($entityClassName);

        $entityClass = $this->getEntityClass();
        $metadataEntity = new $entityClass;
        $metadataEntity->exchangeArray((array) $metadata);

        return $metadataEntity;
    }

    /**
     * Fetch metadata for all REST services
     *
     * @param  array $params
     * @return RestServiceEntity[]
     */
    public function fetchAll($params = array())
    {
        if ($this->getEvent()->getRouteParam('object_manager_alias')) {
            $objectManagerClass = $this->getEvent()->getRouteParam('object_manager_alias');
        }

        if (!$objectManagerClass) {
            // @codeCoverageIgnoreStart
            return new ApiProblem(500, 'No objectManager manager specificed in request.');
            // @codeCoverageIgnoreEnd
        }

        $objectManager = $this->getServiceManager()->get($objectManagerClass);
        $metadataFactory = $objectManager->getMetadataFactory();

        $return = array();
        foreach ($metadataFactory->getAllMetadata() as $metadata) {
            $entityClass = $this->getEntityClass();
            $metadataEntity = new $entityClass;
            $metadataEntity->exchangeArray((array) $metadata);

            $return[] = $metadataEntity;
        }

        return $return;
    }

    /**
     * @codeCoverageIgnore
     */
    public function patch($id, $data)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * @codeCoverageIgnore
     */
    public function delete($id)
    {
        throw new Exception('Not Implemented');
    }
}
