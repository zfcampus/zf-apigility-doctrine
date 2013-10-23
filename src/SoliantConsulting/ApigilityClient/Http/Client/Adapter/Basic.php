<?php

namespace Stormpath\Http\Client\Adapter;

use Stormpath\Service\StormpathService as Stormpath;
use Zend\Http\Client\Adapter\Socket;
use Zend\Http\Request;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class Basic extends Socket
{
    /**
     * Send request to the remote server
     *
     * @param string        $method
     * @param \Zend\Uri\Uri $uri
     * @param string        $httpVer
     * @param array         $headers
     * @param string        $body
     * @return string Request as text
     */
    public function write($method, $uri, $httpVer = '1.1', $headers = array(), $body = '')
    {
        $headers['Authorization'] = 'Basic ' . base64_encode(Stormpath::getApiKey()->getId() . ':' . Stormpath::getApiKey()->getSecret());
        $headers['Content-Type'] = 'application/json;charset=UTF-8';
        return parent::write($method, $uri, $httpVer, $headers, $body);
    }
}