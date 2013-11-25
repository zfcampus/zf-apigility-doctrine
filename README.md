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
with your entities seemless across the API.


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


Collections 
===========

The API created with this library implements full featured and paginated 
collection resources.  A collection is returned from a GET call to a entity endpoint without
specifying the id.  e.g. ```GET /api/data_module/entity/user_data```


Direct API Calls 
----------------

Reserved Words

```
    _page
    _limit
    _orderBy
    _query
```

Return a page of the first five results

```
    /api/user_data?_page=0&_limit=5
```

Return results six through ten

```
    /api/user_data?_page=1&_limit=5
```

Sort by columnOne ascending

```
    /api/user_data?_orderBy%5BcolumnOne%5D=ASC
```

Sort by columnOne ascending then columnTwo decending

```
    /api/user_data?_orderBy%5BcolumnOne%5D=ASC&_orderBy%5BcolumnTwo%5D=DESC
```


Querying Collections
--------------------

Queries are not simple key=value pairs.  The _query parameter is a key-less array of query 
definitions.  Each query definition is an array and the values vary for each query type.

Each query type requires at a minimum the 'type' and a 'field'.  Each query may also specify
a 'where' which can be either 'and' or 'or'.  

*** The goal of querying data is to mirror the doctrine query builder plus custom query solutions ***

Building HTTP GET query with PHP.  Use this to help build your queries.

```php
    echo http_build_query(
        array(
            '_query' => array(
                array('field' => '_DatasetID', 'where' => 'and', 'type' => 'eq' , 'value' => 1),
                array('field' =>'Cycle_number', 'where' => 'and', 'type'=>'between', 'from' => 10, 'to'=>100),
                array('field'=>'Cycle_number', 'where' => 'and', 'type' => 'decimation', 'value' => 10)
            ),
            '_orderBy' => array('columnOne' => 'ASC', 'columnTwo' => 'DESC')
        )
    );
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

```
    array('type' => 'lt', 'field' => 'fieldName', 'value' => 'matchValue')
```

Less Than or Equals

```
    array('type' => 'lte', 'field' => 'fieldName', 'value' => 'matchValue')
```

Greater Than

```
    array('type' => 'gt', 'field' => 'fieldName', 'value' => 'matchValue')
```

Less Than or Equals

```
    array('type' => 'gte', 'field' => 'fieldName', 'value' => 'matchValue')
```

Is Null

```
    array('type' => 'isnull', 'field' => 'fieldName')
```

Is Not Null

```
    array('type' => 'isnotnull', 'field' => 'fieldName')
```

In

```
    array('type' => 'in', 'field' => 'fieldName', 'values' => array(1, 2, 3))
```

NotIn

```
    array('type' => 'notin', 'field' => 'fieldName', 'values' => array(1, 2, 3))
```

Like

```
    array('type' => 'like', 'field' => 'fieldName', 'value' => 'like%search')
```

Not Like

```
    array('type' => 'notlike', 'field' => 'fieldName', 'value' => 'notlike%search')
```

Between

```php
    array('type' => 'between', 'field' => 'fieldName', 'from' => 'startValue', 'to' => 'endValue')
````

Decimation

```php
    array('type' => 'decimation', 'field' => 'fieldName', 'value' => 'decimationModValue')
```


Client uses the same Doctrine Entities
--------------------------------------------------------

Your API client must have a copy of the same Entity code base as the server 
and as the Admin module used to build the Apigility-enabled module, if you
choose to use the Client.

This is best accomplished by creating a distinct module for your entities and 
repositories then requiring this repository from composer.


Client uses proxy objects for lazy loading
----------------------------------------------

The ```ocramius/proxy-manager``` library is used to make all api interactivity lazy load,
including individual find() calls and collections.


Client Configuration
--------------------

Add a service factory for the client to the Application module's setServiceConfig

```
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'apigility_client' => function($serviceManager) {
                    $objectManager = new ObjectManager;

                    # no trailing slash
                    $objectManager->setBaseUrl('http://localhost:8079/api');  
                    
                    // The entity manager you set here should not connect 
                    // to your database.  It is used by the objectManager 
                    // to introspect entities at run time.  Because the 
                    // client is not an Doctrine Entity Manager it doesn't 
                    // have the entity metadata available to it.
                    $objectManager->setEntityManager(
                        $serviceManager->get('doctrine.entitymanager.orm_default')
                    );
                    
                    $objectManager->setHttpClient(new HttpClient(null, array('keepalive' => true)));
                    $objectManager->setCache(StorageFactory::adapterFactory('memory'));

                    return $objectManager;
                },
```

To fetch the object manager in a controller:
```
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function testingAction()
    {
        $objectManager = $this->getServiceLocator()->get('apigility_client');
        $artifact = $objectManager->find('EntityModule\Entity\Artifact', 36);

        return array(
            'artifact' => $artifact,
        );
    }
}
```

Use the $artifact in a view as you would a local Doctrine entity

```
The artifact id is <?= $artifact->getId(); ?><br>
Name: <?= $this->escapeHtml($artifact->getName()); ?><br>
Artifact Type Name: <?= $this->escapeHtml($artifact->getArtifactType()->getName()); ?><br>
Vendor Name: <?= $this->escapeHtml($artifact->getVendor()->getName()); ?>

<BR><BR>

<?php

$artifacts = $artifact->getVendor()->getArtifacts();
foreach ($artifacts as $a) {
    echo "<BR><BR> Artifact Name:" . $a->getName();
}
```


Client Collection
=================

A collection is returned from the object manager any time a collection is requested such 
as ```$artifact->getReferencedData();```

To query for a collection directly create a collection as:

```
use SoliantConsulting\Apigility\Client\Collections\RelationCollection as Collection;

$collection = new Collection($objectManager, 'DbLoadCd\Entity\DataCaptureType');
```

When you first receive a collection as a result of an artifact function the entities will be populated.
When you create a collection from scratch entities will not be populated.

These functions will reset the collection

```php
    $collection->setPage(0);
    $collection->setLimit(10);
    $collection->addFilter('id', 100);
    $collection->setQuery(array());
    $collection->setOrderBy(array(
        'name' => 'ASC'
    ));
```

```
    setPage(#)
``` 

sets the page of results to return based on ```setLimit(#)``` 
so if yo call setLimit(10) and setPage(2) the collection will return results 31-40.

```
    addFilter(field, value)
``` 

is used internally to set persistant filters.  Filters are not
reset between collection api calls and cannot be modified.

```
    setQuery(array(field => value))
``` 

can be modified and are not reset between api calls

```
    setOrderBy(array(field => sort))
``` 

sets the order to return results.

After any of these calls are made the collection resets itself.  This allows loooping.  This example 
echos 50 results.

```
    $collection = new Collection($objectManager, 'DbLoadCd\Entity\DataCaptureType');

    $collection->setLimit(10);
    for ($page = 0; $page <= 4; $page++) {
        $collection->setPage($page); 

        foreach ($collection as $entity) {
            echo $entity->getName();    
        }
    }
```


TODO Medium Term
================
Fix the client to work with the changes we've made in the server


TODO Long Term
==============

Add optional support for DoctrineEntity hydrator.
