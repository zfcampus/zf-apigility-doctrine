<?php

namespace ZFTestApigilityDb\Entity;

class Album
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

    protected $artist;

    public function getArtist()
    {
        return $this->artist;
    }

    public function setArtist($value)
    {
        $this->artist = $value;

        return $this;
    }

    protected $album;

    /**
     * Parent Album
     *
     * @return null|Album
     */
    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * Parent Album
     *
     * @param null|Album $album
     * @return $this
     */
    public function setAlbum($album)
    {
        if (null !== $album && !$album instanceof Album) {
            throw new \InvalidArgumentException('Invalid album argument');
        }
        $this->album = $album;
        return $this;
    }
}
