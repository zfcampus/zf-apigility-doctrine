<?php

namespace Stormpath\Resource;

use Stormpath\Resource\AbstractResource;
use Stormpath\Service\StormpathService;
use Stormpath\Resource\Account;

class LoginAttempt extends AbstractResource
{
    /**
     * Login attempts cannot be lazy loaded or loaded directly
     */
    protected $_url = '';
    protected $_expandString = 'account';

    protected $type = 'basic';
    protected $username;
    protected $password;

    protected $application;
    private $account;

    public function setApplication(Application $value)
    {
        $this->application = $value;
        return $this;
    }

    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setType($value)
    {
        throw new \Exception('Only basic authentication is supported by Stormpath v1');
    }

    public function getType()
    {
        return $this->type;
    }

    public function setUsername($value)
    {
        $this->username = $value;
        return $this;
    }

    public function setPassword($value)
    {
        $this->password = $value;
        return $this;
    }

    private function getValue()
    {
        return base64_encode($this->username . ':' . $this->password);
    }

    private function setAccount(Account $value)
    {
        $this->account = $value;
        return $this;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function exchangeArray($data)
    {
        $eager = $this->getResourceManager()->getExpandReferences();
        $this->getResourceManager()->setExpandReferences(false);

        if ($eager) {
            // @codeCoverageIgnoreStart
            throw new \Exception('Resource expansion is not enabled for this resource');

            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem(
                'Stormpath\Resource\Account' . substr($data['account']['href'],
                strrpos($data['account']['href'], '/') + 1),
                json_encode($data['account']));

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
        return array(
            'type' => $this->getType(),
            'value' => $this->getValue(),
        );
    }
}
