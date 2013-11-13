<?php

namespace SoliantConsulting\Apigility\Client\Persistence;

use Zend\Http\Client;
use Zend\Http\Response;
use Doctrine\Common\Persistence\ObjectManager as CommonObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Cache\Storage\StorageInterface;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use ZF\ApiProblem\ApiProblem;
use SoliantConsulting\Apigility\Client\Collections\RelationCollection;

class ObjectManager implements CommonObjectManager
{
    private $entityManager;
    private $httpClient;
    private $entityMap;
    private $cache;
    private $insert;
    private $update;
    private $delete;

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function getEntityMap()
    {
        return $this->entityMap;
    }

    public function setEntityMap($value)
    {
        $this->entityMap = $value;
        return $this;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($value)
    {
        $this->baseUrl = $value;
        return $this;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(StorageInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function getHttpClient()
    {
        $this->httpClient->resetParameters();
        $headers = $this->httpClient->getRequest()->getHeaders();

        // Setting a single header. Will not overwrite any
        // previously-added headers of the same name.
        $headers->addHeaderLine('Accept', '*/*');

        return $this->httpClient;
    }

    public function setHttpClient(Client $client)
    {
        $this->httpClient = $client;
        return $this;
    }

    public function decodeSingleHalResponse($hal)
    {
        $return = [];

        if (isset($hal['_links'])) {
            $links = $hal['_links'];
            unset($hal['_links']);
        }

        if (isset($hal['_embedded'])) {
            $embedded = $hal['_embedded'];
            unset($hal['_embedded']);

            foreach ($embedded as $key => $value) {
                $className = null;

                if (isset($this->getEntityMap()['entities'][$key])) {
                    $className = $this->getEntityMap()['entities'][$key];
                }

                if (!$className) {
                    die('ObjectManager: No class name found for key ' . $key);
                }

                $id = str_replace($this->getBaseUrl() . '/' . $key . '/', '', $value['_links']['self']['href']);
                if (!$id) {
                    die('id not found for key ' . $key . ' url ' . $value['_links']['self']['href']);
                }

                $return[$key] = $this->find($className, $id);
            }
        }

        // use array merge
        foreach ($hal as $key => $value) {
            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * Initialize one to many relationships with collection classes
     */
    public function initRelations($entity)
    {
        if (!method_exists($entity, 'getRelationMap')) {
            return;
            die('Relation map does not exist for entity ' . get_class($entity));
        }
/*
        $metadataFactory = $this->getMetadataFactory();
        $entityMetadata = $metadataFactory->getMetadataFor(get_class($entity));
die('got metadata');
        foreach($entityMetadata->getAssociationMappings() as $map) {
            switch($map['type']) {
                case 4:
                    $data[$map['fieldName']] = $this->getObjectManager()->find($map['targetEntity'], $data[$map['fieldName']]);
                    break;
                default:
                    break;
            }
        }

        return $data;
*/

        foreach ($entity->getRelationMap() as $relation => $method) {
            if (!method_exists($entity, $method)) {
                die('Method ' . $method . ' does not exist on ' . get_class($entity));
            }

            if (!isset($this->getEntityMap()['collections'][$relation])) {
                die('Relation ' . $relation . ' not found on ' . get_class($entity));
            }

            $fieldName = $this->getEntityMap()['collections'][$relation];

            $relation = new RelationCollection($this, $fieldName);

            $relationFilterField = array_search(get_class($entity), $this->getEntityMap()['entities']);
            $relation->addFilter($relationFilterField, $entity->getId());

            $entity->$method($relation);
        }
    }

    public function find($className, $id)
    {
        $objectManager = $this;

        $factory     = new LazyLoadingValueHolderFactory();
        $initializer = function (& $wrappedObject, LazyLoadingInterface $proxy, $method, array $parameters, & $initializer) use ($objectManager, $className, $id)
        {
            $cachedJson = $objectManager->getCache()->getItem($className . $id, $success);

            if ($success) {
                $wrappedObject = new $className;
                $halData = json_decode($halJson, true);
                $wrappedObject->exchangeArray($objectManager->decodeSingleHalResponse($halData));
                $this->initRelations($wrappedObject);
            } else {

                if (!in_array($className, $objectManager->getEntityMap()['entities'])) {
                    throw new \Exception("$className is not mapped in ObjectManager entity map");
                }

                $client = $objectManager->getHttpClient();
                $client->setUri($objectManager->getBaseUrl() . '/' . array_search($className, $objectManager->getEntityMap()['entities']) . '/' . $id);
                $client->setMethod('GET');

                $response = $client->send();

                if ($response->isSuccess()) {
                    $wrappedObject = new $className;
                    $wrappedObject->exchangeArray($objectManager->decodeSingleHalResponse(json_decode($response->getBody(), true)));
                    $wrappedObject->setId($id);

                    $this->getCache()->setItem($className . $id, $response->getBody());
                } else {
                    // @codeCoverageIgnoreStart
                    $this->handleInvalidResponse($response);
                    // @codeCoverageIgnoreEnd
                }

                $this->initRelations($wrappedObject);

                // Initiation has started, disable lazy loading
                $initializer   = null;

                return true; // confirm that initialization occurred correctly
            }
        };

        $entity = $factory->createProxy($className, $initializer);

        return $entity;
    }

    /**
     * Handle all non 200 OK responses
     */
    public function handleInvalidResponse(Response $response)
    {
        $body = json_decode($response->getBody(), true);
        $problem = new ApiProblem($body['httpStatus'], $body['detail'], $body['problemType'], $body['title']);

        throw new \Exception('API Problem');

        print_r($problem);die();
    }

    function persist($object)
    {
        if (!$this->insert) {
            $this->insert = new ArrayCollection;
        }

        if (!$this->update) {
            $this->update = new ArrayCollection;
        }

        if ($object->getId()) {
            $this->update->add($object);
        } else {
            $this->insert->add($object);
        }
    }

    /**
     * Removes an object instance.
     *
     * A removed object will be removed from the database as a result of the flush operation.
     *
     * @param object $object The object instance to remove.
     */
    function remove($object)
    {
        if (!$this->delete) {
            $this->delete = new ArrayCollection;
        }

        if ($object->getId()) {
            // Objects with no id have not been created
            $this->delete->add($object);
        }
    }

    /**
     * Merges the state of a detached object into the persistence context
     * of this ObjectManager and returns the managed copy of the object.
     * The object passed to merge will not become associated/managed with this ObjectManager.
     *
     * @param object $object
     * @return object
     * @codeCoverageIgnore
     */
    function merge($object)
    {
        throw new \Exception('Merge not implemented');
    }

    /**
     * Clears the ObjectManager. All objects that are currently managed
     * by this ObjectManager become detached.
     *
     * @param string $objectName if given, only objects of this type will get detached
     */
    function clear($objectName = null)
    {
        $this->insert = new ArrayCollection;
        $this->update = new ArrayCollection;
        $this->delete = new ArrayCollection;
    }

    /**
     * Detaches an object from the ObjectManager, causing a managed object to
     * become detached. Unflushed changes made to the object if any
     * (including removal of the object), will not be synchronized to the database.
     * Objects which previously referenced the detached object will continue to
     * reference it.
     *
     * @param object $object The object to detach.
     */
    function detach($object)
    {
        if ($this->insert) {
            $this->insert->removeElement($object);
        }

        if ($this->update) {
            $this->update->removeElement($object);
        }

        if ($this->delete) {
            $this->delete->removeElement($object);
        }
    }

    /**
     * Refreshes the persistent state of an object from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $object The object to refresh.
     */
    function refresh($resource)
    {
        $this->getCache()->removeItem(get_class($resource) . $resource->getId());
        $resource->_load($resource->getId());
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     */
    function flush()
    {
        if ($this->insert) {
            foreach ($this->insert as $resource) {
                switch(get_class($resource)) {
                    case 'SoliantConsulting\Apigility\Client\Resource\Account':
                        if ($resource->getDirectory()) {
                            $resource->_setUrl($this->getBaseUrl() . '/directories/' . $resource->getDirectory()->getId() . '/accounts');
                        } else {
                            $resource->_setUrl($this->getBaseUrl() . '/applications/' . $resource->getApplication()->getId() . '/accounts');
                        }
                        break;
                    case 'SoliantConsulting\Apigility\Client\Resource\Group':
                        $resource->_setUrl($this->getBaseUrl() . '/directories/' . $resource->getDirectory()->getId() . '/groups');
                        break;
                    case 'SoliantConsulting\Apigility\Client\Resource\LoginAttempt':
                        $resource->_setUrl($this->getBaseUrl() . '/applications/' . $resource->getApplication()->getId() . '/loginAttempts');
                        break;
                    case 'SoliantConsulting\Apigility\Client\Resource\PasswordResetToken':
                        $resource->_setUrl($this->getBaseUrl() . '/applications/' . $resource->getApplication()->getId() . '/passwordResetTokens');
                        break;
                    case 'SoliantConsulting\Apigility\Client\Resource\EmailVerificationToken':
                        // @codeCoverageIgnoreStart
                        $resource->_setUrl($this->getBaseUrl() . $resource->_getUrl() . '/' . $resource->getToken());
                        break;
                        // @codeCoverageIgnoreEnd
                    default:
                        break;
                }
                // Create a resource
                $client = $this->getHttpClient();
                $client->setUri($resource->_getUrl());
                $client->setMethod('POST');

                $client->setRawBody(json_encode($resource->getArrayCopy()));

                if ($resource->getAdditionalQueryParameters()) {
                    foreach ($resource->getAdditionalQueryParameters() as $key => $value) {
                        $client->getRequest()->getQuery()->set($key, $value);
                    }
                    $resource->resetAdditionalQueryParameters();
                }

                $response = $client->send();

                if ($response->isSuccess()) {
                    $resource->setResourceManager($this);
                    $newProperties = json_decode($response->getBody(), true);

                    $resource->exchangeArray($newProperties);
                    $this->getCache()->setItem(get_class($resource) . $resource->getId(), $response->getBody());
                } else {
                    // @codeCoverageIgnoreStart
                    $this->handleInvalidResponse($response);
                    // @codeCoverageIgnoreEnd
                }

                $this->insert->removeElement($resource);
            }
        }

        if ($this->update) {
            foreach ($this->update as $resource) {
                $resource->_load();

                // Delete a resource
                $client = $this->getHttpClient();
                $client->setUri($resource->getHref());
                $client->setMethod('POST');

                $client->setRawBody(json_encode($resource->getArrayCopy()));

                // Remove code coverage ignore when/if features are added which affect a update
                // @codeCoverageIgnoreStart
                if ($resource->getAdditionalQueryParameters()) {
                    foreach ($resource->getAdditionalQueryParameters() as $key => $value) {
                        $client->getRequest()->getQuery()->set($key, $value);
                    }
                    $resource->resetAdditionalQueryParameters();
                }
                // @codeCoverageIgnoreEnd

                $response = $client->send();

                if ($response->isSuccess()) {
                    $resource->exchangeArray(json_decode($response->getBody(), true));
                    $this->getCache()->setItem(get_class($resource) . $resource->getId(), $response->getBody());
                } else {
                    // @codeCoverageIgnoreStart
                    $this->handleInvalidResponse($response);
                    // @codeCoverageIgnoreEnd
                }

                $this->update->removeElement($resource);
            }
        }

        if ($this->delete) {
            foreach ($this->delete as $resource) {
                $resource->_load();

                // Delete a resource
                $client = $this->getHttpClient();
                $client->setUri($resource->getHref());
                $client->setMethod('DELETE');

                // Remove code coverage ignore when/if features are added which affect a delete
                // @codeCoverageIgnoreStart
                if ($resource->getAdditionalQueryParameters()) {
                    foreach ($resource->getAdditionalQueryParameters() as $key => $value) {
                        $client->getRequest()->getQuery()->set($key, $value);
                    }
                    $resource->resetAdditionalQueryParameters();
                }
                // @codeCoverageIgnoreEnd

                $response = $client->send();

                if ($response->isSuccess()) {
                } else {
                    // @codeCoverageIgnoreStart
                    $this->handleInvalidResponse($response);
                    // @codeCoverageIgnoreEnd
                }

                $this->getCache()->removeItem(get_class($resource) . $resource->getId());
                $this->delete->removeElement($resource);
            }
        }
    }


    /**
     * Gets the repository for a class.
     *
     * @param string $className
     * @return \Doctrine\Common\Persistence\ObjectRepository
     * @codeCoverageIgnore
     */
    function getRepository($className)
    {

    }

    /**
     * Returns the ClassMetadata descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)).
     *
     * @param string $className
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     * @codeCoverageIgnore
     */
    function getClassMetadata($className)
    {

    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     * @codeCoverageIgnore
     */
    function getMetadataFactory()
    {

    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * This method is a no-op for other objects.
     *
     * @param object $obj
     * @codeCoverageIgnore
     */
    function initializeObject($obj)
    {

    }

    /**
     * Check if the object is part of the current UnitOfWork and therefore
     * managed.
     *
     * @param object $object
     * @return bool
     * @codeCoverageIgnore
     */
    function contains($object)
    {

    }
}