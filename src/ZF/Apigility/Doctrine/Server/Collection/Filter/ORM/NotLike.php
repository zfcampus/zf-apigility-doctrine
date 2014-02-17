<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;

class NotLike implements FilterInterface
{
    public function filter($queryBuilder, $option) {
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

        $queryBuilder->$queryType($queryBuilder->expr()->notLike('row.' . $option['field'], $queryBuilder->expr()->literal($option['value'])));
    }
}
