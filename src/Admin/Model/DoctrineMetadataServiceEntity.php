<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Zend\Stdlib\ArraySerializableInterface;

class DoctrineMetadataServiceEntity implements ArraySerializableInterface
{
    protected $name;
    protected $namespace;
    protected $rootEntityName;
    protected $customGeneratorDefinition;
    protected $customRepositoryClassName;
    protected $isMappedSuperclass;
    protected $parentClasses;
    protected $subClasses;
    protected $namedQueries;
    protected $namedNativeQueries;
    protected $sqlResultSetMappings;
    protected $identifier;
    protected $inheritanceType;
    protected $generatorType;
    protected $fieldMappings;
    protected $fieldNames;
    protected $columnNames;
    protected $discriminatorValue;
    protected $discriminatorMap;
    protected $discriminatorColumn;
    protected $table;
    protected $lifecycleCallbacks;
    protected $entityListeners;
    protected $associationMappings;
    protected $isIdentifierComposite;
    protected $containsForeignIdentifier;
    protected $idGenerator;
    protected $sequenceGeneratorDefinition;
    protected $tableGeneratorDefinition;
    protected $changeTrackingPolicy;
    protected $isVersioned;
    protected $versionField;
    protected $reflClass;
    protected $isReadOnly;
    protected $namingStrategy;
    protected $reflFields;
    protected $ClassMetadataInfo_prototype;

    public function exchangeArray(array $data)
    {
        foreach ($data as $field => $value) {
            switch ($field) {
                case 'name':
                    $this->name = $value;
                    break;
                case 'namespace':
                    $this->namespace = $value;
                    break;
                case 'rootEntityName':
                    $this->rootEntityName = $value;
                    break;
                case 'customGeneratorDefinition':
                    $this->customGeneratorDefinition = $value;
                    break;
                case 'customRepositoryClassName':
                    $this->customRepositoryClassName = $value;
                    break;
                case 'isMappedSuperclass':
                    $this->isMappedSuperclass = $value;
                    break;
                case 'parentClasses':
                    $this->parentClasses = $value;
                    break;
                case 'subClasses':
                    $this->subClasses = $value;
                    break;
                case 'namedQueries':
                    $this->namedQueries = $value;
                    break;
                case 'namedNativeQueries':
                    $this->namedNativeQueries = $value;
                    break;
                case 'sqlResultSetMappings':
                    $this->sqlResultSetMappings = $value;
                    break;
                case 'identifier':
                    $this->identifier = $value;
                    break;
                case 'inheritanceType':
                    $this->inheritanceType = $value;
                    break;
                case 'generatorType':
                    $this->generatorType = $value;
                    break;
                case 'fieldMappings':
                    $this->fieldMappings = $value;
                    break;
                case 'fieldNames':
                    $this->fieldNames = $value;
                    break;
                case 'columnNames':
                    $this->columnNames = $value;
                    break;
                case 'discriminatorValue':
                    $this->discriminatorValue = $value;
                    break;
                case 'discriminatorMap':
                    $this->discriminatorMap = $value;
                    break;
                case 'discriminatorColumn':
                    $this->discriminatorColumn = $value;
                    break;
                case 'table':
                    $this->table = $value;
                    break;
                case 'lifecycleCallbacks':
                    $this->lifecycleCallbacks = $value;
                    break;
                case 'entityListeners':
                    $this->entityListeners = $value;
                    break;
                case 'associationMappings':
                    $this->associationMappings = $value;
                    break;
                case 'isIdentifierComposite':
                    $this->isIdentifierComposite = $value;
                    break;
                case 'containsForeignIdentifier':
                    $this->containsForeignIdentifier = $value;
                    break;
                case 'idGenerator':
                    $this->idGenerator = $value;
                    break;
                case 'sequenceGeneratorDefinition':
                    $this->sequenceGeneratorDefinition = $value;
                    break;
                case 'tableGeneratorDefinition':
                    $this->tableGeneratorDefinition = $value;
                    break;
                case 'changeTrackingPolicy':
                    $this->changeTrackingPolicy = $value;
                    break;
                case 'isVersioned':
                    $this->isVersioned = $value;
                    break;
                case 'versionField':
                    $this->versionField = $value;
                    break;
                case 'reflClass':
                    $this->reflClass = $value;
                    break;
                case 'isReadOnly':
                    $this->isReadOnly = $value;
                    break;
                case '*namingStrategy':
                    $this->namingStrategy = $value;
                    break;
                case 'reflFields':
                    $this->reflFields = $value;
                    break;
                case 'Doctrine\ORM\Mapping\ClassMetadataInfo_prototype':
                    $this->ClassMetadataInfo_prototype = $value;
                    break;
                default:
                    break;
            }
        }

        return $this;
    }

    public function getArrayCopy()
    {
        return [
            'name' => $this->name,
            'namespace' => $this->namespace,
            'rootEntityName' => $this->rootEntityName,
            'customGeneratorDefinition' => $this->customGeneratorDefinition,
            'customRepositoryClassName' => $this->customRepositoryClassName,
            'isMappedSuperclass' => $this->isMappedSuperclass,
            'parentClasses' => $this->parentClasses,
            'subClasses' => $this->subClasses,
            'namedQueries' => $this->namedQueries,
            'namedNativeQueries' => $this->namedNativeQueries,
            'sqlResultSetMappings' => $this->sqlResultSetMappings,
            'identifier' => $this->identifier,
            'inheritanceType' => $this->inheritanceType,
            'generatorType' => $this->generatorType,
            'fieldMappings' => $this->fieldMappings,
            'fieldNames' => $this->fieldNames,
            'columnNames' => $this->columnNames,
            'discriminatorValue' => $this->discriminatorValue,
            'discriminatorMap' => $this->discriminatorMap,
            'discriminatorColumn' => $this->discriminatorColumn,
            'table' => $this->table,
            'lifecycleCallbacks' => $this->lifecycleCallbacks,
            'entityListeners' => $this->entityListeners,
            'associationMappings' => $this->associationMappings,
            'isIdentifierComposite' => $this->isIdentifierComposite,
            'containsForeignIdentifier' => $this->containsForeignIdentifier,
            'idGenerator' => $this->idGenerator,
            'sequenceGeneratorDefinition' => $this->sequenceGeneratorDefinition,
            'tableGeneratorDefinition' => $this->tableGeneratorDefinition,
            'changeTrackingPolicy' => $this->changeTrackingPolicy,
            'isVersioned' => $this->isVersioned,
            'versionField' => $this->versionField,
            'reflClass' => $this->reflClass,
            'isReadOnly' => $this->isReadOnly,
            '*namingStrategy' => $this->namingStrategy,
            'reflFields' => $this->reflFields,
            'Doctrine\ORM\Mapping\ClassMetadataInfo_prototype' => $this->ClassMetadataInfo_prototype,
        ];
    }
}
