Soliant Consulting Apigility
============================

The API created with this library implements full featured and paginated 
collection resources.

Reserved Words
--------------
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


TODO: Complex Query

```
user_id = array(
    'comparator' => 'EQ, LT, GT', // See Doctrine Query Builder documentation
    and
    'values' => array(1,2,3),
    or
    'rangeStart' => 3,
    'rangeEnd' => 5,
```