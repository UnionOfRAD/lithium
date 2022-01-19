#/bin/bash
set -xeuf

cd ..
cp -r lithium/tests/ci/app .
if [ ! -e "app/libraries/lithium" ] ; then
    ln -s ../../lithium app/libraries/lithium
fi

cd app

set +e

composer require alcaeus/mongo-php-adapter

mkdir -p /tmp/tmp/logs
mkdir -p /tmp/tmp/tests
mkdir -p /tmp/tmp/cache/templates
mysql -h mysql -u root -ppassword -e 'create database lithium_test; create database lithium_test_alternative;'

psql postgresql://postgres:5432 -U postgres -c 'create database lithium_test;' 
psql postgresql://postgres:5432 -U postgres -c 'create database lithium_test_alternative;' 

curl -X PUT http://couchdb:5984/lithium_test/