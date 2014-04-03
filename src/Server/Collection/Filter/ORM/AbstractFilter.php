<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;
use ZF\Apigility\Doctrine\Server\Collection\Service\ORMFilterManager;

abstract class AbstractFilter implements FilterInterface
{
    abstract public function filter($queryBuilder, $metadata, $option);

    protected $filterManager;

    public function __construct($params)
    {
        $this->setFilterManager($params[0]);
    }

    public function setFilterManager(ORMFilterManager $filterManager)
    {
        $this->filterManager = $filterManager;
        return $this;
    }

    public function getFilterManager()
    {
        return $this->filterManager;
    }

    protected function typeCastField($metadata, $field, $value, $format, $doNotTypecastDatetime = false)
    {
        if (!isset($metadata['fieldMappings'][$field])) {
            return $value;
        }

        switch ($metadata['fieldMappings'][$field]['type']) {
            case 'string':
                settype($value, 'string');
                break;
            case 'integer':
            case 'smallint':
            #case 'bigint':  // Don't try to manipulate bigints?
                settype($value, 'integer');
                break;
            case 'boolean':
                settype($value, 'boolean');
                break;
            case 'decimal':
                settype($value, 'decimal');
                break;
            case 'date':
                if ($value and !$doNotTypecastDatetime) {
                    if (!$format) {
                        $format = 'Y-m-d';
                    }
                    $value = \DateTime::createFromFormat($format, $value);
                }
                break;
            case 'time':
                if ($value and !$doNotTypecastDatetime) {
                    if (!$format) {
                        $format = 'H:i:s';
                    }
                    $value = \DateTime::createFromFormat($format, $value);
                }
                break;
            case 'datetime':
                if ($value and !$doNotTypecastDatetime) {
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
