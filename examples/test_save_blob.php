#!/usr/local/bin/php
<?php
/**
 * Testscript for OCI8 Class.
 * Shows how to Save a binary file as BLOB in Oracle with the SaveBLOB method.
 * @package db_oci8
 * @subpackage Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 1.00 (07-Aug-2010)
 * $Id: test_save_blob.php,v 1.3 2010/08/07 13:11:41 siegel Exp $
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
/**
 */
require('functions.inc.php');
$d = WhichBR();
if($d['SAPI'] != 'cli')
  {
  echo("<pre>\n");
  }

$DMLSQL=<<<SQL
CREATE TABLE OCIBLOB
  (
  ID    NUMBER(38) NOT NULL,
  BDATA BLOB
  )
SQL;

$fname  = 'php_logo.gif';
$fsize  = filesize($fname);
echo($d['LF'].'Trying to save binary file "'.$fname.'" ('.$fsize.' bytes):'.$d['LF'].$d['LF']);

$db->Connect();

/*
 * Make sure that our test table exists, else we create it here automatically:
 */
if(CheckForDBobject($db,'OCIBLOB',$DMLSQL)==FALSE)
  {
  $db->Disconnect();
  exit;
  }

/*
 * Generate a pseudo ID, normally one would use SEQUENCES for that, here we simply use random values.
 */

$id = mt_rand(0,9999);

echo("Saving BLOB under ID number ".$id.$d['LF'].$d['LF']);

/*
 * First save the value without the BLOB:
 */

$sp = array('id' => $id);
$db->QueryHash("INSERT INTO OCIBLOB(ID) VALUES(:id)",OCI_ASSOC,0,$sp);

/*
 * Now save the blob for this id.
 */

$where_clause = "WHERE ID=".$id;
$table_name   = "OCIBLOB";
$field_name   = "BDATA";

if($db->SaveBlob($fname,$table_name,$field_name,$where_clause)!=0)
  {
  $db->RollBack();
  echo("Error saving blob!".$d['LF'].$d['LF']);
  $db->Disconnect();
  exit;
  }
$db->Commit();
echo("Successfully saved blob data.".$d['LF'].$d['LF'].$d['HR'].$d['LF']);
echo("Dumping table data:".$d['LF']);
$query="SELECT ID,DBMS_LOB.GETLENGTH(BDATA) AS BSIZE FROM OCIBLOB ORDER BY ID";
$db->QueryResult($query);
echo("ID      | Size of blobdata".$d['LF']);
echo("--------+---------------------------------------------------------".$d['LF']);
while($s = $db->FetchResult())
  {
  printf("%7d | %-d Bytes%s",$s['ID'],$s['BSIZE'],$d['LF']);
  }
$db->FreeResult();
$db->Disconnect();
echo($d['LF']);
DBFooter($d['LF'],$db);
if($d['SAPI'] != 'cli')
  {
  echo("</pre>\n");
  }
?>
