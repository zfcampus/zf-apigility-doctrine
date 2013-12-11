<?php

namespace SoliantConsulting\Apigility\Server\Hydrator;

use Zend\Stdlib\Hydrator\HydratorInterface;

/**
 * Class DoctrineHydrator
 *
 * @package SoliantConsulting\Apigility\Server\Hydrator
 */
class DoctrineHydrator
    implements HydratorInterface
{

    /**
     * @var HydratorInterface
     */
    protected $extractService;

    /**
     * @var HydratorInterface
     */
    protected $hydrateService;

    /**
     * @param \Zend\Stdlib\Hydrator\HydratorInterface $extractService
     */
    public function setExtractService($extractService)
    {
        $this->extractService = $extractService;
    }

    /**
     * @return \Zend\Stdlib\Hydrator\HydratorInterface
     */
    public function getExtractService()
    {
        return $this->extractService;
    }

    /**
     * @param \Zend\Stdlib\Hydrator\HydratorInterface $hydrateService
     */
    public function setHydrateService($hydrateService)
    {
        $this->hydrateService = $hydrateService;
    }

    /**
     * @return \Zend\Stdlib\Hydrator\HydratorInterface
     */
    public function getHydrateService()
    {
        return $this->hydrateService;
    }

    /**
     * Extract values from an object
     *
     * @param  object $object
     *
     * @return array
     */
    public function extract($object)
    {
        return $this->extractService->extract($object);
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param  array  $data
     * @param  object $object
     *
     * @return object
     */
     public function hydrate(array $data, $object)
     {
        // Zend hydrator:
        if ($this->hydrateService instanceof HydratorInterface) {
            $this->hydrateService->hydrate($data, $object);
        }

        // Doctrine hydrator: (parameters switched)
        return $this->hydrateService->hydrate($object, $data);
     }
}