<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

class InnerJoin extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        if (!isset($option['field']) or !$option['field']) {
            throw new \Exception('Field must be specified for inner join');
        }

        if (!isset($option['alias']) or !$option['alias']) {
            throw new \Exception('Alias must be specified for inner join');
        }

        if (!isset($option['parentAlias']) or !$option['parentAlias']) {
            $option['parentAlias'] = 'row';
        }

        $queryBuilder->innerJoin($option['parentAlias'] . '.' . $option['field'], $option['alias']);
    }
}
