### FPM base image
FROM php:8.4-fpm-alpine3.23 AS basefpm

ARG CUSTOM_UID
ARG CUSTOM_GID

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2.10.0 /usr/bin/composer /usr/local/bin/composer
COPY ./infrastructure/dockerfiles/php/conf.d/docker-php-ext-overrides-fpm.ini /usr/local/etc/php/conf.d/docker-php-ext-overrides.ini

RUN addgroup -g ${CUSTOM_UID} www \
    && adduser -u ${CUSTOM_GID} -G www -s /bin/sh -D www \
    && install-php-extensions xdebug pdo_pgsql redis amqp

COPY ./infrastructure/dockerfiles/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-xdebug.ini

WORKDIR /app

USER www

CMD ["php-fpm"]


### CLI base image
FROM php:8.4-cli-alpine3.23 AS basecli

ARG CUSTOM_UID
ARG CUSTOM_GID

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2.10.0 /usr/bin/composer /usr/local/bin/composer
COPY ./infrastructure/dockerfiles/php/conf.d/docker-php-ext-overrides-cli.ini /usr/local/etc/php/conf.d/docker-php-ext-overrides.ini

RUN addgroup -g ${CUSTOM_UID} www \
    && adduser -u ${CUSTOM_GID} -G www -s /bin/sh -D www \
    && install-php-extensions xdebug pdo_pgsql redis amqp sockets

COPY ./infrastructure/dockerfiles/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-xdebug.ini

WORKDIR /app

USER www

ENTRYPOINT [""]
