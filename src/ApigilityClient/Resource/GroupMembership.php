<?php

namespace Stormpath\Resource;

use Stormpath\Resource\AbstractResource;
use Stormpath\Resource\Group;
use Stormpath\Resource\Account;
use Stormpath\Service\StormpathService;
use Stormpath\Collections\ResourceCollection;
use Zend\Http\Client;
use Zend\Json\Json;

class GroupMembership extends AbstractResource
{
    protected $_url = '/groupMemberships';
    protected $_expandString = 'account,group';

    protected $account;
    protected $group;

    public function setAccount(Account $value)
    {
        $this->_load();
        $this->account = $value;
        return $this;
    }

    public function getAccount()
    {
        $this->_load();
        return $this->account;
    }

    public function setGroup(Group $value)
    {
        $this->_load();
        $this->group = $value;
        return $this;
    }

    public function getGroup()
    {
        $this->_load();
        return $this->group;
    }

    public function exchangeArray($data)
    {
        $eager = $this->getResourceManager()->getExpandReferences();
        $this->getResourceManager()->setExpandReferences(false);

        $this->setHref(isset($data['href']) ? $data['href']: null);

        if ($eager) {
            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Account' . substr($data['account']['href'], strrpos($data['account']['href'], '/') + 1), json_encode($data['account']));
            $account = $this->getResourceManager()->find('Stormpath\Resource\Account', substr($data['account']['href'], strrpos($data['account']['href'], '/') + 1, false));
        } else {
            $account = new \Stormpath\Resource\Account;
            $account->_lazy($this->getResourceManager(), substr($data['account']['href'], strrpos($data['account']['href'], '/') + 1));
        }
        $this->setAccount($account);

        if ($eager) {
            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Group' . substr($data['group']['href'], strrpos($data['group']['href'], '/') + 1), json_encode($data['group']));
            $group = $this->getResourceManager()->find('Stormpath\Resource\Group', substr($data['group']['href'], strrpos($data['group']['href'], '/') + 1, false));
        } else {
            $group = new \Stormpath\Resource\Group;
            $group->_lazy($this->getResourceManager(), substr($data['group']['href'], strrpos($data['group']['href'], '/') + 1));
        }
        $this->setGroup($group);
    }

    public function getArrayCopy()
    {
        $this->_load();

        return array(
            'account' => array(
                'href' => $this->getAccount()->getHref(),
            ),
            'group' => array(
                'href' => $this->getGroup()->getHref(),
            ),
        );
    }
}
