Apigility for Doctrine
==============================

[![Build status](https://api.travis-ci.org/zfcampus/zf-apigility-doctrine.svg)](http://travis-ci.org/zfcampus/zf-apigility-doctrine)

This module provides the classes for integrating Doctrine with Apigility.

Installation
------------

Installation of this module uses composer. For composer documentation, please refer to
[getcomposer.org](http://getcomposer.org/).

```sh
$ php composer.phar require zfcampus/zf-apigility-doctrine:dev-master
```

This library provides two modules.  The first, ZF\Apigility\Doctrine\Server provides
the classes to serve data created by the second, ZF\Apigility\Doctrine\Admin.  The
Admin module is used to create apigility resources and the Server serves those
created resoruces.  Generally you would include Admin in your development.config.php
and Server in your application.config.php


API Resources
-------------


***/apigility/api/doctrine[/:object_manager_alias]/metadata[/:name]***

This will return metadata for the named entity which is a member of the
named object manager.  Querying without a name will return all metadata
for the object manager.


***/apigility/api/module[/:name]/doctrine[/:controller_service_name]***

This is a Doctrine resource creation route like Apigility Rest `/apigility/api/module[/:name]/rest[/:controller_service_name]`
POST Parameters:

```json
{
    "objectManager": "doctrine.entitymanager.orm_default",
    "resourceName": "Artist",
    "entityClass": "Db\\Entity\\Artist",
    "routeIdentifierName": "artist_id",
    "entityIdentifierName": "id",
    "routeMatch": "/api/artist",
    "pageSizeParam": "limit", // optional default null
    "hydratorName": "DbApi\\V1\\Rest\\Artist\\ArtistHydrator", // Optional default generated
    "hydrateByValue": true // Optional default true
}
```


Hydrating Entities by Value or Reference
----------------------------------------

By default the admin tool hydrates entities by reference by setting `$config['doctrine-hydrator']['hydrator_class']['by_value']` to false.


Custom Events
=============
It is possible to hook in on specific doctrine events of the type `DoctrineResourceEvent`.
This way, it is possible to alter the doctrine entities or collections before or after a specific action is performed.
The EVENT_FETCH_ALL_PRE works on the Query Builder from the fetch all query.  This allows you to modify the Query Builder before a fetch is performed.

A list of all supported events:
```
EVENT_FETCH_POST = 'fetch.post';
EVENT_FETCH_ALL_PRE = 'fetch-all.pre';
EVENT_FETCH_ALL_POST = 'fetch-all.post';
EVENT_CREATE_PRE = 'create.pre';
EVENT_CREATE_POST = 'create.post';
EVENT_UPDATE_PRE = 'update.pre';
EVENT_UPDATE_POST = 'update.post';
EVENT_PATCH_PRE = 'patch.pre';
EVENT_PATCH_POST = 'patch.post';
EVENT_DELETE_PRE = 'delete.pre';
EVENT_DELETE_POST = 'delete.post';
```

The EventManager is available through the StaticEventManager:

```php
StaticEventManager::getInstance()->attach('ZF\Apigility\Doctrine\DoctrineResource', 'create.post', $callable);
```

It is also possible to add custom event listeners to the configuration of a single doctrine-connected resource:
```php
'zf-apigility' => array(
    'doctrine-connected' => array(
        'Api\\V1\\Rest\\User\\UserResource' => array(
            // ...
            'listeners' => array(
                'key.of.aggregate.listener.in.service_manager'
            ),
        ),
    ),
),
```

Querying Single Entities
========================

Multi-keyed entities
--------------------

You may delimit multi keys through the route parameter.  The default
delimter is a period . (e.g. 1.2.3).  You may change the delimiter by
setting the DoctrineResource::setMultiKeyDelimiter($value)


Complex queries through route parameters
----------------------------------------

You may specify multiple route parameters and as long as the route
matches then the route parameter names will be matched to the entity.

For instance, a route of ```/api/artist/:artist_id/album/:album_id``` mapped to the Album
entity will filter the Album for field names.  So, given an album with id, name, and artist
fields the album_id matches to the resoruce configuration and will be queried by key
and the artist is a field on album and will be queried by criteria so the final query
would be

```
$objectManager->getRepository('Album')->findOneBy(
    'id' => :album_id,
    'artist' => :artist_id
);
```

The album(_id) is not a field on the Album entity and will be ignored.


Collections
===========

The API created with this library implements full featured and paginated
collection resources.  A collection is returned from a GET call to an entity endpoint without
specifying the id.  e.g. ```GET /api/resource```

Reserved GET variables

```
    orderBy
    query
```

Providing a base query
----------------------

This module uses an empty Doctrine query-builder to create the base query for a collection.
In some cases you want to have more control over the query-builder.
For example: When using soft deletes, you want to make sure that the end-user can only see the active records.
Therefore it is also possible to use your own query provider.

A custom plugin manager is available to register your own query providers.
This can be done through following configuration:

```php
'zf-collection-query' => array(
    'invokables' => array(
        'custom-query-provider' => 'Application\My\Custom\QueryProvider',
    )
),
```

You have to make sure that this registered class implements the `ApigilityFetchAllQuery` interface.
When the query provider is registered, you have to attach it to the doctrine-connected resource configuration:
```php
'zf-apigility' => array(
    'doctrine-connected' => array(
        'Api\\V1\\Rest\\....' => array(
            'query_provider' => 'custom-query-provider',
        ),
    ),
),
```

Sorting Collections
-------------------

Sort by columnOne ascending

```
    /api/user_data?orderBy%5BcolumnOne%5D=ASC
```

Sort by columnOne ascending then columnTwo decending

```
    /api/user_data?orderBy%5BcolumnOne%5D=ASC&orderBy%5BcolumnTwo%5D=DESC
```


