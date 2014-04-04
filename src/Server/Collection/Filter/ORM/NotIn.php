<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class NotIn extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = $this->normalizeQueryType($option);
        $field = $this->normalizeField($option['field'], $queryBuilder, $metadata);

        $queryValues = array();
        foreach ($option['values'] as $value) {
            $queryValues[] = $this->normalizeValue($field, $value, $queryBuilder, $metadata, $this->normalizeFormat($option));
        }

        $parameter = uniqid('a');
        $queryBuilder->$queryType($queryBuilder->expr()->notIn($field, ":$parameter"));
        $queryBuilder->setParameter($parameter, $queryValues);
    }
}
