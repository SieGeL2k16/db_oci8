#!/usr/local/bin/php
<?php
/**
 * Testscript for OCI8 Class.
 * Shows how to use DML commands with the OCI8 class.
 * @package db_oci8
 * @subpackage Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.1 (26-Dec-2008)
 * $Id$
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
/**
 */
require('functions.inc.php');
$d = WhichBR();
$test_table=<<<EOM
CREATE TABLE OCI8_CLASS_TEST_TABLE
  (
  ID  NUMBER(38),
  VAL VARCHAR2(200)
  )
EOM;

printf("%sTesting DML commands.%s%s",$d['LF'],$d['LF'],$d['LF']);

$db->Connect();

/**
 * First we check if our test table exists. If not we create it, else we truncate it.
 * Note that we are using here the "QueryHash()" method, which should be the prefered
 * way to pass bind variables to queries.
 */

$param['tname'] = 'OCI8_CLASS_TEST_TABLE';

$tst = $db->QueryHash('SELECT COUNT(*) AS CNT FROM USER_TABLES WHERE TABLE_NAME=:tname',OCI_ASSOC,0,$param);
if(!intval($tst['CNT']))
  {
  // Table does not exist, so let's create it now:
  printf("Test table does not exist, creating table.%s%s",$d['LF'],$d['LF']);
  $db->Query($test_table);
  }
else
  {
  // Table already exists, so just truncate it:
  printf("Test table exists, truncating it now.%s%s",$d['LF'],$d['LF']);
  $db->Query('TRUNCATE TABLE OCI8_CLASS_TEST_TABLE');
  }

/*
 * All right, table preparation done, now let's first add 3 rows to it with random data.
 */

printf("Adding 3 rows with random data.%s%s",$d['LF'],$d['LF']);
$query    = '';
$affected = 0;
for($i = 0; $i < 3; $i++)
  {
  $iparam['id']   = $i;
  $iparam['val']  = mt_rand(0,999999);
  $db->QueryHash('INSERT INTO OCI8_CLASS_TEST_TABLE(ID,VAL) VALUES(:id,:val)',OCI_ASSOC,0,$iparam);
  $affected+= $db->AffectedRows();
  }
printf("Affected Rows: %d%s%s",$affected,$d['LF'],$d['LF']);

// Now we display these values

printf("Data added to table:%s%s",$d['LF'],$d['LF']);

showTestTable($db,$d);

// Update row 0 with a new value and display it again:

printf("%sUpdating ID = 0 with new value '0123456789abcdef' : %s%s",$d['LF'],$d['LF'],$d['LF']);

$iparam['id']   = '0';
$iparam['val']  = '0123456789abcdef';
$db->QueryHash('UPDATE OCI8_CLASS_TEST_TABLE SET VAL=:val WHERE ID=:id',OCI_ASSOC,0,$iparam);

showTestTable($db,$d);

// Now we perform a rollback to show that transactional control is working. Table must be empty afterwards:

printf("%sPerforming a rollback() to get rid of our changes:%s%s",$d['LF'],$d['LF'],$d['LF']);
$db->Rollback();

printf("Table must be empty now after rollback was performed:%s%s",$d['LF'],$d['LF']);

showTestTable($db,$d);

$db->Disconnect();

DBFooter($d['LF'],$db);
echo($d['LF']);
exit;
?>
