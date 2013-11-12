Soliant Consulting 
==================

Apigility for Doctrine
----------------------

This library has three parts.  

1. An API client [Object Relational Mapper]
(https://en.wikipedia.org/wiki/Object-relational_mapping) based on the 
[Doctrine Common](http://www.doctrine-project.org/projects/common.html) 
project library.

2. An API server AbstractService class to handle most API interations.

3. An Admin tool to create an Apigility-enabled module with support for all 
Doctrine Entities in scope.


All parts use common Doctrine Entities
--------------------------------------------------------

Your API client must have a copy of the same Entity code base as the server 
and as the Admin module used to build the Apigility-enabled module.

This is best accomplished by creating a distinct module for your entities and 
repositories.


Installation
------------
  1. edit `composer.json` file with following contents:

     ```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/TomHAnderson/soliantconsulting-apigility"
        }
    ],
    "require": {
        "soliantconsulting/soliantconsulting-apigility": "dev-master"
    }
     ```
  2. install composer via `curl -s http://getcomposer.org/installer | php` (on windows, download
     http://getcomposer.org/installer and execute it with PHP)
  3. run `php composer.phar install`


Doctrine Entity Configuration
-----------------------

Doctrine Entities should be ArraySerializable.  You should not include embedded 
resources.


Creating the Apigility-enabled module
-------------------------------------

Browse to http://localhost/soliantconsulting/apigility/admin
Enter the name of your new module.  I suggest DoctrineEntityModuleName + Api such as DbApi.
Click Build Module.  The module will be created and you will be sent to the 
Build Entity API Resources screen.

Client Configuration
--------------------

Assign a base url to the client

```php
    $client->setBaseUrl('http://www.soliantconsulting.com/api');
```

Doctrine entities and rpc calls are mapped to the client through a resource map

```php
    $client->setResourceMap(array(
        'rpc' => array(
            'alias' => '/rpc/path',
        ),
        
        'entities' => array(
            'SoliantConsulting\Entity\User' => 'user',
            'SoliantConsulting\Entity\Address' => 'address',
            'SoliantConsulting\Entity\UserGroup' => 'userGroup',
            ...
    ));
```

FIXME:  add authentication once it's been created for Apigility

Use
---

Fetch the ORM and execute queries

```php
    $entityManager = $client->getEntityManager();
    
    $user = new \SoliantConsulting\Entity\User;
    $user->setName('Tom Anderson');
    $user->setPassword('12345');
    
    $entityManager->persist($user);
    $entityManager->flush($user);
    
    // Will return > 0
    $userCopy = $entityManager->find('SoliantConsulting\Entity\User', $user->getId()); 

    if ($user == $userCopy) {
        die('success');
    }
```

Make RPC calls

```php
    $result = $client->rpc('alias', array('parameters'));
```
