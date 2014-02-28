<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;

abstract class AbstractFilter implements FilterInterface
{
    abstract function filter($queryBuilder, $metadata, $option);

    protected function typeCastField($metadata, $field, $value, $format = null)
    {
        if (!isset($metadata['fieldMappings'][$field])) {
            throw new \Exception("Invalid field '$field' for entity metadata " . $metadata['name'] . ' in filter ' . get_class($this));
        }

        switch ($metadata['fieldMappings'][$field]['type']) {
            case 'string':
                settype($value, 'string');
                break;
            case 'integer':
            case 'smallint':
            #case 'bigint':
                settype($value, 'integer');
                break;
            case 'boolean':
                settype($value, 'boolean');
                break;
            case 'decimal':
                settype($value, 'decimal');
                break;
            case 'date':
                if ($value) {
                    if (!$format) {
                        $format = 'Y-m-d';
                    }
                    $value = \DateTime::createFromFormat($format, $value);
                }
                break;
            case 'time':
                if ($value) {
                    if (!$format) {
                        $format = 'H:i:s';
                    }
                    $value = \DateTime::createFromFormat($format, $value);
                }
                break;
            case 'datetime':
                if ($value) {
                    if (!$format) {
                        $format = 'Y-m-d H:i:s';
                    }
                    $value = \DateTime::createFromFormat($format, $value);
                }
                break;
            case 'float':
                settype($value, 'float');
                break;
            default:
                break;
        }

        return $value;
    }
}