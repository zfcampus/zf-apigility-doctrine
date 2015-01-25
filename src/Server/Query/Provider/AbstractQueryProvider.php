<?php

namespace ZF\Apigility\Doctrine\Server\Query\Provider;

use ZF\Apigility\Doctrine\Server\Paginator\Adapter\DoctrineOrmAdapter;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Zend\Paginator\Adapter\AdapterInterface;
use OAuth2\Request as OAuth2Request;
use OAuth2\Server as OAuth2Server;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;

/**
 * Class FetchAllOrm
 *
 * @package ZF\Apigility\Doctrine\Server\Query\Provider
 */
abstract class AbstractQueryProvider implements ObjectManagerAwareInterface, QueryProviderInterface
{
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
     * @param string $entityClass
     * @param array  $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    abstract public function createQuery(ResourceEvent $event, $entityClass, $parameters);

    /**
     * @param   $queryBuilder
     *
     * @return AdapterInterface
     */
    public function getPaginatedQuery($queryBuilder)
    {
        $adapter = new DoctrineOrmAdapter($queryBuilder->getQuery(), false);

        return $adapter;
    }

    /**
     * @param   $entityClass
     *
     * @return int
     */
    public function getCollectionTotal($entityClass)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $cmf = $this->getObjectManager()->getMetadataFactory();
        $entityMetaData = $cmf->getMetadataFor($entityClass);

        $identifier = $entityMetaData->getIdentifier();
        $queryBuilder->select('count(row.' . $identifier[0] . ')')
            ->from($entityClass, 'row');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
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
            $detail = isset($parameters['error_description'])
                ? $parameters['error_description']: $error->getStatusText();

            return new ApiProblem($error->getStatusCode(), $detail);
        }

        return true;
    }
}
