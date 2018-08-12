#!/usr/local/bin/php
<?php
/**
 * Testscript for OCI8 Class.
 * Shows how to use the method DescTable().
 * @package db_oci8\Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.1 (02-Dec-2006)
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 */
/**
 */
require('functions.inc.php');
$d = WhichBR();
echo($d['LF']."Describe Table SCOTT.EMP:".$d['LF'].$d['LF']);

$db->Connect();
$result = $db->DescTable('SCOTT.EMP');
$db->Disconnect();

if($d['SAPI'] != 'cli')
  {
  echo("<pre>\n");
  }
echo("NAME                             |     TYPE     | SIZE | PRECISION".$d['LF']);
echo("---------------------------------+--------------+------+-----------".$d['LF']);
for($i = 0; $i < count($result); $i++)
  {
  printf("%-32s | %-12s | %4d | %-s".$d['LF'],$result[$i][0],$result[$i][1],$result[$i][2],$result[$i][3]);
  }
echo("---------------------------------+--------------+------+-----------".$d['LF']);
DBFooter($d['LF'],$db);

echo($d['LF']);

if($d['SAPI'] != 'cli')
  {
  echo("</pre>\n");
  }
exit;

