#/bin/bash
set -xeuf

mkdir -p /tmp/tmp/logs
mkdir -p /tmp/tmp/tests
mkdir -p /tmp/tmp/cache/templates
mysql -h mysql -u root -ppassword -e 'drop database if exists lithium_test; create database lithium_test;'
mysql -h mysql -u root -ppassword -e 'drop database if exists lithium_test_alternative; create database lithium_test_alternative;'

psql postgresql://postgres:5432 -U postgres -c 'drop database if exists lithium_test;'
psql postgresql://postgres:5432 -U postgres -c 'create database lithium_test;' 
psql postgresql://postgres:5432 -U postgres -c 'drop database if exists lithium_test_alternative;' 
psql postgresql://postgres:5432 -U postgres -c 'create database lithium_test_alternative;' 

curl -X DELETE http://couchdb:5984/lithium_test/
curl -X PUT http://couchdb:5984/lithium_test/