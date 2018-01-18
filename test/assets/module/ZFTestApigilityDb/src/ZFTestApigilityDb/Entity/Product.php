<?php

namespace ZFTestApigilityDb\Entity;

class Product
{
    protected $id;

    protected $version;

    public function getId()
    {
        return $this->id;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }
}
