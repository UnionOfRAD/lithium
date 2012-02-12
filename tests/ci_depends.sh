# Install APC and Mongo extensions

wget -q http://pecl.php.net/get/APC-3.1.9.tgz
tar -xzf APC-3.1.9.tgz
sh -c "cd APC-3.1.9 && phpize && ./configure && make && sudo make install"

wget -q http://pecl.php.net/get/mongo-1.2.7.tgz
tar -xzf mongo-1.2.7.tgz
sh -c "cd mongo-1.2.7 && phpize && ./configure && make && sudo make install"

echo "extension=apc.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
echo "extension=mongo.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

# Ensure read-only mode for Phar files is disabled
sed -E -i '' -e's/phar\.readonly = On/phar.readonly = Off/g' `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
