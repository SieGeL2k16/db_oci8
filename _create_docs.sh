#!/bin/sh
# -o PDF:default
echo "Removing old docs."
rm -rf ./docs/*
echo "Creating class documentation."
phpdoc run \
 --filename dbdefs.inc.php,db_oci8.class.php \
 -i *.png,*.gif,*.jpg,*.sh,*.zip,*.pak,*.html,*.css,*.ico,*.gz,*.js,*.txt,*.sql,*.csv,tests/,PHP4/,contrib/ \
 -t ./docs \
 --title  "OCI8 PHP Class for PHP4 / PHP5" \
 --defaultpackagename "db_oci8"
