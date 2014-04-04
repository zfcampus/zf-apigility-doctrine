<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class Between extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = $this->normalizeQueryType($option);
        $field = $this->normalizeField($option['field'], $queryBuilder, $metadata);
        $from = $this->normalizeValue($field, $option['from'], $queryBuilder, $metadata, $this->normalizeFormat($option));
        $to = $this->normalizeValue($field, $option['to'], $queryBuilder, $metadata, $this->normalizeFormat($option));

        $fromParameter = uniqid('a');
        $toParameter = uniqid('a');

        $queryBuilder->$queryType($queryBuilder->expr()->between($field, ":$fromParameter", ":$toParameter"));
        $queryBuilder->setParameters(array($fromParameter => $from, $toParameter => $to));
    }
}
