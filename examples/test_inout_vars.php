#!/usr/local/bin/php
<?php
/**
 * Testscript for OCI8 Class.
 * Shows how to use IN/OUT variables and calling procedures.
 * @package db_oci8
 * @subpackage Testscripts
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.1 (11-Feb-2007)
 * $Id: test_inout_vars.php,v 1.4 2010/08/07 13:11:41 siegel Exp $
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
/**
 */
require('functions.inc.php');
$d = WhichBR();
echo($d['LF']."Example how to use IN/OUT variables with PL/SQL procedures.".$d['LF'].$d['LF']);

$sample_procedure=<<<SQL
CREATE OR REPLACE PROCEDURE OCIADD(p_val1 IN NUMBER, p_val2 IN NUMBER, p_result OUT NUMBER)
IS
BEGIN
  p_result := p_val1 + p_val2;
EXCEPTION WHEN OTHERS THEN
  p_result := -1;
END;
SQL;
$db->Connect();
if(CheckForDBobject($db,'OCIADD',$sample_procedure)==FALSE)
  {
  $db->Disconnect();
  exit;
  }

/* Now call the procedure by adding 20 + 22: */

$val1   = 20;
$val2   = 22;

$sql = 'BEGIN OCIADD(:val1,:val2,:result); END;';

/* The hash keys MUST (!) be named exactly like the bind parameters inside the SQL query without the ':' !
 * Also keep in mind that these names are CASE-SENSITIVE !
 */

$invars['val1'] = $val1;
$invars['val2'] = $val2;

/* Outvar definition.
 * Note that you have to enter exactly the amount of digits that could be returned as maximum value,
 * else you may receieve ORA-06502 errors. Same rule apply for strings!
 */

$outvars['result'] = 99;

/* First we have to bind the output variables: */

$db->setOutputHash($outvars);

/* Now we call the procedure. We are not interested of any return value as this stands in the output hash */

$db->QueryHash($sql,OCI_ASSOC,0,$invars);

$data = $db->getOutputHash();

$db->Disconnect();

printf("Result of %d + %d  = %d%s",$val1,$val2,$outvars['result'],$d['LF']);

/* And clear the class-internal output hash just to be on the safe side. */

$db->clearOutputHash();

DBFooter($d['LF'].$d['LF'],$db);
?>
