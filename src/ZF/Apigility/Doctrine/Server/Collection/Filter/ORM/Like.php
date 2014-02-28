<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\AbstractFilter;

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

        $queryBuilder->$queryType($queryBuilder->expr()->like('row.' . $option['field'], $queryBuilder->expr()->literal($option['value'])));
    }
}
