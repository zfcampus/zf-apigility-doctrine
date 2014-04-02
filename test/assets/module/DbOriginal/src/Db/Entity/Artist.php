<?php

namespace Db\Entity;

class Artist
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;

        return $this;
    }

    protected $createdAt;

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\Datetime $value)
    {
        $this->createdAt = $value;

        return $this;
    }

    protected $album;

    public function getAlbum()
    {
        return $this->album;
    }

}
