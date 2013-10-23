<?php
/*
 * Create the client by giving the APIid and the Secret key
 *
 * DevNotes:  keys of 'id' and 'secret' @ http://www.stormpath.com/docs/rest/api#Base
 */

namespace Stormpath\Service;

use Stormpath\Persistence\ResourceManager;
use Stormpath\Http\Client\Adapter\Digest;
use Stormpath\Http\Client\Adapter\Basic;
use Zend\Http\Client as HttpClient;
use Zend\Json\Json;
use Stormpath\Client\ApiKey;
use Zend\Cache\Storage\StorageInterface;
use Zend\Cache\StorageFactory;
use Zend\Config\Reader\Ini as ConfigReader;

class StormpathService
{
    private static $apiKey;
    private static $httpClient;
    private static $cache;
    private static $baseUrl = 'https://api.stormpath.com/v1';

    public static function getBaseUrl()
    {
        return self::$baseUrl;
    }

    public static function getHttpClient()
    {
        return self::$httpClient;
    }

    public static function setHttpClient(HttpClient $value)
    {
        $value->setOptions(array('sslverifypeer' => false));
        self::$httpClient = $value;
    }

    public static function getApiKey()
    {
        return self::$apiKey;
    }

    public static function setApiKey(ApiKey $apiKey)
    {
        self::$apiKey = $apiKey;
    }

    public static function getCache()
    {
        return self::$cache;
    }

    public static function setCache(StorageInterface $cache)
    {
        self::$cache = $cache;
    }

    public static function configure($id, $secret)
    {
        // Set default API key; overwriteable after configuration
        $apiKey = new ApiKey;
        $apiKey->setId($id);
        $apiKey->setSecret($secret);
        self::setApiKey($apiKey);

        // Set default HTTP client; overwriteable after configuration
        $client = new HttpClient(null, array('keepalive' => true));
        $adapter = new Basic();
        $client->setAdapter($adapter);
        self::setHttpClient($client);

        // Set default cache adapter; overwriteable after configuration
        self::setCache(StorageFactory::adapterFactory('memory'));
    }

    public static function getResourceManager()
    {
        $resourceManager = new ResourceManager();
        $resourceManager->setHttpClient(self::getHttpClient());
        $resourceManager->setCache(self::getCache());

        return $resourceManager;
    }
}
