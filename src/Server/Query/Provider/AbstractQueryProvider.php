<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server\Query\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Zend\Paginator\Adapter\AdapterInterface;
use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOrmAdapter;
use ZF\Rest\ResourceEvent;

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
     * @param ResourceEvent $event
     * @param string $entityClass
     * @param array $parameters
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    abstract public function createQuery(ResourceEvent $event, $entityClass, $parameters);

    /**
     * @param $queryBuilder
     * @return AdapterInterface
     */
    public function getPaginatedQuery($queryBuilder)
    {
        $adapter = new DoctrineOrmAdapter($queryBuilder->getQuery(), false);

        return $adapter;
    }

    /**
     * @param $entityClass
     * @return int
     */
    public function getCollectionTotal($entityClass)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $entityMetaData = $cmf->getMetadataFor($entityClass);

        $identifier = $entityMetaData->getIdentifier();
        $queryBuilder
            ->select('count(row.' . $identifier[0] . ')')
            ->from($entityClass, 'row');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
