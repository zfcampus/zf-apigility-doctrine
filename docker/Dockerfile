FROM php:latest
RUN apt-get update
RUN apt-get --yes install vim
RUN apt-get --yes install git
RUN apt-get --yes install inetutils-ping
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /bin/composer
