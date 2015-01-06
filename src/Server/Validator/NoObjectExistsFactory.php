<?php

namespace ZF\Apigility\Doctrine\Server\Validator;

use DoctrineModule\Validator\NoObjectExists;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\MutableCreationOptionsInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;

class NoObjectExistsFactory implements FactoryInterface, MutableCreationOptionsInterface
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $validators
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $validators)
    {
        if (isset($this->options['entity_class'])) {
            return new NoObjectExists(ArrayUtils::merge(
                $this->options,
                array('object_repository' => $validators->getServiceLocator()->get('Doctrine\ORM\EntityManager')->getRepository($this->options['entity_class']))
            ));
        }
        return new NoObjectExists($this->options);
    }

    /**
     * Set creation options
     *
     * @param  array $options
     * @return void
     */
    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }

}