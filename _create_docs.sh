#!/bin/sh
# -o PDF:default
echo "Removing old docs." 
rm -rf ./docs/* 
echo "Creating class documentation." 
phpdoc \
       -d /html/private/PHP-Classes/OCI8/ \
       -i *.png,*.gif,*.jpg,*.sh,*.zip,*.pak,*.html,*.css,*.ico,*.gz,*.js,*.txt,*.sql,*.csv,tests/,PHP4/,contrib/ \
       -t /html/private/PHP-Classes/OCI8/docs \
       -ti "OCI8 PHP Class for PHP4 / PHP5" \
       -dn db_oci8

