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

A list of all supported events:
```
EVENT_FETCH_POST = 'fetch.post';
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


Querying Collections
--------------------

Queries are not simple key=value pairs.  The query parameter is a key-less array of query
definitions.  Each query definition is an array and the array values vary for each query type.

Each query type requires at a minimum a 'type' and a 'field'.  Each query may also specify
a 'where' which can be either 'and' or 'or'.  Embedded logic such as and(x or y) is supported
through AndX and OrX query types.

Building HTTP GET query with PHP.  Use this to help build your queries.

PHP Example
```php
    echo http_build_query(
        array(
            'query' => array(
                array('field' =>'cycle', 'where' => 'and', 'type'=>'between', 'from' => 1, 'to'=>100),
                array('field'=>'cycle', 'where' => 'and', 'type' => 'decimation', 'value' => 10)
            ),
            'orderBy' => array('columnOne' => 'ASC', 'columnTwo' => 'DESC')
        )
    );
```

Javascript Example
```js
$(function() {
    $.ajax({
        url: "http://localhost:8081/api/db/entity/user_data",
        type: "GET",
        data: {
            'query': [
            {
                'field': 'cycle',
                'where': 'or',
                'type': 'between',
                'from': '1',
                'to': '100'
            },
            {
                'field': 'cycle',
                'where': 'or',
                'type': 'gte',
                'value': '1000'
            }
        ]
        },
        dataType: "json"
    });
});
```

Querying Relations
---------------------
It is possible to query collections by relations - just supply the relation name as `fieldName` and
identifier as `value`.

1. Using an RPC created by this module for each collection on each resource: /resource/id/childresource/child_id

2. Assuming we have defined 2 entities, `User` and `UserGroup`...

````php
/**
 * @Entity
 */
class User {
    /**
     * @ManyToOne(targetEntity="UserGroup")
     * @var UserGroup
     */
    protected $group;
}
````

````php
/**
 * @Entity
 */
class UserGroup {}
````

... we can find all users that belong to UserGroup id #1 with the following query:

````php
    $url = 'http://localhost:8081/api/user';
    $query = http_build_query(array(
        array('type' => 'eq', 'field' => 'group', 'value' => '1')
    ));
````


Format of Date Fields
---------------------

When a date field is involved in a query you may specify the format of the date
using PHP date formatting options.  The default date format is ```Y-m-d H:i:s```
If you have a date field which is just Y-m-d then add the format to the query.

```php
    'format' => 'Y-m-d',
    'value' => '2014-02-04',
```


Joining Entities and Aliasing Queries 
-------------------------------------

There is an available ORM Query Type for Inner Join so for every query type there is an optional ```alias```.
The default alias is 'row' and refers to the entity at the heart of the Rest resource.

This example joins the report field through the inner join already defined on the row entity then filters
for r.id = 2

```php
    array('type' => 'innerjoin', 'field' => 'report', 'alias' => 'r'),
    array('type' => 'eq', 'alias' => 'r', 'field' => 'id', 'value' => '2')
```

You can inner join tables from an inner join using parentAlias

```php
    array('type' => 'innerjoin', 'parentAlias' => 'r', 'field' => 'owner', 'alias' => 'o'),
```

The InnerJoin Query Type is not enabled by default.  
To enable it add this to your configuration (e.g. ```config/autoload/global.php```)

```
    'zf-orm-collection-filter' => array(
        'invokables' => array(
            'innerjoin' => 'ZF\Apigility\Doctrine\Server\Collection\Filter\ORM\InnerJoin',
        ),
    ),
```

To disable any filters do the same but set the value to null

```
    'zf-orm-collection-filter' => array(
        'invokables' => array(
            'notlike' => null,
        ),
    ),
```

Available Query Types
---------------------

ORM and ODM

Equals

```php
    array('type' => 'eq', 'field' => 'fieldName', 'value' => 'matchValue')
```

Not Equals

```php
    array('type' => 'neq', 'field' => 'fieldName', 'value' => 'matchValue')
```

Less Than

```php
    array('type' => 'lt', 'field' => 'fieldName', 'value' => 'matchValue')
```

Less Than or Equals

```php
    array('type' => 'lte', 'field' => 'fieldName', 'value' => 'matchValue')
```

Greater Than

```php
    array('type' => 'gt', 'field' => 'fieldName', 'value' => 'matchValue')
```

Greater Than or Equals

```php
    array('type' => 'gte', 'field' => 'fieldName', 'value' => 'matchValue')
```

Is Null

```php
    array('type' => 'isnull', 'field' => 'fieldName')
```

Is Not Null

```php
    array('type' => 'isnotnull', 'field' => 'fieldName')
```

Dates in the In and NotIn filters are not handled as dates.
It is recommended you use multiple Equals statements instead of these
filters.

In

```php
    array('type' => 'in', 'field' => 'fieldName', 'values' => array(1, 2, 3))
```

NotIn

```php
    array('type' => 'notin', 'field' => 'fieldName', 'values' => array(1, 2, 3))
```

Between

```php
    array('type' => 'between', 'field' => 'fieldName', 'from' => 'startValue', 'to' => 'endValue')
````

Like (% is used as a wildcard)

```php
    array('type' => 'like', 'field' => 'fieldName', 'value' => 'like%search')
```


ORM Only
--------

AndX 

In AndX queries the ```conditions``` is an array of query types for any of those described
here.  The join will always be ```and``` so the ```where``` parameter inside of conditions is
ignored.  The ```where``` parameter on the AndX query type is not ignored.

```php
array(
    'type' => 'andx',
    'conditions' => array(
        array('field' =>'name', 'type'=>'eq', 'value' => 'ArtistOne'),
        array('field' =>'name', 'type'=>'eq', 'value' => 'ArtistTwo'),
    ),
    'where' => 'and'
)
```

OrX 

In OrX queries the ```conditions``` is an array of query types for any of those described
here.  The join will always be ```or``` so the ```where``` parameter inside of conditions is
ignored.  The ```where``` parameter on the OrX query type is not ignored.

```php
array(
    'type' => 'orx',
    'conditions' => array(
        array('field' =>'name', 'type'=>'eq', 'value' => 'ArtistOne'),
        array('field' =>'name', 'type'=>'eq', 'value' => 'ArtistTwo'),
    ),
    'where' => 'and'
)
```


ODM Only
--------

Regex

```php
    array('type' => 'regex', 'field' => 'fieldName', 'value' => '/.*search.*/i')
```
