Soliant Consulting Apigility Client 
===================================

*** The client is not up-to-date with the server and admin 11/26/2013 @TomHAnderson ***

Client uses the same Doctrine Entities as Server
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
