#!/usr/local/bin/php
<?php
/**
 * This example uses the oci8_collection_class to demonstrate the use of collections.
 * @package db_oci8
 * @subpackage Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.1 (26-Dec-2008)
 * $Id: test_collections.php,v 1.1 2010/08/07 13:05:30 siegel Exp $
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
/**
 * Load in the basic include used in all examples.
 */
require_once('functions.inc.php');

/**
 * Now we load in the oci8_collection class and instantiate it:
 */
require_once('../oci8_collection_class.php');

$db = new db_oci8_collection_class();

$db->Connect();

$obj = $db->NewCollection('TEST','SIEGEL');

if(is_object($obj) == false)
  {
  $err = $db->getSQLError();
  printf("Error while trying to get Newcollection: %s\n",$err['msg']);
  print_r($err);
  }

$db->Disconnect();
?>
