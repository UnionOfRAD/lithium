#/bin/bash

apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends curl default-mysql-client git libicu-dev libmemcached-dev libmcrypt-dev libpq-dev libssl-dev netcat postgresql-client-13 vim zip zlib1g-dev

pecl install apcu memcached mongodb opcache redis xdebug
docker-php-ext-install intl pdo pdo_mysql pdo_pgsql
docker-php-ext-enable mongodb redis

echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini
echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/xdebug.ini
echo "extension=$(find /usr/local/lib/php/extensions/ -name apcu.so)" > /usr/local/etc/php/conf.d/apcu.ini
echo "extension=$(find /usr/local/lib/php/extensions/ -name memcached.so)" > /usr/local/etc/php/conf.d/memcached.ini

/tmp/install-composer.sh