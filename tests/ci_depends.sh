# Install APC and Mongo extensions

sudo pecl install apc
sudo pecl install mongo

echo "extension=apc.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
echo "extension=mongo.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

# Ensure read-only mode for Phar files is disabled
sed -E -i '' -e's/phar\.readonly = On/phar.readonly = Off/g' `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
