<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class Like extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        $queryType = $this->normalizeQueryType($option);
        $field = $this->normalizeField($option['field'], $queryBuilder, $metadata);

        $queryBuilder->$queryType($queryBuilder->expr()->like($field, $queryBuilder->expr()->literal($option['value'])));
    }
}
