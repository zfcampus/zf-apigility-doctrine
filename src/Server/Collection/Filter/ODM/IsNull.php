<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ODM;

class IsNull extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = 'addAnd';
        if (isset($option['where'])) {
            if ($option['where'] == 'and') {
                $queryType = 'addAnd';
            } elseif ($option['where'] == 'or') {
                $queryType = 'addOr';
            }
        }

        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->exists(false));
    }
}
