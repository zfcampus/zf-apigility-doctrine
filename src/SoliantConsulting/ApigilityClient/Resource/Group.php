<?php

namespace Stormpath\Resource;

use Stormpath\Resource\AbstractResource;
use Stormpath\Service\StormpathService;
use Stormpath\Collections\ResourceCollection;
use Zend\Http\Client;
use Zend\Json\Json;

class Group extends AbstractResource
{
    protected $_url = '/groups';
    protected $_expandString = 'directory,tenant';

    protected $name;
    protected $description;
    protected $status;

    protected $tenant;
    protected $directory;

    protected $accounts;
    protected $accountMemberships;

    /**
     * When a group is created the _url is changed to the directory
     * it is created under.  Therefore we reset the url when the Href is set.
     */
    public function setHref($value)
    {
        $this->_setUrl(StormpathService::getBaseUrl() . '/groups');

        return parent::setHref($value);
    }

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

    public function getDescription()
    {
        $this->_load();
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->_load();
        $this->description = $value;
        return $this;
    }

    public function getStatus()
    {
        $this->_load();
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->_load();
        $this->status = $value;
        return $this;
    }

    public function setTenant(Tenant $value)
    {
        $this->_load();
        $this->tenant = $value;
        return $this;
    }

    public function getTenant()
    {
        $this->_load();
        return $this->tenant;
    }

    public function setDirectory(Directory $value)
    {
        $this->_load();
        $this->directory = $value;
        return $this;
    }

    public function getDirectory()
    {
        $this->_load();
        return $this->directory;
    }

    public function setAccounts(ResourceCollection $value)
    {
        $this->_load();
        $this->accounts = $value;
        return $this;
    }

    public function getAccounts()
    {
        $this->_load();
        return $this->accounts;
    }

    public function setAccountMemberships(ResourceCollection $value)
    {
        $this->_load();
        $this->accountMemberships = $value;
        return $this;
    }

    public function getAccountMemberships()
    {
        $this->_load();
        return $this->accountMemberships;
    }

    public function exchangeArray($data)
    {
        $eager = $this->getResourceManager()->getExpandReferences();
        $this->getResourceManager()->setExpandReferences(false);

        $this->setHref(isset($data['href']) ? $data['href']: null);
        $this->setName(isset($data['name']) ? $data['name']: null);
        $this->setDescription(isset($data['description']) ? $data['description']: null);
        $this->setStatus(isset($data['status']) ? $data['status']: null);

        if ($eager) {
            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Directory' . substr($data['directory']['href'], strrpos($data['directory']['href'], '/') + 1), json_encode($data['directory']));
            $directory = $this->getResourceManager()->find('Stormpath\Resource\Directory', substr($data['directory']['href'], strrpos($data['directory']['href'], '/') + 1, false));
        } else {
            $directory = new \Stormpath\Resource\Directory;
            $directory->_lazy($this->getResourceManager(), substr($data['directory']['href'], strrpos($data['directory']['href'], '/') + 1));
        }
        $this->setDirectory($directory);

        if ($eager) {
            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Tenant' . substr($data['tenant']['href'], strrpos($data['tenant']['href'], '/') + 1), json_encode($data['tenant']));
            $tenant = $this->getResourceManager()->find('Stormpath\Resource\Tenant', substr($data['tenant']['href'], strrpos($data['tenant']['href'], '/') + 1, false));
        } else {
            $tenant = new \Stormpath\Resource\Tenant;
            $tenant->_lazy($this->getResourceManager(), substr($data['tenant']['href'], strrpos($data['tenant']['href'], '/') + 1));
        }
        $this->setTenant($tenant);

        $this->setAccounts(new ResourceCollection($this->getResourceManager(), 'Stormpath\Resource\Account', $data['accounts']['href']));
        $this->setAccountMemberships(new ResourceCollection($this->getResourceManager(), 'Stormpath\Resource\GroupMembership', $data['accountMemberships']['href']));
    }

    public function getArrayCopy()
    {
        $this->_load();

        return array(
            'href' => $this->getHref(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'status' => $this->getStatus(),
        );
    }
}
