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

RUN php --ini | sed -n -e 's/^.*Path: //p'
RUN echo "memory_limit=2G" >> 'test' >> `php --ini | sed -n -e 's/^.*Path: //p'`/php.ini
RUN set -o pipefail && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN if [ ${PHP_VERSION:0:3} == "5.6" ] ; then pecl -q install -f mongo && echo "extension=mongo.so" >> `php --ini | sed -n -e 's/^.*Path: //p'`/php.ini ; fi
RUN if [ ${PHP_VERSION:0:1} == "7" ] ; then pecl config-set php_ini `php --ini | sed -n -e 's/^.*Path: //p'`/php.ini && pecl install mongodb && docker-php-ext-enable mongodb && echo "extension=mongodb.so" >> `php --ini | sed -n -e 's/^.*Path: //p'`/php.ini; fi
RUN if [ ${XDEBUG} == "1" ] ; then pecl config-set php_ini `php --ini | sed -n -e 's/^.*Path: //p'`/php.ini && pecl -q install -f xdebug && docker-php-ext-enable xdebug && echo "zend_extension=xdebug.so" >> `php --ini | sed -n -e 's/^.*Path: //p'`/php.ini ; fi
RUN echo -e '#!/bin/sh ' > /usr/local/bin/entrypoint.sh \
    && echo -e 'while ! nc -z ${MONGO_HOST:-mongo} ${MONGO_PORT:-27017}; do sleep 1; done' >> /usr/local/bin/entrypoint.sh \
    && echo -e 'exec "$@"' >> /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh
WORKDIR /docker
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["./vendor/bin/phpunit"]
