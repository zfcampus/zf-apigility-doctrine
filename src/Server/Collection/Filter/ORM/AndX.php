<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class AndX extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = $this->normalizeQueryType($option);

        $andX = $queryBuilder->expr()->andX();
        $em = $queryBuilder->getEntityManager();
        $qb = $em->createQueryBuilder();

        foreach ($option['conditions'] as $condition) {
            $filter = $this->getFilterManager()->get(strtolower($condition['type']), [$this->getFilterManager()]);
            $filter->filter($qb, $metadata, $condition);
        }

        $andX->addMultiple($qb->getDqlParts()['where']->getParts());
        foreach ($qb->getParameters() as $key => $value) {
            $queryBuilder->getParameters()->add($value);
        }

        $queryBuilder->$queryType($andX);
    }
}
