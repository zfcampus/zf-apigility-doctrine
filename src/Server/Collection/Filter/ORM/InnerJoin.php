<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use Exception;

class InnerJoin extends AbstractFilter
{
    public function filter($queryBuilder, $metadata, $option)
    {
        if (!isset($option['field']) or !$option['field']) {
            // @codeCoverageIgnoreStart
            throw new Exception('Field must be specified for inner join');
        }
            // @codeCoverageIgnoreEnd

        if (!isset($option['alias']) or !$option['alias']) {
            // @codeCoverageIgnoreStart
            throw new Exception('Alias must be specified for inner join');
        }
            // @codeCoverageIgnoreEnd

        if (!isset($option['parentAlias']) or !$option['parentAlias']) {
            $option['parentAlias'] = 'row';
        }

        $queryBuilder->innerJoin($option['parentAlias'] . '.' . $option['field'], $option['alias']);
    }
}
