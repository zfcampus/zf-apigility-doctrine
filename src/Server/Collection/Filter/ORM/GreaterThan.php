<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class GreaterThan extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = $this->normalizeQueryType($option);
        $field = $this->normalizeField($option['field'], $queryBuilder, $metadata);
        $value = $this->normalizeValue($field, $option['value'], $queryBuilder, $metadata, $this->normalizeFormat($option));

        $parameter = uniqid('a');
        $queryBuilder->$queryType($queryBuilder->expr()->gt($field, ":$parameter"));
        $queryBuilder->setParameter($parameter, $value);
    }
}
