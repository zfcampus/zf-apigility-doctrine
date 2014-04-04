<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class OrX extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = $this->normalizeQueryType($option);

        $orX = $queryBuilder->expr()->orX();
        $em = $queryBuilder->getEntityManager();
        $qb = $em->createQueryBuilder();

        foreach ($option['conditions'] as $condition) {
            $filter = $this->getFilterManager()->get(strtolower($condition['type']), [$this->getFilterManager()]);
            $filter->filter($qb, $metadata, $condition);
        }

        $orX->addMultiple($qb->getDqlParts()['where']->getParts());
        foreach ($qb->getParameters() as $value) {
            $queryBuilder->getParameters()->add($value);
        }

        $queryBuilder->$queryType($orX);
    }
}
