<?php

namespace Stormpath\Resource;

use Stormpath\Resource\AbstractResource;
use Stormpath\Service\StormpathService;
use Stormpath\Collections\ResourceCollection;
use Zend\Http\Client;
use Zend\Json\Json;
use Stormpath\Resource\Application;

class Account extends AbstractResource
{
    protected $_url = '/accounts';
    protected $_expandString = 'directory,tenant';
    protected $username;
    protected $email;
    protected $emailVerificationToken;
    protected $password;
    protected $givenName;
    protected $middleName;
    protected $surname;
    protected $status;
    protected $groups;
    protected $directory;
    protected $tenant;
    protected $application;

    /**
     * When an account is created the _url is changed to the directory
     * it is created under.  Therefore we reset the url when the Href is set.
     */
    public function setHref($value)
    {
        $this->_setUrl(StormpathService::getBaseUrl() . '/accounts');

        return parent::setHref($value);
    }

    public function getUsername()
    {
        $this->_load();
        return $this->username;
    }

    public function setUsername($value)
    {
        $this->_load();
        $this->username = $value;
        return $this;
    }

    public function getEmail()
    {
        $this->_load();
        return $this->email;
    }

    public function setEmail($value)
    {
        $validator = new \Zend\Validator\EmailAddress();
        if (!$validator->isValid($value)) {
            throw new \Exception("Invalid email address");
        }

        $this->_load();
        $this->email = $value;
        return $this;
    }

    public function getEmailVerificationToken()
    {
        $this->_load();
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken($value)
    {
        $this->_load();
        $this->emailVerificationToken = $value;
        return $this;
    }

    public function getPassword()
    {
        $this->_load();
        return $this->password;
    }

    public function setPassword($value)
    {
        if (strtolower($value) === $value
            or strtoupper($value) === $value) {
            throw new \Exception('Password must be mixed case');
        }

        $this->_load();
        $this->password = $value;
        return $this;
    }

    public function getGivenName()
    {
        $this->_load();
        return $this->givenName;
    }

    public function setGivenName($value)
    {
        $this->_load();
        $this->givenName = $value;
        return $this;
    }

    public function getMiddleName()
    {
        $this->_load();
        return $this->middleName;
    }

    public function setMiddleName($value)
    {
        $this->_load();
        $this->middleName = $value;
        return $this;
    }

    public function getSurname()
    {
        $this->_load();
        return $this->surname;
    }

    public function setSurname($value)
    {
        $this->_load();
        $this->surname = $value;
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

    public function setGroups(ResourceCollection $value)
    {
        $this->_load();
        $this->groups = $value;
        return $this;
    }

    public function getGroups()
    {
        $this->_load();
        return $this->groups;
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

    /**
     * Application is only used when creating a new account
     *
     * Directory or Application must be set to create.  Directory overrides Application.
     */
    public function setApplication(Application $value)
    {
        $this->application = $value;
        return $this;
    }

    public function getApplication()
    {
        return $this->application;
    }

    public function exchangeArray($data)
    {
        $eager = $this->getResourceManager()->getExpandReferences();
        $this->getResourceManager()->setExpandReferences(false);

        $this->setHref(isset($data['href']) ? $data['href']: null);
        $this->setUsername(isset($data['username']) ? $data['username']: null);
        $this->setEmail(isset($data['email']) ? $data['email']: null);
        $this->setEmailVerificationToken(isset($data['emailVerificationToken']) ? $data['emailVerificationToken']: null);
        $this->setPassword(isset($data['password']) ? $data['password']: null);
        $this->setGivenName(isset($data['givenName']) ? $data['givenName']: null);
        $this->setMiddleName(isset($data['middleName']) ? $data['middleName']: null);
        $this->setSurname(isset($data['surname']) ? $data['surname']: null);
        $this->setStatus(isset($data['status']) ? $data['status']: null);

        $this->setGroups(new ResourceCollection($this->getResourceManager(), 'Stormpath\Resource\Group', $data['groups']['href']));

        if ($eager) {
            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Directory' . substr($data['directory']['href'], strrpos($data['directory']['href'], '/') + 1), json_encode($data['directory']));
            $directory = $this->getResourceManager()->find('Stormpath\Resource\Directory', substr($data['directory']['href'], strrpos($data['directory']['href'], '/') + 1), false);
        } else {
            $directory = new \Stormpath\Resource\Directory;
            $directory->_lazy($this->getResourceManager(), substr($data['directory']['href'], strrpos($data['directory']['href'], '/') + 1));
        }
        $this->setDirectory($directory);

        if ($eager) {
            // If this resource was fetched with eager loading store the retrieved data in the cache then
            // fetch the object from the cache.
            $this->getResourceManager()->getCache()->setItem('Stormpath\Resource\Tenant' . substr($data['tenant']['href'], strrpos($data['tenant']['href'], '/') + 1), json_encode($data['tenant']));
            $tenant = $this->getResourceManager()->find('Stormpath\Resource\Tenant', substr($data['tenant']['href'], strrpos($data['tenant']['href'], '/') + 1), false);
        } else {
            $tenant = new \Stormpath\Resource\Tenant;
            $tenant->_lazy($this->getResourceManager(), substr($data['tenant']['href'], strrpos($data['tenant']['href'], '/') + 1));
        }
        $this->setTenant($tenant);
    }

    public function getArrayCopy()
    {
        $this->_load();

        return array(
            'href' => $this->getHref(),
            'username' => $this->getUsername(),
            'email' => $this->getEmail(),
            'emailVerificationToken' => $this->getEmailVerificationToken(),
            'password' => $this->getPassword(),
            'givenName' => $this->getGivenName(),
            'middleName' => $this->getMiddleName(),
            'surname' => $this->getSurname(),
            'status' => $this->getStatus(),
        );
    }
}
