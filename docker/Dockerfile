ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-alpine

ARG XDEBUG=0

RUN apk add --no-cache \
	autoconf \
	make \
	g++ \
	bash \
	git \
	openssl-dev

RUN echo -e 'memory_limit=2G' > /usr/local/etc/php/conf.d/memory.ini
RUN set -o pipefail && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN if [ ${PHP_VERSION:0:3} == "5.6" ] ; then \
        pecl install mongo && docker-php-ext-enable mongo ; \
    else \
        pecl install mongodb && docker-php-ext-enable mongodb ; \
    fi
RUN if [ ${XDEBUG} == "1" ] ; then pecl install xdebug && docker-php-ext-enable xdebug ; fi
RUN composer config --global "platform.ext-mongo" "1.999" ;
RUN echo -e '#!/bin/sh ' > /usr/local/bin/entrypoint.sh \
    && echo -e 'while ! nc -z ${MONGO_HOST:-mongo} ${MONGO_PORT:-27017}; do sleep 1; done' >> /usr/local/bin/entrypoint.sh \
    && echo -e 'exec "$@"' >> /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /docker
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
