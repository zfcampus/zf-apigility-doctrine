<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\AbstractFilter;

class Between extends AbstractFilter
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

        if (!isset($option['format'])) {
            $option['format'] = null;
        }

        $from = $this->typeCastField($metadata, $option['field'], $option['from'], $option['format']);
        $to = $this->typeCastField($metadata, $option['field'], $option['to'], $option['format']);

        $fromParameter = uniqid('a');
        $toParameter = uniqid('a');

        $queryBuilder->$queryType($queryBuilder->expr()->between('row.' . $option['field'], ":$fromParameter", ":$toParameter"));
        $queryBuilder->setParameters(array($fromParameter => $from, $toParameter => $to));
    }
}
