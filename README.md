Soliant Consulting 
==================

Apigility for Doctrine
----------------------

This provides Apigility Module creation for Doctrine ORM and ODM.  API Modules created with this library's Admin code are supported by this library's Server code.  With this library you can turn a set of Doctrine entities into a full featured API.  No special configuration of your entities is needed.

Installation
------------
  1. edit `composer.json` file with following contents:

     ```json
    "require": {
        "soliantconsulting/soliantconsulting-apigility": "dev-master"
    }
     ```
  2. install composer via `curl -s http://getcomposer.org/installer | php` (on windows, download
     http://getcomposer.org/installer and execute it with PHP)
  3. run `php composer.phar install`


Creating the Apigility-enabled module
-------------------------------------

The Admin tool can create an Apigility-enabled module with the Doctrine entities in scope.
To enable the Admin include ```'SoliantConsulting\Apigility',``` in your 
development.config.php configuration.

All entities managed by the object manager will be available to build into apigility resources.  

Browse to ```/soliant-consulting/apigility/admin``` to begin.  On this page you will enter the name of a new module which does not already exist.  When the form is submitted
the module will be created.

The next page allows you to select entities from the object manager to build into 
resources.  You may change your object manager and refresh entities for that object
manager.  Check the entities you want then submit the form and you're done.  Your new module is enabled in your application and you can start making API requests.  


Hydrating Entities by Value or Reference
----------------------------------------

By default the admin tool hydrates entities by reference by setting `$config['zf-rest-doctrine-hydrator']['hydrator_class']['by_value']` to false.  


Handling Embedded Resources
---------------------------

You may choose to supress embedded resources by setting
`$config['zf-hal']['renderer']['render_embedded_resources']` to false.  Doing so
returns only links to embedded resources instead of their full details.
This setting is useful to avoid circular references.


Collections 
===========

The API created with this library implements full featured and paginated 
collection resources.  A collection is returned from a GET call to a entity endpoint without
specifying the id.  e.g. ```GET /api/data_module/entity/user_data```

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


Querying Collections
--------------------

Queries are not simple key=value pairs.  The query parameter is a key-less array of query 
definitions.  Each query definition is an array and the array values vary for each query type.

Each query type requires at a minimum a 'type' and a 'field'.  Each query may also specify
a 'where' which can be either 'and' or 'or'.  Embedded logic such as and(x or y) is not supported.

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
                'where': 'and',
                'type': 'between',
                'from': '1',
                'to': '100'
            },
            {
                'field': 'cycle',
                'where': 'and',
                'type': 'decimation',
                'value': '10'
            }
        ]
        },
        dataType: "json"
    });
});
```

Querying relations
---------------------
It is possible to query collections by relations - just supply the relation name as `fieldName` and
identifier as `value`.

For example, assuming we have defined 2 entities, `User` and `UserGroup`...

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
    $url = 'http://localhost:8081/api/db/entity/user';
    $query = http_build_query(array(
        array('type' => 'eq', 'field' => 'group', 'value' => '1')
    ));
````


Expanding Collections * in development *
---------------------

You may include in the _GET[extractCollections] an array of field names which are collections 
to return instead of a link to the collection.

```
    /api/user_data?extractCollections%5B0%5D=UserGroup
```



Available Query Types
---------------------

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

In

```php
    array('type' => 'in', 'field' => 'fieldName', 'values' => array(1, 2, 3))
```

NotIn

```php
    array('type' => 'notin', 'field' => 'fieldName', 'values' => array(1, 2, 3))
```

Like (% is used as a wildcard)

```php
    array('type' => 'like', 'field' => 'fieldName', 'value' => 'like%search')
```

Not Like (% is used as a wildcard)

```php
    array('type' => 'notlike', 'field' => 'fieldName', 'value' => 'notlike%search')
```

Between

```php
    array('type' => 'between', 'field' => 'fieldName', 'from' => 'startValue', 'to' => 'endValue')
````

Decimation (mod(field, value) = 0 e.g. value = 10 fetch one of every ten rows)

```php
    array('type' => 'decimation', 'field' => 'fieldName', 'value' => 'decimationModValue')
```
