<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter;

interface FilterInterface
{
    public function filter($queryBuilder, $metadata, $option);
}
