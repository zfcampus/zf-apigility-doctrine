Doctrine in Apigility
==============================

[![Build status](https://api.travis-ci.org/zfcampus/zf-apigility-doctrine.svg)](http://travis-ci.org/zfcampus/zf-apigility-doctrine)
[![Total Downloads](https://poser.pugx.org/zfcampus/zf-apigility-doctrine/downloads)](https://packagist.org/packages/zfcampus/zf-apigility-doctrine)

This module provides the classes for integrating Doctrine with Apigility.

Installation
------------

Installation of this module uses composer. For composer documentation, please refer to
[getcomposer.org](http://getcomposer.org/).

```console
$ composer require zfcampus/zf-apigility-doctrine
```

This library provides two modules.  The first, `ZF\Apigility\Doctrine\Server` provides
the classes to serve data created by the second, `ZF\Apigility\Doctrine\Admin`.  The
*Admin* module is used to create apigility resources and the Server serves those
created resoruces.  Generally you would include *Admin* in your `development.config.php`
and *Server* in your `application.config.php`

`ZF\Apigility\Doctrine\Server` has a dependency with `Phpro\DoctrineHydrationModule` to handle entity hydration. See [documentation and instructions](https://github.com/phpro/zf-doctrine-hydration-module) on how to set up this module.

For Apache installations it is recommended the [AllowEncodedSlashes-directive is set to On](http://httpd.apache.org/docs/2.4/mod/core.html#allowencodedslashes) so the configuration can be read.

API Resources
-------------

**NOTE!**  This section was/is intended for the authors of [zf-apigility-admin-ui](https://github.com/zfcampus/zf-apigility-admin-ui).  While it is possible to use these instructions to manually create Apigility Doctrine resources it is strongly recommended to use the UI.


`/apigility/api/doctrine[/:object_manager_alias]/metadata[/:name]`

This will return metadata for the named entity which is a member of the
named object manager. Querying without a name will return all metadata
for the object manager.


`/apigility/api/module[/:name]/doctrine[/:controller_service_name]`

This is a Doctrine resource route _like_ Apigility Rest `/apigility/api/module[/:name]/rest[/:controller_service_name]`
To create a resource do not include `[/:controller_service_name]`

POST Parameters
```json
{
    "objectManager": "doctrine.entitymanager.orm_default",
    "serviceName": "Artist",
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

Supported events:
```
EVENT_FETCH_PRE = 'fetch.pre';
EVENT_FETCH_POST = 'fetch.post';
EVENT_FETCH_ALL_PRE = 'fetch-all.pre';
EVENT_FETCH_ALL_POST = 'fetch-all.post';
EVENT_CREATE_PRE = 'create.pre';
EVENT_CREATE_POST = 'create.post';
EVENT_UPDATE_PRE = 'update.pre';
EVENT_UPDATE_POST = 'update.post';
EVENT_PATCH_PRE = 'patch.pre';
EVENT_PATCH_POST = 'patch.post';
EVENT_PATCH_LIST_PRE = 'patch-all.pre';
EVENT_PATCH_LIST_POST = 'patch-all.post';
EVENT_DELETE_PRE = 'delete.pre';
EVENT_DELETE_POST = 'delete.post';
EVENT_DELETE_LIST_PRE = 'delete-list.pre';
EVENT_DELETE_LIST_POST = 'delete-list.post';
```

Attach to events through the *Shared Event Manager*:

```php
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;

$sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();

$sharedEvents->attach(
    'ZF\Apigility\Doctrine\DoctrineResource',
    DoctrineResourceEvent::EVENT_CREATE_PRE,
    function(DoctrineResourceEvent $e) {
        $e->stopPropagation();
        return new ApiProblem(400, 'Stop API Creation');
    }
);
```

It is also possible to add custom event listeners to the configuration of a single doctrine-connected resource:
```php
'zf-apigility' => [
    'doctrine-connected' => [
        'Api\\V1\\Rest\\User\\UserResource' => [
            // ...
            'listeners' => [
                'key.of.aggregate.listener.in.service_manager',
            ],
        ],
    ],
],
```

Querying Single Entities
========================

Multi-keyed entities
--------------------

You may delimit multi keys through the route parameter.  The default
delimiter is a period . (e.g. 1.2.3).  You may change the delimiter by
setting the DoctrineResource::setMultiKeyDelimiter($value)


Complex queries through route parameters
----------------------------------------

NO LONGER SUPPORTED.  As of version 2.0.4 this functionality has been removed from
this module.  The intended use of this module is a 1:1 mapping of entities to resources
and using subroutes is not in the spirit of this intention.  It is STRONGLY recommended
you use [zfcampus/zf-doctrine-querybuilder](https://github.com/zfcampus/zf-doctrine-querybuilder)
for complex query-ability.



Query Providers
===============

Query Providers are available for all find operations.  The find query provider is used to fetch an entity before it is acted upon for all *DoctrineResource* methods except create.

A query provider returns a *QueryBuilder* object.  By using a custom query provider you may inject conditions specific to the resource or user without modifying the resource.  For instance, you may add a ```$queryBuilder->andWhere('user = ' . $event->getIdentity());``` in your query provider before returning the *QueryBuilder* created therein.  Other uses include soft deletes so the end user can only see the active records.

A custom plugin manager is available to register your own query providers.  This can be done through this configuration:

```php
'zf-apigility-doctrine-query-provider' => [
    'aliases' => [
        'entity_name_fetch_all' => \Application\Query\Provider\EntityName\FetchAll::class,
    ],
    'factories' => [
        \Application\Query\Provider\EntityName\FetchAll::class => \Zend\ServiceManager\Factory\InvokableFactory::class,
    ],
],
```

When the query provider is registered attach it to the doctrine-connected resource configuration.  The default query provider is used if no specific query provider is set.  You may set query providers for these keys:

* default
* fetch
* fetch_all
* update
* patch
* delete

```php
'zf-apigility' => [
    'doctrine-connected' => [
        'Api\\V1\\Rest\\....' => [
            'query_providers' => [
                'default' => 'default_orm',
                'fetch_all' => 'entity_name_fetch_all',
                // or fetch, update, patch, delete
            ],
        ],
    ],
],
```

Query Create Filters
==============

In order to filter or change data sent to a create statement before it is used to hydrate the entity you may use a query create filter.  Create filters are very similar to *Query Providers* in their implementation.

Create filters take the data as a parameter and return the data, modified or filtered.

A custom plugin manager is available to register your own create filters.  This can be done through following configuration:

```php
'zf-apigility-doctrine-query-create-filter' => [
    'aliases' => [
        'entity_name' => \Application\Query\CreateFilter\EntityName::class,
    ],
    'factories' => [
        \Application\Query\CreateFilter\EntityName::class => \Zend\ServiceManager\Factory\InvokableFactory::class,
    ],
],
```

Register your Query Create Filter as:
```php
'zf-apigility' => [
    'doctrine-connected' => [
        'Api\\V1\\Rest\\....' => [
            'query_create_filter' => 'entity_name',
            ...
        ],
    ],
],
```
