<?php

namespace SoliantConsulting\Apigility\Server\Hydrator;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager as ZendServiceManager;
use Zend\Stdlib\Hydrator\HydratorInterface;


class AbstractHydrator implements ServiceManagerAwareInterface, HydratorInterface
{
    protected $serviceManager;

    public function setServiceManager(ZendServiceManager $serviceManager) {
        die('set service manager');
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager() {
        return $this->serviceManager;
    }

#    public function __construct() {
#        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
#        die('construct hydrator');
#    }

    public function hydrate(array $data, $object) {
        die('hydrate');
    }

    public function extract($object) {
        die('extract');
    }
}
