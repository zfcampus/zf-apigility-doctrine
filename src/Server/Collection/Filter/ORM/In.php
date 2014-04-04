<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class In extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = $this->normalizeQueryType($option);
        $field = $this->normalizeField($option['field'], $queryBuilder, $metadata);

        $queryValues = array();
        foreach ($option['values'] as $value) {
            $queryValues[] = $this->normalizeValue($field, $value, $queryBuilder, $metadata, $this->normalizeFormat($option), $doNotTypecastDatetime = true);
        }

        $parameter = uniqid('a');
        $queryBuilder->$queryType($queryBuilder->expr()->in($field, ":$parameter"));
        $queryBuilder->setParameter($parameter, $queryValues);
    }
}
