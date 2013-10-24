<?php
/*
 * Create the client by giving the APIid and the Secret key
 *
 * DevNotes:  keys of 'id' and 'secret' @ http://www.stormpath.com/docs/rest/api#Base
 */

namespace SoliantConsulting\ApigilityClient;

use SoliantConsulting\ApigilityClient\Persistence\ResourceManager;
# use SoliantConsulting\ApigilityClient\Http\Client\Adapter\Digest;
use SoliantConsulting\ApigilityClient\Http\Client\Adapter\Basic;
use Zend\Http\Client as HttpClient;
use Zend\Json\Json;
use Zend\Cache\Storage\StorageInterface;
use Zend\Cache\StorageFactory;
use Zend\Config\Reader\Ini as ConfigReader;

class Client
{
    private $httpClient;
    private $cache;
    private $resourceMap;
    private $baseUrl = 'https://github.com/soliantconsulting';

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

    public function setResourceMap(array $array)
    {
        $this->resourceMap = $array;
        return $this;
    }

    public function getResourceMap()
    {
        return $this->resourceMap;
    }

    public static function getResourceManager()
    {
        $resourceManager = new ResourceManager();
        $resourceManager->setHttpClient(self::getHttpClient());
        $resourceManager->setCache(self::getCache());
        $resourceManager->setEntityMap($this->getResourceMap()['entities']);

        return $resourceManager;
    }
}