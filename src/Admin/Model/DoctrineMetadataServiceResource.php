<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Zend\ServiceManager\ServiceManager;
use ZF\Apigility\Admin\Model\RestServiceEntity;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

class DoctrineMetadataServiceResource extends AbstractResourceListener
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @param ServiceManager $serviceManager
     * @return $this
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function create($data)
    {
        throw new \Exception('Not Implemented');
    }

    /**
     * Fetch REST metadata
     *
     * @param string $entityClassName
     * @return RestServiceEntity|ApiProblem
     */
    public function fetch($entityClassName)
    {
        $objectManagerAlias = $this->getEvent()->getRouteParam('object_manager_alias');

        if (! $objectManagerAlias) {
            return new ApiProblem(500, 'No objectManager manager specified in request.');
        }

        $objectManager = $this->getServiceManager()->get($objectManagerAlias);
        /** @var AbstractClassMetadataFactory $metadataFactory */
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
     * @param array $params
     * @return RestServiceEntity[]|ApiProblem
     */
    public function fetchAll($params = [])
    {
        if ($this->getEvent()->getRouteParam('object_manager_alias')) {
            $objectManagerClass = $this->getEvent()->getRouteParam('object_manager_alias');
        }

        if (empty($objectManagerClass)) {
            return new ApiProblem(500, 'No objectManager manager specified in request.');
        }

        $objectManager = $this->getServiceManager()->get($objectManagerClass);
        /** @var AbstractClassMetadataFactory $metadataFactory */
        $metadataFactory = $objectManager->getMetadataFactory();

        $return = [];
        foreach ($metadataFactory->getAllMetadata() as $metadata) {
            $entityClass = $this->getEntityClass();
            $metadataEntity = new $entityClass;
            $metadataEntity->exchangeArray((array) $metadata);

            $return[] = $metadataEntity;
        }

        return $return;
    }

    public function patch($id, $data)
    {
        throw new \Exception('Not Implemented');
    }

    public function delete($id)
    {
        throw new \Exception('Not Implemented');
    }
}
