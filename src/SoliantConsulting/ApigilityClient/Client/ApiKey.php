<?php

namespace Stormpath\Client;

class ApiKey 
{
    private $id;
    private $secret;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function setSecret($value)
    {
        $this->secret = $value;
    }
}