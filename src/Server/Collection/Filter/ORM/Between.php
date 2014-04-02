<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

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

        $format = null;
        if (isset($option['format'])) {
            $format = $option['format'];
        }

        $from = $this->typeCastField($metadata, $option['field'], $option['from'], $format);
        $to = $this->typeCastField($metadata, $option['field'], $option['to'], $format);

        $fromParameter = uniqid('a');
        $toParameter = uniqid('a');

        $queryBuilder->$queryType($queryBuilder->expr()->between('row.' . $option['field'], ":$fromParameter", ":$toParameter"));
        $queryBuilder->setParameters(array($fromParameter => $from, $toParameter => $to));
    }
}
