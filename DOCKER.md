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
docker/connect
```

You will connect to the php container a the root directory.
`cd` to `docker` to work with the mapped local files.


## Configuration

Because you're in a Docker environment with a differnet IP address and name for each
container you need to either change the config files in the project to point to `mongo`
or with a simple hack map localhost to the `mongo` container.

* To edit the config file to point to the `mongo` container instaed of `localhost` edit
`test/config/ODM/local.php` and change the configuration.

* Optionally you can map localhost to mongo with
```
echo `host mongo` | awk '{print $4, "localhost"}' > /etc/hosts
```

## Unit Tests

You may now run the unit tests

```
vendor/bin/phpunit
```
