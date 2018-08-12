#!/usr/local/bin/php
<?php
/**
 * Shows how to read multiple data rows from Oracle with the QueryResult method.
 * @package db_oci8\Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.11 (11-Feb-2007)
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 */
/**
 */
require('functions.inc.php');
$d = WhichBR();
if($d['SAPI'] != 'cli')
  {
  echo("<pre>\n");
  }
echo($d['LF']."Trying to fetch all rows from SCOTT.EMP:".$d['LF'].$d['LF']);

$db->Connect();
$db->QueryResult("SELECT EMPNO,ENAME,JOB,SAL FROM SCOTT.EMP ORDER BY EMPNO");
while($r = $db->FetchResult())
  {
  printf("EMPNO = %5s | ENAME = %-15s | JOB = %-15s | SALARY = %4d".$d['LF'], $r['EMPNO'],$r['ENAME'],$r['JOB'],$r['SAL']);
  }
$db->FreeResult();

echo($d['LF'].$d['HR'].$d['LF']);

/*
 * Now the same with the QueryHash() method and restriction of JOB=SALESMAN:
 */

$bindvars = array('jobval'  => 'SALESMAN');

echo("Fetch all rows with Job = ".$bindvars['jobval'].$d['LF'].$d['LF']);

$db->QueryResultHash("SELECT EMPNO,ENAME,JOB,SAL FROM SCOTT.EMP WHERE JOB=:jobval ORDER BY EMPNO",$bindvars);
while($r = $db->FetchResult())
  {
  printf("EMPNO = %5s | ENAME = %-15s | JOB = %-15s | SALARY = %4d".$d['LF'], $r['EMPNO'],$r['ENAME'],$r['JOB'],$r['SAL']);
  }
$db->FreeResult();

$db->Disconnect();

DBFooter($d['LF'],$db);
echo($d['LF']);
if($d['SAPI'] != 'cli')
  {
  echo("</pre>\n");
  }
exit;

