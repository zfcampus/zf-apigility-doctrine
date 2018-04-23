ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-alpine

RUN apk add --no-cache \
	autoconf \
	make \
	g++ \
	bash \
	git \
	openssl-dev

RUN echo "memory_limit=2G" >> /usr/local/etc/php/php.ini
RUN if [ ${PHP_VERSION:0:3} == "5.6" ] ; then pecl install mongo --no && echo "extension=mongo.so" >> /usr/local/etc/php/php.ini ; else pecl install mongodb && docker-php-ext-enable mongodb ; fi
RUN set -o pipefail && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN echo -e '#!/bin/sh ' > /usr/local/bin/entrypoint.sh \
    && echo -e 'while ! nc -z ${MONGO_HOST:-mongo} ${MONGO_PORT:-27017}; do sleep 1; done' >> /usr/local/bin/entrypoint.sh \
    && echo -e 'exec "$@"' >> /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh
WORKDIR /docker
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["./vendor/bin/phpunit"]
