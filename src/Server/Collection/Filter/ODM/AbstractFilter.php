<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ODM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;

abstract class AbstractFilter implements FilterInterface
{
    abstract public function filter($queryBuilder, $metadata, $option);

    protected function typeCastField($metadata, $field, $value, $format = null, $doNotTypecastDatetime = false)
    {
        if (!isset($metadata['fieldMappings'][$field])) {
            return $value;
        }

        switch ($metadata['fieldMappings'][$field]['type']) {
            case 'int':
                settype($value, 'integer');
                break;
            case 'boolean':
                settype($value, 'boolean');
                break;
            case 'float':
                settype($value, 'float');
                break;
            case 'string':
                settype($value, 'string');
                break;
            case 'bin_data_custom':
                break;
            case 'bin_data_func':
                break;
            case 'bin_data_md5':
                break;
            case 'bin_data':
                break;
            case 'bin_data_uuid':
                break;
            case 'collection':
                break;
            case 'custom_id':
                break;
            case 'date':
                if ($value and !$doNotTypecastDatetime) {
                    if (!$format) {
                        $format = 'Y-m-d H:i:s';
                    }
                    $value = \DateTime::createFromFormat($format, $value);
                }
                break;
            case 'file':
                break;
            case 'hash':
                break;
            case 'id':
                break;
            case 'increment':
                break;
            case 'key':
                break;
            case 'object_id':
                break;
            case 'raw_type':
                break;
            case 'timestamp':
                break;
            default:
                break;
        }

        return $value;
    }
}
