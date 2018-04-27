#!/bin/sh

while ! nc -z ${MONGO_HOST:-mongo} ${MONGO_PORT:-27017};
    do sleep 1;
    done

cat ./.docker/ascii.art

exec "$@"
