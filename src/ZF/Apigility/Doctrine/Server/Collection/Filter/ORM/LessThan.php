<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\AbstractFilter;

class LessThan extends AbstractFilter
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

        $value = $this->typeCastField($metadata, $option['field'], $option['value']);

        $parameter = uniqid('a');
        $queryBuilder->$queryType($queryBuilder->expr()->lt('row.' . $option['field'], ":$parameter"));
        $queryBuilder->setParameter($parameter, $value);
    }
}
