<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\AbstractFilter;

class NotIn extends AbstractFilter
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

        $queryValues = array();
        foreach ($option['values'] as $value) {
            $queryValues[] = $this->typeCastField($value, $option['field'], $value);
        }

        $parameter = uniqid('a');
        $queryBuilder->$queryType($queryBuilder->expr()->notIn('row.' . $option['field'], ":$parameter"));
        $queryBuilder->setParameter($parameter, $queryValues);
    }
}
