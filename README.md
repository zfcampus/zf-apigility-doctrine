Soliant Consulting 
==================

Apigility for Doctrine
----------------------

This library has three parts.  

1. An Admin to create Apigility-enabled modules with support for all 
Doctrine Entities.

2. An API Server AbstractService class to handle API interactions from resources
created with the Admin tool.

3. An API Client [Object Relational Mapper]
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
  4. If you are using the server enable ```SoliantConsulting/Apigility``` module in ```development.config.php``` or ```application.config.php```


Admin
-----

This tool will create a set of Apigility resources for the entities you specify.  You 
must run this tool inside an application which already has Doctrine entities managed 
by an object manager.  

Admin supports multiple object managers by alias.  To use this resource creation tool
you must include ```SoliantConsulting\Apigility``` in your ```application.config.php```
or ```development.config.php```.  Then browse to the route ```/soliant-consulting/apigility/admin```
to create your resources.

All entities managed by any object manager will be available to build into a resource, one 
object manager at a time.  The resources created by the Admin use the Server to do their work.


Server
------

The Server portion is one abstract resource file.  All
generated resources are extended from this abstract.  The abstract supports 
GET, POST, PUT, and DELETE requests.

A GET request to /api/entity_name/1 will return the matching record for EntityName id = 1

A GET request to /api/entity_name will return a collection.


Collections 
--------------------

The API created with this library implements full featured and paginated 
collection resources returned with a call like /api/entity_name

Reserved Words

```
    _page
    _limit
    _orderBy
```

Return a page of the first five results

```
    /api/entity_name?_page=0&_limit=5
```

Return results six through ten

```
    /api/entity_name?_page=1&_limit=5
```

Sort by columnOne ascending

```
    /api/entity_name?_orderBy%5BcolumnOne%5D=ASC
```

Sort by columnOne ascending then columnTwo decending

```
    /api/entity_name?_orderBy%5BcolumnOne%5D=ASC&_orderBy%5BcolumnTwo%5D=DESC
```


Filtering Data
-------------

Any field passed in the GET to a collection resource is added to the query by name.  
Note these are field names, not database column names.

```
    /api/entity_name?user=1
```

See TODO


Doctrine Entity Configuration
-----------------------

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


Client
------

The Client is a Doctrine Object Relational Mapper based on the Doctrine Common library.
It works by using the same copy of your entities used to create your Server resources.
This is best accomplished by keeping each Doctrine connection entities in their own 
module e.g. Db.  This common entity module is then used in the API application and the
client application.


The ```ocramius/proxy-manager``` library is used to make all client to api 
interactivity lazy load, including individual find() calls and collections.


Client Configuration
--------------------

For each client add a service factory to the Application module's setServiceConfig.  
Each client only supports one entity manager.

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
        $artifact = $objectManager->find('EntityModule\Entity\Artifact', 1);

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

$collection = new Collection($objectManager, 'Db\Entity\DataCaptureType');
```

The collection is lazy loaded.  These functions will reset the collection

```
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
    $collection = new Collection($objectManager, 'Db\Entity\DataCaptureType');

    $collection->setLimit(10);
    for ($page = 0; $page <= 4; $page++) {
        $collection->setPage($page); 

        foreach ($collection as $entity) {
            echo $entity->getName();    
        }
    }
```


TODO Short Term
===============

TODO: Complex Query - Find a format which supports doctrine query builder more completely.

```
    user = array(
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
