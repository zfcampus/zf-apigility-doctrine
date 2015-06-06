<?php

namespace ZFTestApigilityDb\Query\Provider\Artist;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;
use ZF\Apigility\Doctrine\Server\Query\Provider\AbstractQueryProvider;

class UpdateQueryProvider extends AbstractQueryProvider
{
    /**
     * @param string $entityClass
     * @param array  $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    public function createQuery(ResourceEvent $event, $entityClass, $parameters)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->select('row')
            ->from($entityClass, 'row');

        return $queryBuilder;
    }
}
