# Docker for Development

This library requires a running instance of Mongo in order to run and pass
the unit tests.  It is not expected for each developer to configure their
individual machine to match this environment so Docker is provided.


## Running docker-compose

You will need docker-compose installed on your machine.

From the root directory of this project run

```
docker-compose build
```

This will build the php container.
To connect to the php container and run the unit tests run

```
docker-compose run --rm php bash
```


## Unit Tests Only

You may run the unit tests through the container without connecting
with bash via

```
docker-composer run --rm php
```
