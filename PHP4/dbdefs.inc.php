<?php
/**
 * Oracle Database Class.
 * Default Login data and configuration options.
 *
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @package db_oci8
 * @version 1.00 (14-May-2010)
 * $Id$
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */

/**
 * The TNS name of the target database to connect to.
 * If running locally on the database server you may leave that string empty.
 */
define('OCIDB_HOST', '');

/**
 * The Database username used for connecting.
 */
define('OCIDB_USER', 'SCOTT');

/**
 * The password used for connecting.
 */
define('OCIDB_PASS', 'TIGER');

/**
 * Enter the name of your application here.
 * Used when printing out error messages and also used to register this name
 * to Oracle when DB_REGISTER is set to 1.
 * @see DB_REGISTER
 */
define('OCIAPPNAME', 'OCI8-CLASS');

/**
 * Set the following define to 0 if you do not want auto-register of OCIAPPNAME.
 * @see OCIAPPNAME
 */
define('DB_REGISTER',1);

/**
 * Grouping character autoset.
 * Set the following define to 0 if you do not want to auto-set the decimal
 * and grouping characters (see below).
 * @see DB_NUM_DECIMAL
 * @see DB_NUM_GROUPING
 */
define('DB_SET_NUMERIC', 1);

/**#@+
 * Define here what you are using as grouping character and decimal character.
 * Germans usually use DECIMAL=, | GROUPING =. in opposite of english/american
 * which seems to be using DECIMAL=. | GROUPING=, and if you are using something
 * completly different, please drop me a short mail and tell me what you use, I
 * always love to learn :)
 */
define('DB_NUM_DECIMAL'   ,'.');
define('DB_NUM_GROUPING'  ,',');
/**#@-*/

/**
 * Modify default error handling mode if you wish.
 * Default is db_oci8::DBOF_SHOW_NO_ERRORS if you omit this parameter.
 * @since V1.00
 */
define('DB_ERRORMODE', DBOF_SHOW_ALL_ERRORS);

/**
 * You may set a default prefetch value with this define.
 * This is a simple solution to have a higher prefetch value defined
 * as default for all your queries instead of calling setPrefetch()
 * everytime.
 * @since V1.00
 */
define('DB_DEFAULT_PREFETCH', 10);

/**
 * Set address to be shown in case of an error.
 * If this is not set the default address of $_SERVER['SERVER_ADMIN'] is used.
 * @since V1.00
 */
define('OCIDB_ADMINEMAIL' , "php@saschapfalz.de");

/**
 * Set this define to 1 if you want auto-emails to be sent whenever an error occures.
 * Default is 0 (disabled)
 * @since V1.00
 */
define('OCIDB_SENTMAILONERROR', 0);

/**
 * Set this define to 1 if you want to use persistant connections as default connection.
 * Default is 0, which means that OCIlogon is used instead. (new connections).
 * @since V1.00
 */
define('OCIDB_USE_PCONNECT', 1);

/**
 * Set amount of retries in case of a connection failure.
 * Whenever the connection fails and you have defined a value which is greater
 * than 1 the class sleeps for two seconds and retry connection attempt.
 * If you do not define any value here the class uses the default of
 * 1 connection attempt without any sleep, this is the behavour in all previous versions.
 * @since V1.00
 */
define('OCIDB_CONNECT_RETRIES', 5);

/**
 * Setup a default characterset to be used during the connection (Only for Oracle >= 9.2 and PHP 5.1.2+).
 * If this define is not set the NLS_LANG environment variable value is used.
 * You can also override this when using the connect() method.
 * @since V1.00
 */
define('OCIDB_CHARSET'  , 'UTF8');
?>
