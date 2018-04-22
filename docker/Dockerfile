ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-alpine

RUN apk add --no-cache \
	autoconf \
	make \
	g++ \
	bash

RUN pecl install mongodb && docker-php-ext-enable mongodb
RUN set -o pipefail && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN echo -e '#!/bin/sh' > /usr/local/bin/entrypoint.sh \
    && echo -e 'while ! nc -z ${MONGO_HOST:-mongo} ${MONGO_PORT:-27017}; do sleep 1; done' >> /usr/local/bin/entrypoint.sh \
    && echo -e 'exec "$@"' >> /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh
WORKDIR /docker
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["./vendor/bin/phpunit"]

# RUN apt-get update
# RUN apt-get --yes install vim
# RUN apt-get --yes install git
# RUN apt-get --yes install inetutils-ping
# RUN apt-get --yes install host
