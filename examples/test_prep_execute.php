#!/usr/local/bin/php
<?php
/**
 * Shows how to use the Prepare() / Execute() methods.
 * @package db_oci8\Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.1 (26-Dec-2008)
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 */
/**
 */
require('functions.inc.php');

// Determine SAPI type
$d = WhichBR();

// Our testing table definition

$test_table=<<<EOM
CREATE TABLE OCI8_CLASS_TEST_TABLE
  (
  ID  NUMBER(38),
  VAL VARCHAR2(200)
  )
EOM;

$table_name = 'OCI8_CLASS_TEST_TABLE';

printf("%sTesting methods Prepare()/ExecuteHash().%s%s",$d['LF'],$d['LF'],$d['LF']);

$db->Connect();

// Check if table exists, create it if not exist yet:
CheckForDBobject($db, $table_name, $test_table);

// Truncate table for safety (we want an empty table for our tests)
$db->Query('TRUNCATE TABLE '.$table_name);

/*
 * Now we add 10 rows of data with the help of a prepared statement. This is faster than
 * calling 10 times the Query() method involving OCIParse/OCIExecute/OCIFetch/OCIFreeStatement.
 * First we "Prepare" the statement. The class stores this statement then in an internal cache
 * and Execute() will finally execute the query.
 * Please note that we call Prepare() here with the 2nd parameter set to 1, this means
 * that the class won't auto-handle errors but instead return them to us.
 */

$istmt = $db->Prepare('INSERT INTO OCI8_CLASS_TEST_TABLE(ID,VAL) VALUES(:id,:val)',1);

// Iterate now and add the rows:

$doubles = 0;
for($i = 0; $i < 10; $i++)
  {
  $iparam['id']   = $i;
  $iparam['val']  = mt_rand(0,9999999);
  $rc = $db->ExecuteHash($istmt,$iparam);
  if($rc != $istmt)
    {
		if($rc == 1)
		  {
			$doubles++;
			}
		else
			{
			$oerr = $db->getSQLError();
			$db->Rollback();
			$db->Disconnect();
			die("ERROR: CANNOT INSERT DATA (ID=".$i."|VAL=".$iparam['val'].") TO TEST TABLE: ".$oerr['msg'].$d['LF'].$d['LF']);
			exit;
			}
    }
  }

// Finally we have to free the stored statement:
$db->FreeResult($istmt);

// And of course we have to commit(), else we loose the added data!
$db->Commit();

// Now we display our results:
showTestTable($db,$d);

$db->Disconnect();

DBFooter($d['LF'],$db);
echo($d['LF']);
exit;
