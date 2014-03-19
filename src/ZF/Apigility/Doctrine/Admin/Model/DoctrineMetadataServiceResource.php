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
use ZF\Apigility\Doctrine\Admin\Model\DoctrineMetadataServiceModel;
use Zend\Filter\FilterChain;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

class DoctrineMetadataServiceResource
    extends AbstractResourceListener
    implements ServiceManagerAwareInterface
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

    public function create($data) {
        throw new \Exception('Not Implemented');
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
            return new ApiProblem(500, 'No objectManager manager specificed in request.');
        }

        $objectManager = $this->getServiceManager()->get($objectManagerAlias);
        $metadataFactory = $objectManager->getMetadataFactory();

        $metadata = $metadataFactory->getMetadataFor($entityClassName);

        $entityClass = $this->getEntityClass();
        $metadataEntity = new $entityClass;
        $metadataEntity->exchangeArray((array)$metadata);

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
            return new ApiProblem(500, 'No objectManager manager specificed in request.');
        }

        $objectManager = $this->getServiceManager()->get($objectManagerClass);
        $metadataFactory = $objectManager->getMetadataFactory();

        $return = [];
        foreach ($metadataFactory->getAllMetadata() as $metadata) {
            $entityClass = $this->getEntityClass();
            $metadataEntity = new $entityClass;
            $metadataEntity->exchangeArray((array)$metadata);

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
