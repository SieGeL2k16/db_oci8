<?php
/**
 * Tests general class functions.
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @package db_oci8\Testscripts
 * @version 1.1.0 (11-Aug-2018)
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 */
/**
 * Load in the general functions for all tests.
 */
require_once('functions.inc.php');

// Determine SAPI type
$d = WhichBR();

// Before doing anything connect first!
$db->Connect();


if($d['SAPI'] != 'cli')
  {
  echo('<pre>');
  }

echo($d['LF'].'General Test for OCI8 class'.$d['LF'].$d['LF']);

printf("PHP Version / SAPI type......: %s / %s%s",phpversion(),$d['SAPI'],$d['LF']);
printf("OCI8 Class Version...........: %s%s",$db->GetClassVersion(),$d['LF']);
printf("Oracle Server Version........: %s%s",$db->Version(),$d['LF']);

if(IS_PHP5 == TRUE)
  {
  $CLASS_NAME = "spfalz\db_oci8";
  }
else
  {
  $CLASS_NAME = "db_oci8";
  }

  // Always disconnect when you don't need the database anymore
$db->Disconnect();
// Dump out all defined methods in the class:

$class_methods = get_class_methods($CLASS_NAME);
natcasesort ($class_methods);

printf("%sList of defined methods (%s) in db_oci8 class:%s%s",$d['LF'],count($class_methods),$d['LF'],$d['LF']);
$cnt = 1;
foreach ($class_methods as $method_name)
  {
  printf("%02d. %s%s",$cnt,$method_name,$d['LF']);
  $cnt++;
  }

DBFooter($d['LF'],$db);

echo($d['LF']);
exit;
