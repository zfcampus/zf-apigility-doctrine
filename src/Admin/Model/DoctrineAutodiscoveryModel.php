<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use ZF\Apigility\Admin\Model\AbstractAutodiscoveryModel;

class DoctrineAutodiscoveryModel extends AbstractAutodiscoveryModel
{
    /**
     * Fetch fields for an adapter
     *
     * @param string $module
     * @param int $version
     * @param string $adapterName
     * @return array
     */
    public function fetchFields($module, $version, $adapterName)
    {
        $entities = [];

        /**
         * @var ObjectManager $em
         */
        $em = $this->getServiceLocator()->get($adapterName);

        /**
         * @var AbstractClassMetadataFactory $cmf
         */
        $cmf = $em->getMetadataFactory();

        /**
         * @var ClassMetadata $classMetadata
         */
        foreach ($cmf->getAllMetadata() as $classMetadata) {
            $service = substr($classMetadata->getName(), strrpos($classMetadata->getName(), '\\') + 1);
            if ($this->moduleHasService($module, $version, $service)) {
                continue;
            }
            $entity = [
                'entity_class' => $classMetadata->getName(),
                'service_name' => $service,
                'fields'       => [],
            ];

            foreach ($classMetadata->fieldMappings as $mapping) {
                if ($classMetadata->isIdentifier($mapping['fieldName'])) {
                    continue;
                }
                $field = [
                    'name'       => $mapping['fieldName'],
                    'required'   => ! isset($mapping['nullable']) || $mapping['nullable'] !== true,
                    'filters'    => [],
                    'validators' => [],
                ];
                switch ($mapping['type']) {
                    case 'string':
                        $field['filters'] = $this->filters['text'];
                        if (! empty($mapping['length'])) {
                            $validator = $this->validators['text'];
                            $validator['options']['max'] = $mapping['length'];
                            $field['validators'][] = $validator;
                        }
                        break;
                    case 'integer':
                    case 'int':
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
