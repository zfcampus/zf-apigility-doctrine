# Stormpath Client for PHP

[![Build Status](https://travis-ci.org/stormpath/stormpath-sdk-php.png)](https://travis-ci.org/stormpath/stormpath-sdk-php)
[![Dependency Status](https://www.versioneye.com/user/projects/51e052589041060002005a07/badge.png)](https://www.versioneye.com/user/projects/51e052589041060002005a07)

Overview
========

This API client is an [Object Relational Mapper](https://en.wikipedia.org/wiki/Object-relational_mapping) based on the [Doctrine Common](http://www.doctrine-project.org/projects/common.html) project library.  These are the available Resources:

```php 
    Stormpath\Resource\Account
    Stormpath\Resource\AccountStoreMapping
    Stormpath\Resource\Application
    Stormpath\Resource\EmailVerificationToken
    Stormpath\Resource\Directory
    Stormpath\Resource\Group
    Stormpath\Resource\GroupMembership
    Stormpath\Resource\LoginAttempt
    Stormpath\Resource\PasswordResetToken
    Stormpath\Resource\Tenant
```

When resources are found using the find() method of the Resource Manager they are initialized and fetched immediatly.  When Resources are part of a collection they are lazy loaded so the Resource is not fetched from the server until it is acted upon through a getter, setter, or Resource Manager action.

Multiple Resources may be set for insert, update, or delete
and all acted upon when the Resource Manager is flush(); ed.  To queue a Resource for addition or update use ``` $resourceManager->persist($resource); ```  To queue a Resource for deletion use ``` $resourceManager->remove($resource); ```

See the [Stormpath Product Guide](https://www.stormpath.com/docs/php/product-guide) and the [Stormpath REST API](http://www.stormpath.com/docs/rest/api) for details on everything this client implements.


Collections
-----------

A collection is a group of related Resources as a property of a Resource and may be paginated.  By default collections have a limit of 25 and an offset of 0.  These may be changed by fetching the collection and using ``` $collection->setLimit(#); ``` and ``` $collection->setOffset(#); ```

```php
    $groupsCollection = $directory->getGroups();
    $this->assertEquals(25, sizeof($groupsCollection));
```

To fetch a new page of results from a collection set the new limit and/or offset.  This will clear the collection so the next time it's accessed it will be with the new offset/limit(s).  The collection will lazy load the next time it is used.

```php
    $groupsCollection->setOffset(25);
    $this->assertEquals(25, sizeof($groupsCollection));

    $groupsCollection->setLimit(5);
    $groupsCollection->setOffset(0);
    $this->assertEquals(5, sizeof($groupsCollection));
```

You may search collections by setting setSearch(string|array);  See https://www.stormpath.com/docs/rest/api#CollectionResources for more details of search options.

```php
    // Search all properties for Joe
    $groupsCollection->setSearch('Joe');
    
    // Search name for Joe
    $groupsCollection->setSearch(array(
        'name' => 'Joe'
    ));
```

You may sort Collections

```php

    $groupsCollection->setOrderBy(array('name' => 'ASC', 'description' => 'DESC')); 
```

The collection is reset when the sort, search, offset, or limit is set and will lazy load when the collection is next accessed.  


Resource Expansion
------------------

This feature of the Stormpath API can also be called eager loading references.  You may use Resource Expansion when using the ``` $resourceManager->find(); ``` method.  Resource Expansion will occur for the found resource only and will not occur for resources which are returned from the find().  In other words, when a resource is fetched eagerly, with Resource Expansion, only those resources directly associated to the found Resource will be eagerly loaded.  Resources which are properties of the Resources which are eagerly loaded are not eagerly loaded.  This avoids a waterfall affect of loading whole trees of data with one request.

To expand a resouce use ``` $resourceManager->find('Stormpath\Resource\ResourceName', $resourceId, true); ``` setting the third parameter to true to enable resource expansion.  You cannot use resource expansion for resources fetched from a Collection.


Caching
-------

This API client relies on caching.  There must be a ZF2 cache registered with the StormpathService.  By default the Memory cache is used.

Caching occurs whenever a Resource is fetched from Stormpath.  Caches are identified by ResourceName + ResourceID such as 'Stormpath\Resource\Application12345'.  Caching will return a copy of the last fetched resource when that resource is fetched again.  This works for lazy loaded Resources too:  e.g. a Resource is fetched as part of a collection and configured for lazy loading.  When the Resource is loaded it will pull a cached copy of that resource if one exists.

Caching works completely behind the scenes and you can change the cache adapter when you configure the service.  See the Use section for an example of changing the cache adapter.  If you ever had a need to work with the cache directly you can fetch the current adapter with ``` StormpathService::getCache(); ``` or ``` $resourceManager->getCache(); ```


Exception Handling
------------------

Any exception generated by Stormpath will be thrown as a Stormpath\Exception\ApiException.  See the LoginAttempt and PasswordResetToken for examples.  This exception class has the following functions which correspond to the error thrown by Stormpath:

```php
    getMessage
    getCode
    getStatus
    getDeveloperMessage
    getMoreInfo
``` 


Installation
------------
  1. edit `composer.json` file with following contents:

     ```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/TomHAnderson/StormpathClient-PHP"
        }
    ],
    "require": {
        "stormpath/stormpath": "dev-master"
    }
     ```
  2. install composer via `curl -s http://getcomposer.org/installer | php` (on windows, download
     http://getcomposer.org/installer and execute it with PHP)
  3. run `php composer.phar install`


Use
---
Configure for use

```php
    use Stormpath\Service\StormpathService;

    StormpathService::configure($id, $secret);
```

Optionally enable Basic authentication instead of the default Digest authentication

```php
    use Stormpath\Http\Client\Adapter\Basic;

    StormpathService::configure($id, $secret);
    StormpathService::getHttpClient()->setAdapter(new Basic(null, array('keepalive' => true)));
```

By default a memory cache is used.  You may use an alternative cache.  See https://packages.zendframework.com/docs/latest/manual/en/modules/zend.cache.storage.adapter.html for all available cache adapters.  The advantage of enabling an alternative cache is the cache may persist between user sessions.

```php
    use Zend\Cache\StorageFactory;

    Stormpath::setCache(StorageFactory::adapterFactory('apc'));
```

Once configured with these options you may fetch the Resource Manager to begin working.

```php
    use Stormpath\Service\StormpathService;
    
    $resourceManager = StormpathService::getResourceManager();
```


Finding Resources
-----------------

To find an existing resource use the find() method of the Resource Manager.

```php
    use Stormpath\Service\StormpathService;

    $resourceManager = StormpathService::getResourceManager();
    
    // Parameters are the Resource class and id for the resource
    $account = $resourceManager->find('Stormpath\Resource\Account', $resourceId);
```

To eagerly load a resouce use ``` $resourceManager->find('Stormpath\Resource\ResourceName', $resourceId, true);


Creating a Resource
-------------------

To create a new resource create a new instance of it's resource class, assign applicable properties then persist it in the Resource Manager.

```php
    use Stormpath\Service\StormpathService;
    use Stormpath\Resource\Application;

    $resourceManager = StormpathService::getResourceManager();

    $app = new Application;
    
    $app->setName(md5(rand()));
    $app->setDescription('API Created Application');
    $app->setStatus('ENABLED');

    $resourceManager->persist($app);
    $resourceManager->flush();
```

After running this code the $app object will be a fully populated Application resource.


Editing a Resource
------------------

Editing resources is as simple as setting properties on a found object then persisting the resource.

```php
    use Stormpath\Service\StormpathService;
    
    $resourceManager = StormpathService::getResourceManager();
    
    // Parameters are the Resource class and id for the resource
    $account = $resourceManager->find('Stormpath\Resource\Account', $accountId);
    
    $account->setSurname('ChangedSurname');
    
    $resourceManager->persist($account)
    $resourceManager->flush();
```


Deleting a Resource
-------------------

Use the resource manager to delete resources

```php
    use Stormpath\Service\StormpathService;
    
    $resourceManager = StormpathService::getResourceManager();
    
    // Parameters are the Resource class and id for the resource
    $account = $resourceManager->find('Stormpath\Resource\Account', $accountId);
    
    $resourceManager->remove($account)
    $resourceManager->flush();
```


Common Resource Properties
--------------------------

Every resource shares these methods

```php
    // Return the resource id
    $resource->getId(); 
    
    // Return the resource Href including the id portion
    // Collections also have this method.
    $resource->getHref();
```


Account
-------

Accounts must be created against an Application or a Directory.  To specify which just set the property of either using ``` $account->setApplication($application); ``` or ``` $account->setDirectory($directory); ``` at the time you create the account resource.

Create a new account and assign it to an Application

```php
    use Stormpath\Service\StormpathService;
    use Stormpath\Resource\Account;

    $resourceManager = StormpathService::getResourceManager();

    // Parameters are the Resource class and id for the resource
    $application = $resourceManager->find('Stormpath\Resource\Application', $applicationId);

    $account = new Account;
    $account->setUsername(md5(rand()));
    $account->setEmail(md5(rand()) . '@test.stormpath.com');
    
    // Passwords must contain upper and lower case characters
    $account->setPassword(md5(rand()) . strtoupper(md5(rand())));
    $account->setGivenName('Test');
    $account->setMiddleName('User');
    $account->setSurname('One');
    $account->setStatus('Enabled');
    
    $account->setApplication($application);
    
    // To assign to a directory instead
    #  $account->setDirectory($directory);

    $resourceManager->persist($account);
    $resourceManager->flush();
```

Properties (editable with ``` $account->set[Property]($value); ```)

```
    Username
    Email
    Password
    GivenName
    MiddleName
    Surname
    Status
```

References

```
    Application - only used when creating an account.  This reference is not populated from a find() call.
    Directory
    Tenant
```

Collections

```
    Groups
```


AccountStoreMapping
-------------------

Map an application to an account store.  An account store is a Directory or a Group.  Accounts are attached 
to the Directory or Group and mapped back to the application through this resource.

An AccountStoreMapping may provide multiple roles.  First, it may specify it's accountStore as the designated
storage location for new Accounts created through the Application endpoint.

Second, it may speicfy it's accountStore as the designated storage location for new Groups created through the
Application endpoint.

``

Application
-----------

Create a new application

```php
    use Stormpath\Service\StormpathService;

    $resourceManager = StormpathService::getResourceManager();

    $app = new Application;

    $app->setName(md5(rand()));
    $app->setDescription('phpunit test application');
    $app->setStatus('ENABLED');

    $resourceManager->persist($app);
    $resourceManager->flush();
```

You may auto-create a directory and assign it to the AccountStore of the defaultAccountStoreMapping for the application when it is created.

```php
    // directoryName may be true for automatically generated name
    $app->setAutoCreateDirectory($directoryName);
```


Properties

```
    Name
    Description
    Status
```

References

```
    Tenant
```

Collections

```
    Accounts
    Groups
    LoginAttempts
    PasswordResetTokens
```


Directory
---------

Properties

```
    Name
    Description
    Status
```

References

```
    Tenant
```

Collections

```
    Accounts
    Groups
```

Group
-----

You must set a Directory before persisting a new Group.

Properties

```
    Name
    Description
    Status
```

References

```
    Tenant
    Directory
```

Collections

```
    Accounts
    AccountMemberships
```


Group Membership
----------------

All properties are Resources.  Group Memberships may only be created or deleted.  To create a Group Membership set the Account and Group then persist.

Properties set when created
```
    Account
    Group
```


Login Attempt
-------------

A login attempt is the Resource to use when you want to authenticate a user by username and password against an application.  All three parameters are required.

```php
    use Stormpath\Exception\ApiException;

    $loginAttempt = new LoginAttempt;
    $loginAttempt->setUsername($username);
    $loginAttempt->setPassword($password);
    $loginAttempt->setApplication($application);

    $resourceManager->persist($loginAttempt);

    try {
        $resourceManager->flush();
        $authorizedAccount = $loginAttempt->getAccount();
    } catch (ApiException $e) {
        if ($e->getCode() == 400) {
            $userMessage = $e->getMessage();  # will = There is no account with that email address.
        }
    }
```


Password Reset Token
--------------------

To initialize a password reset email create a PasswordResetToken, set the email and application, and persist and flush it.  Post flush the PasswordResetToken will contain the acocunt which was reset.

```php
    use Stormpath\Resource\PasswordResetToken;
    use Stormpath\Exception\ApiException;

    $application = $resourceManager->find('Stormpath\Resource\Application', $applicationId);

    $passwordResetToken = new PasswordResetToken;
    $passwordResetToken->setApplication($application);
    $passwordResetToken->setEmail('resetpassword@test.stormpath.com');
    $resourceManager->persist($passwordResetToken);

    try {
        $resourceManager->flush();
        $account = $passwordResetToken->getAccount();
    } catch (ApiException $e) {
        if ($e->getCode() == 400) {
            $userMessage = $e->getMessage(); # message may be: "There is no account with that email address.",
        }
    }
```    

Email Verification Token
------------------------

```php
use Stormpath\Resource\EmailVerificationToken;

// Obtained from the GET parameter 'verificationToken'
$verificationToken = 'token'; 

$emailVerificationToken = new EmailVerificationToken;
$emailVerificationToken->setToken($verificationToken);

$resourceManager->persist($emailVerificationToken);
$resourceManager->flush();

$account = $emailVerificationToken->getAccount();
```


Tenant
------

Get the current tenant

```php
    $currentTenant = $resourceManager->find('Stormpath\Resource\Tenant', 'current');
```



Testing
-------
Create a ```local.php``` file and set api parameters and run composer with --dev then run phpunit.

```php
<?php
// ~/test/autoload/local.php

return array(
    'stormpath' => array(
        'id' => 'stormpath_id',
        'secret' => 'stormpath_secret',
    ),
);
```

This project is licensed under the [Apache 2.0 Open Source License](http://www.apache.org/licenses/LICENSE-2.0).

Copyright &copy; 2013 Stormpath, Inc. and contributors.  
