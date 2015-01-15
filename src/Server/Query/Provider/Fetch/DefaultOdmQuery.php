<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider\Fetch;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use ZF\ApiProblem\ApiProblem;
use Doctrine\Common\Persistence\ObjectManager;
use ZF\Apigility\Doctrine\Server\Query\Provider\Fetch\FetchQueryProviderInterface;

/**
 * Class DefaultOrmQuery
 *
 * @package ZF\Apigility\Doctrine\Server\Query\Provider\Fetch
 */
class DefaultOdmQuery implements ObjectManagerAwareInterface, FetchQueryProviderInterface
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
     * {@inheritDoc}
     */
    public function createQuery($entityClass)
    {
        /** @var \Doctrine\Odm\MongoDB\Query\Builder $queryBuilder */
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->find($entityClass);

        return $queryBuilder;
    }
}
