<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use ZF\Apigility\Admin\Model\AbstractAutodiscoveryModel;

class DoctrineAutodiscoveryModel extends AbstractAutodiscoveryModel
{
    /**
     * Fetch fields for an adapter
     *
     * @param string $module
     * @param int    $version
     * @param string $adapter_name
     * @return array
     */
    public function fetchFields($module, $version, $adapter_name)
    {
        $entities = array();

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getServiceLocator()->get($adapter_name);

        /** @var \Doctrine\ORM\Mapping\ClassMetadataFactory $cmf */
        $cmf = $em->getMetadataFactory();

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $classMetadata */
        foreach ($cmf->getAllMetadata() as $classMetadata) {
            $service = substr($classMetadata->getName(), strrpos($classMetadata->getName(), '\\') + 1);
            if ($this->moduleHasService($module, $version, $service)) {
                continue;
            }
            $entity = array(
                'entity_class' => $classMetadata->getName(),
                'service_name' => $service,
                'fields' => array(),
            );

            foreach ($classMetadata->fieldMappings as $mapping) {
                if ($classMetadata->isIdentifier($mapping['fieldName'])) {
                    continue;
                }
                $field = array(
                    'name' => $mapping['fieldName'],
                    'required' => !$mapping['nullable'],
                    'filters' => array(),
                    'validators' => array(),
                );
                switch ($mapping['type']) {
                    case 'string':
                        $field['filters'] = $this->filters['text'];
                        if ($mapping['length'] != '') {
                            $validator = $this->validators['text'];
                            $validator['options']['max'] = $mapping['length'];
                            $field['validators'][] = $validator;
                        }
                        break;
                    case 'integer':
                        $field['filters'] = $this->filters['integer'];
                        break;
                    default:
                        continue;
                        break;
                }
                $entity['fields'][] = $field;
            }

            $entities[] = $entity;
        }

        return $entities;
    }
}