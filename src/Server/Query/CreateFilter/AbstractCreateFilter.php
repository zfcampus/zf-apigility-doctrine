<?php

namespace ZF\Apigility\Doctrine\Server\Query\CreateFilter;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OAuth2\Server as OAuth2Server;
use OAuth2\Request as OAuth2Request;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;

/**
 * Class DefaultCreateFilter
 *
 * @package ZF\Apigility\Doctrine\Server\Query\CreateFilter
 */
abstract class AbstractCreateFilter implements ObjectManagerAwareInterface, QueryCreateFilterInterface
{
    /**
     * @param string $entityClass
     * @param array  $data
     *
     * @return array
     */
    abstract public function filter(ResourceEvent $event, $entityClass, $data);

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var OAuth2Server
     */
    protected $oAuth2Server;

    /**
     * Set the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Get the object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Get the OAuth2 server
     *
     * @return OAuth2Server
     */
    public function getOAuth2Server()
    {
        return $this->oAuth2Server;
    }

    /**
     * Set the OAuth2 server
     *
     * @param OAuth2Server
     */
    public function setOAuth2Server(OAuth2Server $server)
    {
        $this->oAuth2Server = $server;

        return $this;
    }

    /**
     * Validate an OAuth2 request
     *
     * @param scope
     * @return ApiProblem | bool
     */
    public function validateOAuth2($scope = null)
    {
        if (! $this->getOAuth2Server()->verifyResourceRequest(
            OAuth2Request::createFromGlobals(),
            $response = null,
            $scope = null
        )) {
            $error = $this->getOAuth2Server()->getResponse();
            $parameters = $error->getParameters();
            $detail = isset($parameters['error_description']) ?
                $parameters['error_description']: $error->getStatusText();

            return new ApiProblem($error->getStatusCode(), $detail);
        }

        return true;
    }
}
