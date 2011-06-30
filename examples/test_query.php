#!/usr/local/bin/php
<?php
/**
 * Testscript for OCI8 Class.
 * Shows how to read data from Oracle with the Query method.
 * @package db_oci8
 * @subpackage Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 1.00 (15-May-2010)
 * $Id$
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
/**
 */
require('functions.inc.php');
$d = WhichBR();
echo($d['LF']."Trying to fetch SYSDATE from Oracle: ".$d['LF'].$d['LF']);

$db->Connect();

/*
 * First simple usage of the Query() method:
 */

$result = $db->Query("SELECT TO_CHAR(SYSDATE,'DD-Mon-YYYY HH24:MI:SS') AS SD FROM DUAL");

echo("Time on Database is ".$result['SD'].$d['LF']);
echo($d['HR']);

/*
 * Query, but this time with associative array to use bind vars:
 */

echo("Reading EMP table data from entry with EMPNO = 7900 via HASH:".$d['LF'].$d['LF']);

$bindvars = array('empno' => 7900);

$result = $db->QueryHash('SELECT ENAME,JOB FROM EMP WHERE EMPNO = :empno', OCI_ASSOC,0, $bindvars);

echo("Mr. ".$result['ENAME']." is ".$result['JOB']." and has empno = ".$bindvars['empno'].$d['LF']);

$db->Disconnect();

DBFooter($d['LF'],$db);
echo($d['LF']);
exit;
?>
