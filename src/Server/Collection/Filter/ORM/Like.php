<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class Like extends AbstractFilter
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

        if (!isset($option['alias'])) {
            $option['alias'] = 'row';
        }

        $queryBuilder->$queryType($queryBuilder->expr()->like($option['alias'] . '.' . $option['field'], $queryBuilder->expr()->literal($option['value'])));
    }
}
