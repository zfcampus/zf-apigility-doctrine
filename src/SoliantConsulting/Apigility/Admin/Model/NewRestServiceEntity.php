<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Apigility\Doctrine\Admin\Model;

use ZF\Rest\Exception\CreationException;
use ZF\Apigility\Admin\Model\NewRestServiceEntity as ZFNewRestServiceEntity;

class NewRestServiceEntity extends ZFNewRestServiceEntity
{
    protected $objectManager;

    protected $hydratorName;

    protected $hydrateByValue = false;

    public function exchangeArray(array $data)
    {
        parent::exchangeArray($data);
        foreach ($data as $key => $value) {
            $key = strtolower($key);
            $key = str_replace('_', '', $key);
            switch ($key) {
                case 'objectmanager':
                    $this->objectManager = $value;
                    break;
                case 'hydratorname':
                    $this->hydratorName = $value;
                    break;
                case 'hydratebyvalue':
                    $this->hydrateByValue = $value;
                    break;
                default:
                    break;
            }
        }
    }

    public function getArrayCopy()
    {
        $return = parent::getArrayCopy();
        $return['object_manager'] = $this->objectManager;
        $return['hydrator_name'] = $this->hydratorName;
        $return['hydrate_by_value'] = $this->hydrateByValue;
        $return['entity_identifier_name'] = $this->entityIdentifierName;
        return $return;
    }
}