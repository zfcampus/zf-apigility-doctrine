<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ODM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\ODM\AbstractFilter;

class In extends AbstractFilter
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

        $queryValues = array();
        foreach ($option['values'] as $value) {
            $queryValues[] = $this->typeCastField($value, $option['field'], $value, $format);
        }

        $queryBuilder->$queryType($queryBuilder->expr()->field($option['field'])->in($queryValues));
    }
}
