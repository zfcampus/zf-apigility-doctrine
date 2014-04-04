<?php

namespace ZF\Apigility\Doctrine\Server\Collection\Filter\ORM;

use ZF\Apigility\Doctrine\Server\Collection\Filter\FilterInterface;
use ZF\Apigility\Doctrine\Server\Collection\Service\ORMFilterManager;
use Doctrine\ORM\Query\Expr\Math;

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

    public function normalizeQueryType($option)
    {
        if (isset($option['where'])) {
            if ($option['where'] == 'and') {
                $queryType = 'andWhere';
            } elseif ($option['where'] == 'or') {
                $queryType = 'orWhere';
            }
        }

        if (!isset($queryType)) {
            $queryType = 'andWhere';
        }

        return $queryType;
    }

    public function normalizeField($field, $queryBuilder, $metadata)
    {
        if (is_array($field) and isset($field['type']) and strtolower($field['type']) == 'math') {
            $expression = $field['expr'];
            switch($expression) {
                case 'prod':
                case 'diff':
                case 'sum':
                case 'quot':
                    return $queryBuilder->expr()->$expression('row.' . $field['field'], $field['value']);
            }
        }

        return 'row.' . $field;
    }

    public function normalizeFormat($field)
    {
        $format = null;
        if (isset($option['format'])) {
            $format = $option['format'];
        }

        return $format;
    }

    public function normalizeValue($field, $value, $queryBuilder, $metadata, $format, $doNotTypecastDatetime = false)
    {
    /*
        if (is_array($value) and isset($value['type']) and strtolower($value['type']) == 'math') {
            $expression = $value['expr'];
            switch($expression) {
                case 'prod':
                case 'diff':
                case 'sum':
                case 'quot':
                    return $queryBuilder->expr()->$expression('row.' . $value['field'], $value['value']);
                default:
                    return;
            }
        }
    */

        if ($field instanceof Math) {
            if ((int)$value == $value) {
                settype($value, 'integer');
            } elseif ((float)$value == $value) {
                settype($value, 'float');
            }

            return $value;  // Cannot typecast a value when it's field is a math expr
        } else {
            return $this->typeCastField($metadata, $field, $value, $format, $doNotTypecastDatetime);
        }
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
