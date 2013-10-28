<?php
/*
 * Create the client by giving the APIid and the Secret key
 *
 * DevNotes:  keys of 'id' and 'secret' @ http://www.stormpath.com/docs/rest/api#Base
 */

namespace SoliantConsulting\ApigilityClient;

use SoliantConsulting\ApigilityClient\Persistence\EntityManager;
use Zend\Http\Client as HttpClient;
use Zend\Json\Json;
use Zend\Cache\Storage\StorageInterface;
use Zend\Cache\StorageFactory;
use Zend\Config\Reader\Ini as ConfigReader;

class Client
{
    private $httpClient;
    private $cache;
    private $entityMap;
    private $baseUrl;

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($value)
    {
        $this->baseUrl = $value;
        return $this;
    }

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function setHttpClient(HttpClient $value)
    {
        $value->setOptions(array('sslverifypeer' => false));
        $this->httpClient = $value;
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

    public function setEntityMap(array $array)
    {
        $this->entityMap = $array;
        return $this;
    }

    public function getEntityMap()
    {
        return $this->entityMap;
    }

    public function getEntityManager()
    {
        $resourceManager = new EntityManager();
        $resourceManager->setHttpClient($this->getHttpClient());
        $resourceManager->setCache($this->getCache());
        $resourceManager->setEntityMap($this->getEntityMap());
        $resourceManager->setBaseUrl($this->getBaseUrl());

        return $resourceManager;
    }
}