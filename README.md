Apigility for Doctrine - Admin
==============================

Installation
------------

Installation of this module uses composer. For composer documentation, please refer to
[getcomposer.org](http://getcomposer.org/).

```sh
$ php composer.phar require zfcampus/zf-apigility-doctrine-admin:dev-master
```

Add `ZF\Apigility\Doctrine\Admin` and `ZF\Apigility\Doctrine\Server` to your `modules`


API Resources
-------------

***/admin/api/module[/:name]/doctrine[/:controller_service_name]***

This is a Doctrine resource creation route like Apigility Rest `/admin/api/module[/:name]/rest[/:controller_service_name]`

POST Parameters
```json
{
    "objectManager": "doctrine.entitymanager.orm_default",
    "resourceName": "Artist",
    "entityClass": "Db\\Entity\\Artist",
    "pageSizeParam": "limit",
    "routeIdentifierName": "artist_id",
    "entityIdentifierName": "id",
    "routeMatch": "/api/artist",
    "hydratorName": "DbApi\\V1\\Rest\\Artist\\ArtistHydrator",
    "hydrateByValue": true
}
```


***/admin/api/doctrine[/:object_manager_alias]/metadata[/:name]***

This will return metadata for the named entity which is a member of the
named object manager.  Querying without a name will return all metadata
for the object manager.


Hydrating Entities by Value or Reference
----------------------------------------

By default the admin tool hydrates entities by reference by setting `$config['zf-rest-doctrine-hydrator']['hydrator_class']['by_value']` to false.  


Handling Embedded Resources
---------------------------

You may choose to supress embedded resources by setting
`$config['zf-hal']['renderer']['render_embedded_resources']` to false.  Doing so
returns only links to embedded resources instead of their full details.
This setting is useful to avoid circular references.  This was created during the
development of this module, although it is part of HAL it is documented here for safe keeping.
