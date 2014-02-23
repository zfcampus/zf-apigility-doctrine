<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ODM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;

class LessThanOrEquals implements FilterInterface
{
    public function filter($queryBuilder, $option) {
        $queryType = 'addAnd';
        if (isset($option['where'])) {
            if ($option['where'] == 'and') {
                $queryType = 'addAnd';
            } elseif ($option['where'] == 'or') {
                $queryType = 'addOr';
            }
        }

        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->lte($option['value']));
    }
}
