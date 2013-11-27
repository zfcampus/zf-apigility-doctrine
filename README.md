Soliant Consulting 
==================

Apigility for Doctrine
----------------------

This library has three parts.  

1. An Admin tool to create an Apigility-enabled module with support for all 
Doctrine Entities in scope.

2. An API server AbstractService class to handle API interactions from resources
created with the Admin tool.

3. An API client [Object Relational Mapper]
(https://en.wikipedia.org/wiki/Object-relational_mapping) based on the 
[Doctrine Common](http://www.doctrine-project.org/projects/common.html) 
project library which works in tandem with a Doctrine ORM class to make interacting
with your entities seemless across the API.  See the README.CLIENT.md


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

This documents the reqiurements of your entities to work with this library.  
The ArraySerializable hydrator is used by default.

```
public function getArrayCopy() 
{
    return array(
        'id' => $this->getId(),
        'anotherField' => $this->getAnotherField(),
        'referenceToAnotherEntity' => $this->getReferenceToAnotherEntity(),
    );
}

public function exchangeArray($data) 
{
    $this->setAnotherField(isset($data['anotherField']) ? $data['anotherField']: null);
    $this->setReferenceToAnotherEntity(isset($data['referenceToAnotherEntity']) ? $data['referenceToAnotherEntity']: null);
}

public function setId($value) 
{
    $this->id = $value;
}
```

It is important the id is not in exchangeArray and is in getArrayCopy.  
All fields and references need to be in both functions.  Collections
such as many to one relationships are in neither function.  

```setId($value)``` is generally not implemented by traditional Doctrine entity design
but if using the Client and because ArraySerializable hydration is used and becuase 
setting the id in exchangeArray() is not advised, this setter is required.

At the time of this writing Doctrine many-to-many relationships are not supported by this
library.


Creating the Apigility-enabled module
-------------------------------------

The Admin tool can create an Apigility-enabled module with the Doctrine entities in scope.
To enable the Admin include ```'SoliantConsulting\Apigility',``` in your 
development.config.php configuration.

All entities managed by the object manager will be available to build into a resource.  

Browse to ```/soliant-consulting/apigility/admin``` to begin.  On this page you will enter 
the name of a new module which does not already exist.  When the form is submitted
the module will be created.

The next page allows you to select entities from the object manager to build into 
resources.  You may change your object manager and refresh entities for that object
manager.  Check the entities you want then submit the form.

Done.  Your new module is enabled in your application and you can start making API 
requests.  

The route for an entity named DataModule\Entity\UserData is
```/api/user_data``` or, if using namespaced routes, ```/api/data_module/entity/user_data``` 
After going through the above process your API should be working.


Handling Embedded Resources
---------------------------

The Apigility-enabled module sets the zf-hal renderer configuration variable ```render_embedded_resources```
to false.  This supresses all embedded resource details and only returns their _links[self][href].  This
is used in the Client for lazy loading and may be turned on based on your needs.

```php
    'zf-hal' => array(
        'renderer' => array(
            'default_hydrator' => 'ArraySerializable',
            'render_embedded_resources' => '',
        ),
```

Collections 
===========

The API created with this library implements full featured and paginated 
collection resources.  A collection is returned from a GET call to a entity endpoint without
specifying the id.  e.g. ```GET /api/data_module/entity/user_data```


Direct API Calls 
----------------

Reserved Words

```
    page
    limit
    orderBy
    query
```

Return a page of the first five results

```
    /api/user_data?page=0&limit=5
```

Return results six through ten

```
    /api/user_data?page=1&limit=5
```

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
    echo http_buildquery(
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
