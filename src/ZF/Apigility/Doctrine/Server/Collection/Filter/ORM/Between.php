<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;

class Between implements FilterInterface
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
        $queryBuilder->$queryType($queryBuilder->expr()->between('row.' . $option['field'],':from'.$option['field'],':to'.$option['field']));
        $queryBuilder->setParameters(array('from'.$option['field'] => $option['from'], 'to'.$option['field']  => $option['to']));
    }
}
