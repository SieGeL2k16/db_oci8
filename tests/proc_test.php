<?php
/*
Tests OUT var support, the following dummy procedure is used for the test:

CREATE OR REPLACE PROCEDURE TestProc(giveittome OUT NUMBER)
IS
BEGIN
  giveittome := 1000;
END;
/

*/

require("oci8_class.php");

$db = new db_oci8;
$db->Connect('siegel','Strafe','sgldev');


$testarray = array('myvar'  => -1);

$db->QueryHash('BEGIN TestProc(:myvar); END;',OCI_ASSOC,0,$testarray);

$db->Disconnect();

print_r($testarray);


?>
