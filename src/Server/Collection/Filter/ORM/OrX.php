<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class OrX extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        if (isset($option['where'])) {
            if ($option['where'] == 'and') {
                $queryType = 'andWhere';
            } elseif ($option['where'] == 'or') {
                $queryType = 'orWhere';
            }
        }

        if (!isset($queryType)) {
            $queryType = 'andWhere';
        }

        $orX = $queryBuilder->expr()->orX();
        $em = $queryBuilder->getEntityManager();
        $qb = $em->createQueryBuilder();

        foreach ($option['conditions'] as $condition) {
            $filter = $this->getFilterManager()->get(strtolower($condition['type']), [$this->getFilterManager()]);
            $filter->filter($qb, $metadata, $condition);
        }

        $orX->addMultiple($qb->getDqlParts()['where']->getParts());
        $queryBuilder->setParameters($qb->getParameters());

        $queryBuilder->$queryType($orX);
    }
}
