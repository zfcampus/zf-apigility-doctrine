# Using Docker for Development

This library requires a running instance of Mongo in order to run and pass
the unit tests.  It is not expected for each developer to configure their
individual machine to match this environment so Docker is provided.


## Running docker-compose

You will need docker-compose installed on your machine.

From the root directory of this project run

```
docker-compose build
```

This will build the php container.  Next run

```
docker-compose up
```

This will spin up the php container and a mongo container.

To connect to the php container and run the unit tests
run

```
docker-compose run php bash
```

You will connect to the php container a the root directory.
`cd` to `docker` to work with the mapped local files.

## Unit Tests

Having run `composer install` you may now run the unit tests

```
vendor/bin/phpunit
```
