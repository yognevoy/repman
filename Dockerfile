FROM php:8.0.19-fpm-alpine

ARG TIMEZONE="UTC"
ARG APP_ENV="prod"

SHELL ["sh", "-eo", "pipefail", "-c"]

# install composer and extensions: pdo_pgsql, intl, zip
RUN apk update && \
    apk add --no-cache -q \
    bash \
    git \
    subversion \
    openssh-client

RUN curl -sSLf \
    -o /usr/local/bin/install-php-extensions \
    https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions opcache pdo_pgsql intl zip @composer && \
    rm /usr/local/bin/install-php-extensions

# set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && \
    echo ${TIMEZONE} > /etc/timezone && \
    printf '[PHP]\ndate.timezone = "%s"\n', "$TIMEZONE" > \
    /usr/local/etc/php/conf.d/tzone.ini && "date"

# set memory limit
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# hide X-Powered-By in reponse header
RUN echo "expose_php=off" > /usr/local/etc/php/conf.d/expose.ini

# automatically add new host keys to the user known hosts
RUN printf "Host *\n    StrictHostKeyChecking no" > /etc/ssh/ssh_config

RUN mkdir /app
WORKDIR /app

COPY . .

ENV APP_ENV=${APP_ENV}

# install dependencies based on environment
RUN if [ "$APP_ENV" = "dev" ] ; then \
        composer install --optimize-autoloader ; \
    else \
        composer install --optimize-autoloader --no-dev ; \
    fi && \
    composer clear-cache
