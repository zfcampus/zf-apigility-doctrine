Soliant Consulting Apigility
============================

This documents the reqiurements of your entities to work with this library.  

The ArraySerializable hydrator is used by default.  This requires

```
public function getArrayCopy() 
{
    return array(
        'id' => $this->getId(),
        'anotherField' => $this->getAnotherField(),
        'referenceToAnotherEntity' => $this->getReferenceToAnotherEntity(),
    );
}
```

and 

```
public function exchangeArray($data) 
{
    $this->setAnotherField(isset($data['anotherField']) ? $data['anotherField']: null);
    $this->setReferenceToAnotherEntity(isset($data['referenceToAnotherEntity']) ? $data['referenceToAnotherEntity']: null);
}
```

It is important the id is not in exchangeArray and is in getArrayCopy.  
All fields and references need to be in both functions.  Collections
such as many to one relationships are in neither function.  

*** note the DoctrineEntity hydrator may be preferred.  To impliment this hydrator
you must create a hydrator which can dyanamicly compose a DoctrineEntity hydrator 
from the given object manager. ***