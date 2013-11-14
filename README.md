Soliant Consulting 
==================

Apigility for Doctrine
----------------------

This library has three parts.  

1. An Admin tool to create an Apigility-enabled module with support for all 
Doctrine Entities in scope.

2. An API server AbstractService class to handle API interations from resources
created with the Admin tool.

3. An API client [Object Relational Mapper]
(https://en.wikipedia.org/wiki/Object-relational_mapping) based on the 
[Doctrine Common](http://www.doctrine-project.org/projects/common.html) 
project library which works in tandem with a Doctrine ORM class to make interacting
with your entities seemless across the API.


All parts use common Doctrine Entities
--------------------------------------------------------

Your API client must have a copy of the same Entity code base as the server 
and as the Admin module used to build the Apigility-enabled module.

This is best accomplished by creating a distinct module for your entities and 
repositories then requiring this repository from composer.


Everything uses proxy objects for lazy loading
----------------------------------------------

The ```ocramius/proxy-manager``` library is used to make all api interactivity lazy load,
including individual find() calls and collections.


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

Before you begin configure your application with Doctrine entity support and be
sure you can connect to the database.  All entities 
managed by the object manager will be available to build into a resource.  

Browse to ```/soliant-consulting/apigility/admin``` to begin.  On this page you will enter 
the name of a new module which does not already exist.  When the form is submitted
the module will be created.

The next page allows you to select entities from the object manager to build into 
resources.  Check those you want then submit the form.

Done.  Your new module is enabled in your application and you can start making API 
requests.  

The route for an entity named DataModule\Entity\UserData is
```/api/user_data``` and after going through the above process your API should be working.


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

                    $objectManager->setBaseUrl('http://localhost:8079/api');  # no trailing slash
                    
                    // The entity manager you set here should not connect to your database.  It is used
                    // by the objectManager to introspect entities at run time.  Because the client is not
                    // an Doctrine Entity Manager it doesn't have the entity metadata available to it.
                    $objectManager->setEntityManager($serviceManager->get('doctrine.entitymanager.orm_default'));
                    
                    $objectManager->setHttpClient(new HttpClient(null, array('keepalive' => true)));
                    $objectManager->setCache(StorageFactory::adapterFactory('memory'));

                    return $objectManager;
                },
```

To fetch the object manager is a controller:
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

Collections 
===========

The API created with this library implements full featured and paginated 
collection resources.

Direct API Calls 
----------------

Reserved Words

```
_page
_limit
_orderBy
```

Return a page of the first five results

```/api/user_data?_page=0&_limit=5```

Return results six through ten

```/api/user_data?_page=1&_limit=5```

Sort by columnOne ascending

```/api/user_data?_orderBy%5BcolumnOne%5D=ASC```

Sort by columnOne ascending then columnTwo decending

```/api/user_data?_orderBy%5BcolumnOne%5D=ASC&_orderBy%5BcolumnTwo%5D=DESC```


Querying Data
-------------

Simple Query 

Any field passed in the GET to a collection resource is added to the query by name

```/api/user_data?user_id=1```


Collection Calls
----------------

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

```
$collection->setPage(0);
$collection->setLimit(10);
$collection->addFilter('id', 100);
$collection->setQuery(array());
$collection->setOrderBy(array(
    'name' => 'ASC'
));
```

```setPage(#)``` sets the page of results to return based on ```setLimit(#)``` 
so if yo call setLimit(10) and setPage(2) the collection will return results 31-40.

```addFilter(field, value)``` is used internally to set persistant filters.  Filters are not
reset between collection api calls and cannot be modified.

```setQuery(array(field => value))``` can be modified and are not reset between api calls```

```setOrderBy(array(field => sort))``` sets the order to return results.

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


TODO Short Term
===============

Add update and delete to the object manager.

TODO: Complex Query - Find a format which supports doctrine query builder more completely.

```
user_id = array(
    'comparator' => 'EQ, LT, GT', // See Doctrine Query Builder documentation
    and
    'values' => array(1,2,3),
    or
    'rangeStart' => 3,
    'rangeEnd' => 5,
```


TODO Long Term
==============

Add optional support for DoctrineEntity hydrator.