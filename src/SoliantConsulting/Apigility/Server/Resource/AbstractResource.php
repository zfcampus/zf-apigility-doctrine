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
        if (!isset($parameters['page'])) {
            $parameters['page'] = 0;
        }
        if (!isset($parameters['limit'])) {
            $parameters['limit'] = 25;
        }
        if ($parameters['limit'] > 100) {
            $parameters['limit'] = 100;
        }

        // Limits
        $queryBuilder->setFirstResult($parameters['page'] * $parameters['limit']);
        $queryBuilder->setMaxResults($parameters['limit']);

        // Orderby
        if (!isset($parameters['orderBy'])) {
            $parameters['orderBy'] = array('id' => 'asc');
        }
        foreach($parameters['orderBy'] as $fieldName => $sort) {
            $queryBuilder->addOrderBy("row.$fieldName", $sort);
        }

        unset($parameters['limit'], $parameters['page'], $parameters['orderBy']);

        /*
        // Testing GET request builder

        echo http_buildquery(
            array(
                'query' => array(
                    array('field' => '_DatasetID','type' => 'eq' , 'value' => 1),
                    array('field' =>'Cycle_number','type'=>'between', 'from' => 10, 'to'=>100),
                    array('field'=>'Cycle_number', 'type' => 'decimation', 'value' => 10)
                ),
                'orderBy' => array('columnOne' => 'ASC', 'columnTwo' => 'DESC')
            )
        );

        */

        // Add query parameters
        if (isset($parameters['query'])) {
            foreach ($parameters['query'] as $option) {
                // Allow and/or queries
                if (isset($option['where'])) {
                    if ($option['where'] == 'and') $queryType = 'andWhere';
                    if ($option['where'] == 'or') $queryType = 'orWhere';
                } else {
                    $queryType == 'andWhere';
                }

                switch (strtolower($option['type'])) {
                    case 'eq':
                        // field, value
                        $queryBuilder->$queryType($queryBuilder->expr()->eq('row.' . $option['field'], $option['value']));
                        break;

                    case 'neq':
                        $queryBuilder->$queryType($queryBuilder->expr()->neq('row.' . $option['field'], $option['value']));
                        break;

                    case 'lt':
                        $queryBuilder->$queryType($queryBuilder->expr()->lt('row.' . $option['field'], $option['value']));
                        break;

                    case 'lte':
                        $queryBuilder->$queryType($queryBuilder->expr()->lte('row.' . $option['field'], $option['value']));
                        break;

                    case 'gt':
                        $queryBuilder->$queryType($queryBuilder->expr()->gt('row.' . $option['field'], $option['value']));
                        break;

                    case 'gte':
                        $queryBuilder->$queryType($queryBuilder->expr()->gte('row.' . $option['field'], $option['value']));
                        break;

                    case 'isnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->isNull('row.' . $option['field']));
                        break;

                    case 'isnotnull':
                        $queryBuilder->$queryType($queryBuilder->expr()->isNotNull('row.' . $option['field']));
                        break;

                    case 'in':
                        $queryBuilder->$queryType($queryBuilder->expr()->in('row.' . $option['field'], $option['values']));
                        break;

                    case 'notin':
                        $queryBuilder->$queryType($queryBuilder->expr()->notIn('row.' . $option['field'], $option['values']));
                        break;

                    case 'like':
                        $queryBuilder->$queryType($queryBuilder->expr()->like('row.' . $option['field'], $queryBuilder->expr()->literal($option['value'])));
                        break;

                    case 'notlike':
                        $queryBuilder->$queryType($queryBuilder->expr()->notLike('row.' . $option['field'], $queryBuilder->expr()->literal($option['value'])));
                        break;

                    case 'between':
                        // field, from, to
                        $queryBuilder->$queryType($queryBuilder->expr()->between('row.' . $option['field'], $option['from'], $option['to']));
                        break;

                    case 'decimation':
                        // field, value
                        $md5 = 'a' . md5(uniqid()); # parameter cannot start with #
                        $queryBuilder->$queryType("mod(row." . $option['field'] . ", :$md5) = 0")
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
