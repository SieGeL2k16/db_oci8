#!/bin/bash

# Determine directory where the pear executables reside
# NOTE: The Pear version of phpdoc won't work on PHP 7.x, so we need to use the phar version...
# PEAR_BIN=`pear config-get bin_dir`/phpdoc
PEAR_BIN=~/bin/phpDocumentor.phar

echo "Removing old docs."
rm -rf ./docs/*

echo "Creating class documentation."
$PEAR_BIN run \
 --filename dbdefs.inc.php,db_oci8.class.php \
 -i *.png,*.gif,*.jpg,*.sh,*.zip,*.pak,*.html,*.css,*.ico,*.gz,*.js,*.txt,*.sql,*.csv,tests/,PHP4/,contrib/ \
 -t ./docs \
 --title  "OCI8 PHP Class for PHP4 / PHP5" \
 --defaultpackagename "\spfalz\db_oci8"
