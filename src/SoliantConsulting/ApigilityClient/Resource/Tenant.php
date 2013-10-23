<?php
/**
 * A class to fetch the Tenant resources
 *
 */

namespace Stormpath\Resource;

use Stormpath\Collections\ResourceCollection;
use Zend\Http\Client;
use Zend\Json\Json;

class Tenant extends AbstractResource
{
    protected $_url = '/tenants';

    protected $name;

    public function getName()
    {
        $this->_load();
        return $this->name;
    }

    public function setName($value)
    {
        $this->_load();
        $this->name = $value;
        return $this;
    }

    protected $key;

    public function getKey()
    {
        $this->_load();
        return $this->key;
    }

    public function setKey($value)
    {
        $this->_load();
        $this->key = $value;
        return $this;
    }

    protected $applications;

    public function getApplications()
    {
        $this->_load();
        return $this->applications;
    }

    public function setApplications(ResourceCollection $applications)
    {
        $this->_load();
        $this->applications = $applications;
    }

    protected $directories;

    public function getDirectories()
    {
        $this->_load();
        return $this->directories;
    }

    public function setDirectories(ResourceCollection $directories)
    {
        $this->_load();
        $this->directories = $directories;
    }

    public function exchangeArray($data)
    {
        $this->setHref(isset($data['href']) ? $data['href']: null);
        $this->setName(isset($data['name']) ? $data['name']: null);
        $this->setKey(isset($data['key']) ? $data['key']: null);

        $this->setApplications(new ResourceCollection($this->getResourceManager(), 'Stormpath\Resource\Application', $data['applications']['href']));
        $this->setDirectories(new ResourceCollection($this->getResourceManager(), 'Stormpath\Resource\Directory', $data['directories']['href']));
    }

    public function getArrayCopy()
    {
        $this->_load();
        return array(
            'href' => $this->getHref(),
            'name' => $this->getName(),
            'key' => $this->getKey(),
        );
    }
}