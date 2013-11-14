Soliant Consulting Apigility
============================

This documents the reqiurements of your entities to work with this library.  

The ArraySerializable hydrator is used by default.  This requires

```
public function setId($value) 
{
    $this->id = $value;
}

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
```

It is important the id is not in exchangeArray and is in getArrayCopy.  
All fields and references need to be in both functions.  Collections
such as many to one relationships are in neither function.  

```setId($value)``` is generally not implemented by traditional Doctrine entity design
but if using the Client and because ArraySerializable hydration is used and becuase 
setting the id in exchangeArray() is not advised, this setter is required.