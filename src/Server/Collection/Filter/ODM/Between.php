<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ODM;

class Between extends AbstractFilter
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

        $format = null;
        if (isset($option['format'])) {
            $format = $option['format'];
        }

        $from = $this->typeCastField($metadata, $option['field'], $option['from'], $format);
        $to = $this->typeCastField($metadata, $option['field'], $option['to'], $format);

        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->range($from, $to));
    }
}
