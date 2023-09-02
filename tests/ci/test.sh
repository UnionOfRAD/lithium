#/bin/bash
./tests/ci/install.sh

set -xeuf
cd ../app

DB=sqlite ./libraries/lithium/console/li3 test libraries/lithium/tests --verbose

DB=couchdb ./libraries/lithium/console/li3 test libraries/lithium/tests/cases/data --verbose
# DB=couchdb ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose

DB=mysql ./libraries/lithium/console/li3 test libraries/lithium/tests/cases/data --verbose
DB=mysql ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose

DB=pgsql ./libraries/lithium/console/li3 test libraries/lithium/tests/cases/data --verbose
DB=pgsql ./libraries/lithium/console/li3 test libraries/lithium/tests/integration/data --verbose