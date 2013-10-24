<?php

namespace Stormpath\Resource;

use Stormpath\Resource\AbstractResource;
use Stormpath\Service\StormpathService;
use Stormpath\Resource\Account;

/**
 * @codeCoverageIgnore
 */
class EmailVerificationToken extends AbstractResource
{
    /**
     * Login attempts cannot be lazy loaded or loaded directly
     */
    protected $_url = '/accounts/emailVerificationTokens';

    protected $token;
    protected $account;

    public function setToken($value)
    {
        $this->token = $value;
        return $this;
    }

    public function getToken()
    {
        return $this->token;
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
            if (isset($data['account'])) {
                $account = new \Stormpath\Resource\Account;
                $account->_lazy($this->getResourceManager(), substr($data['account']['href'], strrpos($data['account']['href'], '/') + 1));
            }
        }
        if (isset($account)) $this->setAccount($account);
    }

    public function getArrayCopy()
    {
        return array(
        );
    }
}
