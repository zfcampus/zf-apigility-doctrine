Client for Apigility
====================

This API client is an [Object Relational Mapper]
(https://en.wikipedia.org/wiki/Object-relational_mapping) based on the 
[Doctrine Common](http://www.doctrine-project.org/projects/common.html) 
project library, hereafter referred to as Client, for Apigility.


Apigility Server and Client use common Doctrine Entities
--------------------------------------------------------

Your Apigility server should serve Doctrine entities from the same entity code 
base as the resources mapped to the service.  This can be accomplished by 
keeping all your Doctrine entities in the same ZF2 module and either:

a. Include this library with the Apigility service in the same code base

b. Include this library in a code base which shares the same Doctrine entities module.

This client is an HTTP client and not ment to consume Apigility resources directly, but only through an HTTP API.


Apigility Configuration
-----------------------

Doctrine Entities should be ArraySerializable.  You should not include embedded resources.  Resource identifier_name must be 'id'.

```php
  'zf-hal' =>
  array (
    'renderer' =>
    [
      'default_hydrator' => 'ArraySerializable',
      'render_embedded_resources' => false, // see https://github.com/zfcampus/zf-hal/pull/3
    ],
```

module.config.php for Apigility

return array (
  'service_manager' =>
  array (
    'invokables' =>
    array (
      'SoliantConsultingApi\\V1\\Rest\\UserGroup\\UserGroupResource' => 'SoliantConsultingApi\\V1\\Rest\\UserGroup\\UserGroupResource',
      'SoliantConsultingApi\\V1\\Rest\\User\\UserResource' => 'SoliantConsultingApi\\V1\\Rest\\User\\UserResource',
    ),
  ),
  'router' =>
  array (
    'routes' =>
    array (
      'soliant-consulting-api.rest.userGroup' =>
      array (
        'type' => 'Segment',
        'options' =>
        array (
          'route' => '/api/userGroup[/:id]',
          'defaults' =>
          array (
            'controller' => 'SoliantConsultingApi\\V1\\Rest\\UserGroup\\Controller',
          ),
        ),
      ),
      'soliant-consulting-api.rest.user' =>
      array (
        'type' => 'Segment',
        'options' =>
        array (
          'route' => '/api/user[/:id]',
          'defaults' =>
          array (
            'controller' => 'DbLoadCdApi\\V1\\Rest\\User\\Controller',
          ),
        ),
      ),
    ),
  ),


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
