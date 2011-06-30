<?php
/**
 * Set of functions used in the OCI8 examples.
 * @package db_oci8
 * @subpackage Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 1.00 (14-May-2010)
 * $Id$
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
/**
 * Make sure that we get noticed about EVERYTHING problematic in our code:
 */
ini_set('error_reporting' , E_ALL|E_NOTICE|E_STRICT);

/** Setup the minimal version required to load in the PHP5+ class: */
define('NEW_PHP' , 5);

/**
 * Load in the class definition based on used PHP version.
 */
$phpversion = phpversion();
$phpmajor   = intval($phpversion[0]);
if($phpmajor >= NEW_PHP)
  {
  $class = '../db_oci8.class.php';
  }
else
  {
  $class = '../PHP4/oci8_class.php';
  }
require_once($class);

/**
 * We define here own defines if the PHP 5 class in use to get the old behavour from PHP 4 class.
 * (not recommended, only shown as an example!)
 */
if($phpmajor >= NEW_PHP)
  {
  define('DBOF_DEBUGOFF'          , db_oci8::DBOF_DEBUGOFF);
  define('DBOF_DEBUGSCREEN'       , db_oci8::DBOF_DEBUGSCREEN);
  define('DBOF_DEBUGFILE'         , db_oci8::DBOF_DEBUGFILE);
  define('DBOF_COLNAME'           , db_oci8::DBOF_COLNAME);
  define('DBOF_COLTYPE'           , db_oci8::DBOF_COLTYPE);
  define('DBOF_COLSIZE'           , db_oci8::DBOF_COLSIZE);
  define('DBOF_COLPREC'           , db_oci8::DBOF_COLPREC);
  define('DBOF_CACHE_QUERY'       , db_oci8::DBOF_CACHE_QUERY);
  define('DBOF_CACHE_STATEMENT'   , db_oci8::DBOF_CACHE_STATEMENT);
  define('DBOF_SHOW_NO_ERRORS'    , db_oci8::DBOF_SHOW_NO_ERRORS);
  define('DBOF_SHOW_ALL_ERRORS'   , db_oci8::DBOF_SHOW_ALL_ERRORS);
  define('DBOF_RETURN_ALL_ERRORS' , db_oci8::DBOF_RETURN_ALL_ERRORS);
  }

// Now create new instance of the db_oci8 class (both PHP4 / PHP5)
$db = new db_oci8();

/**
 * Returns an associative array with sapi-type name and required line break char.
 * Use this function to retrieve the required line-break character for both the
 * browser output and shell output. Currently only two keys are included:
 * - "SAPI" => The sapi type of PHP (i.e. "cli")
 * - "LF"   => The line-break character to use (i.e. "<br>")
 * @return array The associative array as described.
 */
function WhichBR()
  {
  $data = array();
  $data['SAPI'] = php_sapi_name();
  switch($data['SAPI'])
    {
    case  'cli':
          $data['LF'] = "\n";
          $data['HR'] = "------------------------------------------------------------------------------\n";
          break;
    default:
          $data['LF'] = "<br>";
          $data['HR'] = "<hr>";
          break;
    }
  return($data);
  }

/**
 * Prints out the amount of queries and the time required to process them.
 * @param string $lf The linefeed character to use.
 * @param mixed &$dbh The database object.
 */
function DBFooter($lf, &$dbh)
  {
  printf("%sQueries: %d | Time required: %5.3fs%s",$lf,$dbh->GetQueryCount(),$dbh->GetQueryTime(),$lf);
  }

/**
 * Checks if given Object name exists inside the database.
 * If checked object does not exist function can auto create the object if required DML is supplied
 * @param mixed &$dbh The database object.
 * @param string $objectname Name of object to check.
 * @param string $dml_sql Required SQL to create the object if it does not exist.
 * @return bool TRUE if Object exists else false.
 */
function CheckForDBobject(&$dbh, $objectname, $dml_sql = '')
  {
  $sp = array('obj' => $objectname);
  $result = $dbh->QueryHash("SELECT COUNT(*) AS CNT FROM USER_OBJECTS WHERE OBJECT_NAME = :obj", OCI_ASSOC, 0, $sp);
  if(intval($result['CNT']) > 0)
    {
    return(true);
    }
  /* If no sql to create object is supplied we return false as object does not exist. */
  if($dml_sql == '')
    {
    return(false);
    }
  /* If $dml_sql != '' we try to create the object in question, and if this does not work we return false. */
  $result = $dbh->Query($dml_sql,OCI_ASSOC, 1);
  if($result)
    {
    $d = WhichBR();
    $error = $dbh->GetErrorText();
    printf("OCI ERROR: %s-%s%s",$result,$error,$d['LF']);
    return(false);
    }
  /* All is okay return true now. */
  return(true);
  }

/**
 * Function lists the contents of our testing table.
 * @param mixed &$dbh Database class object
 * @param array $d The return value from "WhichBR()"
 */
function showTestTable(&$dbh,$d)
  {
  $shown = 0;
  $dbh->QueryResult('SELECT ID,VAL FROM OCI8_CLASS_TEST_TABLE ORDER BY ID');
  while($data = $dbh->FetchResult())
    {
    printf("%5d. %s%s",$data['ID'],$data['VAL'],$d['LF']);
    $shown++;
    }
  $dbh->FreeResult();
  if(!$shown)
    {
    printf("No data in table.%s",$d['LF']);
    }
  }
?>
