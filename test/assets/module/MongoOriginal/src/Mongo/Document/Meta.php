<?php

namespace Mongo\Document;

class Meta
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    protected $itemUrl;

    public function getItemUrl()
    {
        return $this->itemUrl;
    }

    protected $identifier;

    public function getIdentifier()
    {
        return $this->identifier;
    }

    protected $mediatype;

    public function getMediaType()
    {
        return $this->mediatype;
    }

    protected $publicdate;

    public function getPublicDate()
    {
        return $this->publicdate;
    }

    protected $creator;

    public function getCreator()
    {
        return $this->creator;
    }

    protected $publisher;

    public function getPublisher()
    {
        return $this->publisher;
    }

    protected $description;

    public function getDescription()
    {
        return $this->description;
    }

    protected $date;

    public function getDate()
    {
        return $this->date;
    }

    protected $collection;

    public function getCollection()
    {
        return $this->collection;
    }

    protected $title;

    public function getTitle()
    {
        return $this->title;
    }

    protected $addeddate;

    public function getAddedDate()
    {
        return $this->addeddate;
    }

    protected $credits;

    public function getCredits()
    {
        return $this->credits;
    }

    protected $director;

    public function getDirector()
    {
        return $this->director;
    }

    protected $contact;

    public function getContact()
    {
        return $this->contact;
    }

    protected $subject;

    public function getSubject()
    {
        return $this->subject;
    }
}
