<?php

namespace Stormpath\Resource;

use Stormpath\Resource\AbstractResource;
use Stormpath\Service\StormpathService;
use Stormpath\Resource\Account;
use Stormpath\Resource\Application;

class PasswordResetToken extends AbstractResource
{
    /**
     * Login attempts cannot be lazy loaded or loaded directly
     */
    protected $_url = '';
    protected $_expandString = 'account';

    protected $application;
    protected $email;
    protected $account;

    public function getApplication()
    {
        return $this->application;
    }

    public function setApplication(Application $value)
    {
        $this->application = $value;
        return $this;
    }

    public function setEmail($value)
    {
        $this->_load();
        $this->email = $value;
        return $this;
    }

    public function getEmail()
    {
        $this->_load();
        return $this->email;
    }

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

    public function exchangeArray($data)
    {
        $eager = $this->getResourceManager()->getExpandReferences();
        $this->getResourceManager()->setExpandReferences(false);

        $this->setHref(isset($data['href']) ? $data['href']: null);
        $this->setEmail(isset($data['email']) ? $data['email']: null);

        if ($eager) {
            // @codeCoverageIgnoreStart
            throw new \Exception('Resource expansion is not enabled for this resource');

            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Account' . substr($data['account']['href'], strrpos($data['account']['href'], '/') + 1, json_encode($data['account'])));
            $account = $this->getResourceManager()->find('Stormpath\Resource\Account', substr($data['account']['href'], strrpos($data['account']['href'], '/') + 1, false));
            // @codeCoverageIgnoreEnd
        } else {
            $account = new \Stormpath\Resource\Account;
            $account->_lazy($this->getResourceManager(), substr($data['account']['href'], strrpos($data['account']['href'], '/') + 1));
        }
        $this->setAccount($account);
    }

    public function getArrayCopy()
    {
        $this->_load();

        return array(
            'href' => $this->getHref(),
            'email' => $this->getEmail(),
        );
    }
}
