<?php

namespace ZFTestApigilityDb\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Artist
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->album = new ArrayCollection();
    }

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

    public function setCreatedAt(\DateTime $value)
    {
        $this->createdAt = $value;

        return $this;
    }

    protected $album;

    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * Add album
     *
     * @param Album $album
     * @return $this
     * @throws \Exception
     */
    public function addAlbum($album)
    {
        if ($album instanceof \ZFTestApigilityDb\Entity\Album) {
            $this->album[] = $album;
        } elseif ($album instanceof ArrayCollection) {
            foreach ($album as $a) {
                if (! $a instanceof \ZFTestApigilityDb\Entity\Album) {
                    throw new \Exception('Invalid type in addAlbum');
                }
                $this->album->add($a);
            }
        }

        return $this;
    }

    /**
     * Remove album
     *
     * @param Album $album
     * @throws \Exception
     */
    public function removeAlbum($album)
    {
        if ($album instanceof \ZFTestApigilityDb\Entity\Album) {
            $this->album[] = $album;
        } elseif ($album instanceof ArrayCollection) {
            foreach ($album as $a) {
                if (! $a instanceof \ZFTestApigilityDb\Entity\Album) {
                    throw new \Exception('Invalid type remove addAlbum');
                }
                $this->album->removeElement($a);
            }
        }
    }
}
