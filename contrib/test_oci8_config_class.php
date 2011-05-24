<?php
/**
 * Testscript for OCI8 Config Class.
 * Tests all methods of class "oci8_config_class()".
 * @package db_oci8
 * @subpackage Testscripts
 * @category config class
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.1 (07-Aug-2009)
 * $Id: test_oci8_config_class.php,v 1.1 2010/08/07 13:05:30 siegel Exp $
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
require('functions.inc.php');
$d = WhichBR();
if($d['SAPI'] != 'cli')
  {
  echo("<pre>\n");
  }
require_once('../oci8_config_class.php');

$oci8_cfg = new oci8_config_class();

printf("%sTesting oci8_config_class with db_oci8 class %s%s%s",$d['LF'],$oci8_cfg->GetClassVersion(),$d['LF'],$d['LF']);

// Connect to our database:

$oci8_cfg->Connect();

// Initialize the config class code:

$oci8_cfg->initConfigClass();

// Set a config item:
if($oci8_cfg->setConfigItem('test.value',1,OCIDB_USER) == false)
  {
  $oci8_cfg->Disconnect();
  die("An error occured while trying to set config value test.value = 1 !!\n\n");
  }

// Retrieve back the config item:
$cfg_test_value = $oci8_cfg->getConfigItem('test.value',OCIDB_USER);
printf("Config value 'test.value' for '%s'=|%s|%s%s",OCIDB_USER,$cfg_test_value,$d['LF'],$d['LF']);

// And disconnect from database:
$oci8_cfg->Disconnect();

// Print footer and stats:
echo($d['HR']);
DBFooter('',$oci8_cfg);
echo($d['LF'].$d['LF']);
if($d['SAPI'] != 'cli')
  {
  echo("</pre>\n");
  }
exit;
?>
