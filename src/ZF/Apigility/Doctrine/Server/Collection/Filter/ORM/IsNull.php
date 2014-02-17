<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;

class IsNull implements FilterInterface
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

        $queryBuilder->$queryType($queryBuilder->expr()->isNull('row.' . $option['field']));
    }
}
