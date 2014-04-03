<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class AndX extends AbstractFilter
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

        $andX = $queryBuilder->expr()->andX();
        $em = $queryBuilder->getEntityManager();
        $qb = $em->createQueryBuilder();

        foreach ($option['conditions'] as $condition) {
            $filter = $this->getFilterManager()->get(strtolower($condition['type']), [$this->getFilterManager()]);
            $filter->filter($qb, $metadata, $condition);
        }

        $andX->addMultiple($qb->getDqlParts()['where']->getParts());
        $queryBuilder->setParameters($qb->getParameters());

        $queryBuilder->$queryType($andX);
    }
}
