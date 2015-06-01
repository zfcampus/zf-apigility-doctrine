<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider;

use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOrmAdapter;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Zend\Paginator\Adapter\AdapterInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;

/**
 * Class FetchAllOrm
 *
 * @package ZF\Apigility\Doctrine\Server\Query\Provider
 */
abstract class AbstractQueryProvider implements ObjectManagerAwareInterface, QueryProviderInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Get the object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @param string $entityClass
     * @param array  $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    abstract public function createQuery(ResourceEvent $event, $entityClass, $parameters);

    /**
     * @param   $queryBuilder
     *
     * @return AdapterInterface
     */
    public function getPaginatedQuery($queryBuilder)
    {
        $adapter = new DoctrineOrmAdapter($queryBuilder->getQuery(), false);

        return $adapter;
    }

    /**
     * @param   $entityClass
     *
     * @return int
     */
    public function getCollectionTotal($entityClass)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $entityMetaData = $cmf->getMetadataFor($entityClass);

        $identifier = $entityMetaData->getIdentifier();
        $queryBuilder->select('count(row.' . $identifier[0] . ')')
            ->from($entityClass, 'row');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
