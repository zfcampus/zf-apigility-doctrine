# Allow Non-Doctrine Entity Identifier Fields as Resource Identifier(s)

When finding the target entity the identifiers were limited to the entity metadata identifier fields.  This change makes the API much more flexible by allowing standard field(s) as the identifier.

```
    'zf-rest' => array(
        'ZFTestApigilityDbApi\\V1\\Rest\\ArtistByName\\Controller' => array(
            'listener' => 'ZFTestApigilityDbApi\\V1\\Rest\\ArtistByName\\ArtistByNameResource',
            'route_name' => 'zf-test-apigility-db-api.rest.doctrine.artist-by-name',
            'route_identifier_name' => 'artist_name',
            'entity_identifier_name' => 'name',
            'collection_name' => 'artist_by_name',
            'entity_http_methods' => array(
```

This configuration shows the ```entity_identifier_name``` as 'name'.  

# Multiple keys

With this change you can use any combination of fields as your identifer.  For instance you can use 
```
...
'entity_identifer_name' => 'email.shop'
```

Using the multi key delimiter on the resource, defaulted to '.' you may then make an api call like ```/api/user/useremailaddress.usershop``` where neither field is an identifier.

