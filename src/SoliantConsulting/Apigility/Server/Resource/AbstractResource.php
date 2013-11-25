<?php
namespace SoliantConsulting\Apigility\Server\Resource;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager as ZendServiceManager;
use Doctrine\Common\Persistence\ObjectManager;

class AbstractResource extends AbstractResourceListener implements ServiceManagerAwareInterface
{
    protected $serviceManager;
    protected $objectManager;
    protected $objectManagerAlias;

    public function setServiceManager(ZendServiceManager $serviceManager) {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager() {
        return $this->serviceManager;
    }

    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
        return $this;
    }

    public function getObjectManagerAlias()
    {
        return $this->objectManagerAlias;
    }

    public function setObjectManagerAlias($value)
    {
        $this->objectManagerAlias = $value;
        return $this;
    }

    public function getObjectManager()
    {
        if (!$this->objectManager) {
            $this->setObjectManager($this->getServiceManager()->get($this->getObjectManagerAlias()));
        }

        return $this->objectManager;
    }

    /**
     * Error handling to catch E_RECOVERABLE_ERROR
     */
    public function pushErrorHandler() {
        set_error_handler(array($this, 'errorHandler'));
    }

    public function popErrorHandler() {
        restore_error_handler();
    }

    public function errorHandler($errno, $errstr, $errfile, $errline) {
        if ( E_RECOVERABLE_ERROR === $errno ) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
        return false;
    }

    /**
     * Create a resource
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function create($data)
    {
        $this->pushErrorHandler();
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;

        try {
            $entity->exchangeArray($this->populateReferences((array)$data));
            $this->getObjectManager()->persist($entity);
            $this->getObjectManager()->flush();
        } catch (\Exception $e) {
            return new ApiProblem(400, $e->getMessage());
        }

        $this->popErrorHandler();
        return $entity;
    }

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        $this->pushErrorHandler();
        $entity = $this->getObjectManager()->find($this->getEntityClass(), $id);
        if (!$entity) {
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }

        if ($entity->canDelete()) {
            $this->getObjectManager()->remove($entity);
            $this->getObjectManager()->flush();

            $this->popErrorHandler();
            return true;
        }

        $this->popErrorHandler();
        return new ApiProblem(403, 'Cannot delete entity with id ' . $id);
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function deleteList($data)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for collections');
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {
        $this->pushErrorHandler();
        $return = $this->getObjectManager()->find($this->getEntityClass(), $id);
        $this->popErrorHandler();

        return $return;
    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     */
    public function fetchAll($params = array())
    {
        $this->pushErrorHandler();
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();

        $queryBuilder->select('row')
            ->from($this->getEntityClass(), 'row');

        $parameters = $this->getEvent()->getQueryParams();

        // Defaults
        if (!isset($parameters['_page'])) {
            $parameters['_page'] = 0;
        }
        if (!isset($parameters['_limit'])) {
            $parameters['_limit'] = 25;
        }
        if ($parameters['_limit'] > 100) {
            $parameters['_limit'] = 100;
        }

        // Limits
        $queryBuilder->setFirstResult($parameters['_page'] * $parameters['_limit']);
        $queryBuilder->setMaxResults($parameters['_limit']);

        // Orderby
        if (!isset($parameters['_orderBy'])) {
            $parameters['_orderBy'] = array('id' => 'asc');
        }
        foreach($parameters['_orderBy'] as $fieldName => $sort) {
            $queryBuilder->addOrderBy("row.$fieldName", $sort);
        }

        unset($parameters['_limit'], $parameters['_page'], $parameters['_orderBy']);

        /*
        // Testing GET request builder

        echo http_build_query(
            array(
                '_query' => array(
                    array('field' => '_DatasetID','type' => 'eq' , 'value' => 1),
                    array('field' =>'Cycle_number','type'=>'between', 'from' => 10, 'to'=>100),
                    array('field'=>'Cycle_number', 'type' => 'decimation', 'value' => 10)
                ),
                '_orderBy' => array('columnOne' => 'ASC', 'columnTwo' => 'DESC')
            )
        );

        */

        // Add query parameters
        if (isset($parameters['_query'])) {
            foreach ($parameters['_query'] as $option) {
                switch ($option['type']) {
                    case 'between':
                        // field, from, to
                        $queryBuilder->andWhere($queryBuilder->expr()->between('row.' . $option['field'], $option['from'], $option['to']));
                        break;

                    case 'eq':
                        // field, value
                        $queryBuilder->andWhere($queryBuilder->expr()->eq('row.' . $option['field'], $option['value']));
                        break;

                    case 'decimation':
                        // field, value
                        $md5 = 'a' . md5(uniqid()); # parameter cannot start with #
                        $queryBuilder->andWhere("mod( row." . $option['field'] . ", :$md5)= 0")
                                     ->setParameter($md5, $option['value']);
                        break;

                    default:
                        break;
                }
            }
        }

        //print_r($queryBuilder->getDql());
        //die();
        $collectionClass = $this->getCollectionClass();
        $return = new $collectionClass($queryBuilder->getQuery(), false);

        $this->popErrorHandler();
        return $return;
    }

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patch($id, $data)
    {
        $this->pushErrorHandler();
        $entity = $this->getObjectManager()->find($this->getEntityClass(), $id);
        if (!$entity) {
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }

        $data = $this->populateReferences($data);

        $entity->exchangeArray(array_merge($entity->getArrayCopy(), (array)$data));
        $this->getObjectManager()->flush();

        $this->popErrorHandler();
        return $entity;
    }

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function update($id, $data)
    {
        $this->pushErrorHandler();
        $entity = $this->getObjectManager()->find($this->getEntityClass(), $id);
        if (!$entity) {
            return new ApiProblem(404, 'Entity with id ' . $id . ' was not found');
        }

        $newValues = $entity->getArrayCopy();
        foreach ($newValues as $key => $value) {
            if (isset($data->$key)) {
                $newValues[$key] = $data->$key;
            }
        }

        $entity->exchangeArray($this->populateReferences($newValues));
        $this->getObjectManager()->flush();
        $this->popErrorHandler();

        return $entity;
    }

    private function populateReferences($data)
    {
        $metadataFactory = $this->getObjectManager()->getMetadataFactory();
        $entityMetadata = $metadataFactory->getMetadataFor($this->getEntityClass());

        foreach($entityMetadata->getAssociationMappings() as $map) {
            switch($map['type']) {
                case 2:
                    if (isset($data[$map['fieldName']])) {
                        $data[$map['fieldName']] = $this->getObjectManager()->find($map['targetEntity'], $data[$map['fieldName']]);
                    }
                    break;
                default:
                    break;
            }
        }

        return $data;
    }
}
