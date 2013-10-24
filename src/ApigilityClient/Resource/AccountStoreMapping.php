<?php

namespace Stormpath\Resource;

use Stormpath\Resource\AbstractResource;
use Stormpath\Service\StormpathService;
use Stormpath\Collections\ResourceCollection;
use Zend\Http\Client;
use Zend\Json\Json;

use Stormpath\Resource\Application;

class AccountStoreMapping extends AbstractResource
{
    protected $_url = '/accountStoreMappings';
    protected $_expandString = 'application,accountStore';

    protected $application;
    protected $listIndex = 0;
    protected $accountStore;
    protected $isDefaultAccountStore = false;
    protected $isDefaultGroupStore = false;

    public function getApplication()
    {
        $this->_load();
        return $this->application;
    }

    public function setApplication(Application $value)
    {
        $this->_load();
        $this->application = $value;
        return $this;
    }

    public function getListIndex()
    {
        $this->_load();
        return $this->listIndex;
    }

    public function setListIndex($value)
    {
        $this->_load();
        $this->listIndex = $value;
        return $this;
    }

    public function getAccountStore()
    {
        $this->_load();
        return $this->accountStore;
    }

    public function setAccountStore($value)
    {
        switch (get_class($value)) {
            case 'Stormpath\Resource\Directory':
            case 'Stormpath\Resource\Group':
                break;
            default:
                throw new \Exception('Account store is neither a Group nor Directory resource.');
                break;
        }

        $this->_load();
        $this->accountStore = $value;
        return $this;
    }

    public function setIsDefaultAccountStore($value)
    {
        $this->_load();
        $this->isDefaultAccountStore = $value;
        return $this;
    }

    public function getIsDefaultAccountStore()
    {
        $this->_load();
        return $this->isDefaultAccountStore;
    }

    public function setIsDefaultGroupStore($value)
    {
        $this->_load();
        $this->isDefaultGroupStore = $value;
        return $this;
    }

    public function getIsDefaultGroupStore()
    {
        $this->_load();
        return $this->isDefaultGroupStore;
    }


    public function exchangeArray($data)
    {
        $eager = $this->getResourceManager()->getExpandReferences();
        $this->getResourceManager()->setExpandReferences(false);

        $this->setHref(isset($data['href']) ? $data['href']: null);
        $this->setListIndex(isset($data['listIndex']) ? $data['listIndex']: null);
        $this->setIsDefaultAccountStore(isset($data['isDefaultAccountStore']) ? $data['isDefaultAccountStore']: false);
        $this->setIsDefaultGroupStore(isset($data['isDefaultGroupStore']) ? $data['isDefaultGroupStore']: false);

        if ($eager) {
            // @codeCoverageIgnoreStart
            throw new \Exception('Resource expansion is not enabled for this resource');

            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Application' . substr($data['application']['href'], strrpos($data['application']['href'], '/') + 1), json_encode($data['application']));
            $application = $this->getResourceManager()->find('Stormpath\Resource\Application', substr($data['application']['href'], strrpos($data['application']['href'], '/') + 1), false);

            if (strstr($data['accountStore']['href'], 'directories')) {
                $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Directory' . substr($data['accountStore']['href'], strrpos($data['accountStore']['href'], '/') + 1), json_encode($data['accountStore']));
                $accountStore = $this->getResourceManager()->find('Stormpath\Resource\Directory', substr($data['accountStore']['href'], strrpos($data['accountStore']['href'], '/') + 1), false);
            } else if (strstr($data['accountStore']['href'], 'groups')) {
                $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Group' . substr($data['accountStore']['href'], strrpos($data['accountStore']['href'], '/') + 1), json_encode($data['accountStore']));
                $accountStore = $this->getResourceManager()->find('Stormpath\Resource\Group', substr($data['accountStore']['href'], strrpos($data['accountStore']['href'], '/') + 1), false);
            } else {
                throw new \Exception('Invalid accountStore returned.  Neither directory or group.');
            }
            // @codeCoverageIgnoreEnd

        } else {
            $application = new \Stormpath\Resource\Application;
            $application->_lazy($this->getResourceManager(), substr($data['application']['href'], strrpos($data['application']['href'], '/') + 1));

            if (strstr($data['accountStore']['href'], 'directories')) {
                $accountStore = new \Stormpath\Resource\Directory;
                $accountStore->_lazy($this->getResourceManager(), substr($data['accountStore']['href'], strrpos($data['accountStore']['href'], '/') + 1));
            } else if (strstr($data['accountStore']['href'], 'groups')) {
                $accountStore = new \Stormpath\Resource\Group;
                $accountStore->_lazy($this->getResourceManager(), substr($data['accountStore']['href'], strrpos($data['accountStore']['href'], '/') + 1));
            } else {
                // @codeCoverageIgnoreStart
                throw new \Exception('Invalid accountStore returned.  Neither directory or group.');
                // @codeCoverageIgnoreEnd
            }
        }
        $this->setApplication($application);
        $this->setAccountStore($accountStore);
    }

    /**
     * GetArrayCopy only returns those properties which can be added and/or updated
     */
    public function getArrayCopy()
    {
        $this->_load();

        return array(
            'application' => array(
                'href' => $this->getApplication()->getHref(),
            ),
            'accountStore' => array(
                'href' => $this->getAccountStore()->getHref(),
            ),
            'listIndex' => $this->getListIndex(),
            'isDefaultAccountStore' => ($this->getIsDefaultAccountStore()) ? 'true': 'false',
            'isDefaultGroupStore' => ($this->getIsDefaultGroupStore()) ? 'true': 'false',
        );
    }
}
