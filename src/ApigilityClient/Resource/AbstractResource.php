<?php

namespace Stormpath\Resource;

use Stormpath\Persistence\ResourceManager;
use Stormpath\Service\StormpathService;

abstract class AbstractResource
{
    /**
     * Set the $_url to the url to fetch a resource from for this resource type
     *
     * @required per resource
     */
    protected $_url = '';

    /**
     * The instance of the resource manager which manages this resource
     */
    protected $_resourceManager;

    /**
     * When lazy loading is used this variable is set to false
     * until the loading of the resource has occured
     */
    private $_isInitialized = true;

    /**
     * The id for this resource.  This variable is only used
     * for lazy loading
     */
    private $_identifier;

    /**
     * The true identifier of this resource.  Populated only after
     * the resource has been loaded
     */
    private $id;

    /**
     * The url primary key
     */
    private $href;

    /**
     * A string of all references on the Resource to eagerly load with Expand Resources
     */
    protected $_expandString;

    /**
     * An array of get parameters to add to the next request.  This is reset between requests.
     */
    protected $_additionalQueryParameters = array();

    public function __construct()
    {
        $this->_setUrl(StormpathService::getBaseUrl() . $this->_getUrl());
    }

    public function getAdditionalQueryParameters()
    {
        return $this->_additionalQueryParameters;
    }

    public function resetAdditionalQueryParameters()
    {
        $this->_additionalQueryParameters = array();
        return $this;
    }

    public function getExpandString()
    {
        return $this->_expandString;
    }

    /**
     * Get the resource manager managing this resource
     */
    public function getResourceManager()
    {
        return $this->_resourceManager;
    }

    /**
     * Set the resource manager
     */
    public function setResourceManager(ResourceManager $resourceManager)
    {
        $this->_resourceManager = $resourceManager;
        return $this;
    }

    /**
     * Initialize this resource for lazy loading
     */
    public function _lazy($resourceManager, $identifier)
    {
        $this->_isInitialized = false;
        $this->_identifier = $identifier;
        $this->setResourceManager($resourceManager);
    }

    /**
     * Load a lazy initialized resource
     */
    public function _load($overrideIdentifierAndForceLoad = false)
    {
        if (!$overrideIdentifierAndForceLoad
            and ($this->_isInitialized or !$this->_identifier)) {
            return;
        }

        $this->_isInitialized = true;

        $this->setId($overrideIdentifierAndForceLoad ?: $this->_identifier);
        $this->_resourceManager->load($this->getId(), $this);

        unset($this->_entityPersister, $this->_identifier);
    }

    public function getId()
    {
        $this->_load();
        return $this->id;
    }

    protected function setId($value)
    {
        $this->id = $value;
        return $this;
    }

    public function getHref()
    {
        if ($this->href) {
            return $this->href;
        }

        if ($this->getId()) {
            return $this->_getUrl() . '/' . $this->getId();
        }
    }

    public function setHref($value)
    {
        $this->href = $value;

        $this->setId(substr($value, strrpos($value, '/') + 1));

        return $this;
    }

    public function _getUrl()
    {
        return $this->_url;
    }

    public function _setUrl($value)
    {
        $this->_url = $value;
        return $this;
    }

    abstract public function exchangeArray($values);

    abstract public function getArrayCopy();
}
