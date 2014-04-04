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


Filtering Collections
--------------------

Filters are not simple key=value pairs.  The query parameter is a key-less array of filter
definitions.  Each filter definition is an array and the array values vary for each filter.

Each filter requires at a minimum a 'type'.  Each filter may also specify
a ```where``` which can be either ```and``` or ```or```.  Embedded logic such as and(x or y) is supported
through AndX and OrX filters.

Building HTTP GET query with PHP.  Use this to help build your queries.
-----------------------------------------------------------------------

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

Filtering Relations
---------------------
It is possible to filter collections by relations - just supply the relation name as `fieldName` and
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

When a date field is involved in a filter you may specify the format of the date
using PHP date formatting options.  The default date format is ```Y-m-d H:i:s```
If you have a date field which is just Y-m-d then add the format to the filter.

```php
    'format' => 'Y-m-d',
    'value' => '2014-02-04',
```

Available Filters
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

In AndX queries the ```conditions``` is an array of filters for any of those described
here.  The join will always be ```and``` so the ```where``` parameter inside of conditions is
ignored.  The ```where``` parameter on the AndX filter is not ignored.

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

In OrX queries the ```conditions``` is an array of filters for any of those described
here.  The join will always be ```or``` so the ```where``` parameter inside of conditions is
ignored.  The ```where``` parameter on the OrX filter is not ignored.

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

AndX and OrX are infinitly embeddable

```php
array(
    'type' => 'orx',
    'conditions' => array(
        array('field' =>'name', 'type'=>'eq', 'value' => 'ArtistOne'),
        array(
            'type' => 'andx',
            'conditions' => array(
                array(
                    'type' => 'orx', 
                    'conditions' => array(                    
                        array('field' =>'name', 'type'=>'eq', 'value' => 'ArtistTwo'),
                        array('field' =>'name', 'type'=>'like', 'value' => 'Artist%'),
                    ),
                ),
                array('field' =>'createdAt', 'type'=>'eq', 'value' => '2014-12-18 13:17:17'),
            ),
        ),
    ),
)
```


ODM Only
--------

Regex

```php
    array('type' => 'regex', 'field' => 'fieldName', 'value' => '/.*search.*/i')
```
