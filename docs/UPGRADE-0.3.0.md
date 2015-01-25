# Module changes

- Install and configure zf-doctrine-querybuilder according the documentation
- Make sure to add the use with apigility doctrine part!

# Configuration adjustments

- Add 'entity_identifier_name' key to the zf-rest config. This is the name of the identifier property of your entity.
- Change key 'zf-collection-query' to 'zf-apigility-doctrine-query-provider'
- Change key 'query_provider' in zf-apigility > doctrine-connected to 'query_providers' according to https://github.com/zfcampus/zf-apigility-doctrine#query-providers. Example:

```
'query_providers' => array(
    'default' => 'default_odm',
    'fetch_all' => 'Key.in.zf-apigility-doctrine-query-provider',
),
```

# Class adjustments

- Change interface of query provider classes to: ZF\Apigility\Doctrine\Server\Query\Provider\QueryProviderInterface or extend the DefaultOrm / DefaultOdm. (Note: you could also use the defaults from the querybuilder module)
- Prepend the $resourcEvent to the createQuery and change logic if needed


# Implementation adjustments
- Change the 'query' parameter to 'filters' (in $_GET)
- Change the 'sort' parameter to 'order-by' (in $_GET)

