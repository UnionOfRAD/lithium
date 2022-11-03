#/bin/bash

apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends curl default-mysql-client git libicu-dev libmemcached-dev libmcrypt-dev libpq-dev netcat postgresql-client-13 vim zip zlib1g-dev

pecl install apcu mcrypt memcached mongodb opcache redis xdebug
docker-php-ext-install intl pdo pdo_mysql pdo_pgsql
docker-php-ext-enable mongodb redis

echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini
echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/xdebug.ini
echo "extension=$(find /usr/local/lib/php/extensions/ -name apcu.so)" > /usr/local/etc/php/conf.d/apcu.ini
echo "extension=$(find /usr/local/lib/php/extensions/ -name memcached.so)" > /usr/local/etc/php/conf.d/memcached.ini
echo "extension=$(find /usr/local/lib/php/extensions/ -name mcrypt.so)" > /usr/local/etc/php/conf.d/mcrypt.ini

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer