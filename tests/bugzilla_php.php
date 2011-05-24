<?php
//$query = 'SELECT MYCLOB FROM NCLOB_TEST WHERE ID = 1';
$query = 'SELECT TO_NCHAR(MYCLOB) AS MYCLOB FROM NCLOB_TEST';
//oci_internal_debug(1);
$sock = OCILogon('SieGeL','microsoft');
$stmt = OCIParse($sock,$query);
OCIExecute($stmt,OCI_DEFAULT);
OCIFetchInto($stmt,$resultarray,OCI_ASSOC+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
print_r($resultarray);

// Now try to load the clob with descriptor:

OCIExecute($stmt,OCI_DEFAULT);
OCIFetchInto($stmt,$resultarray,OCI_ASSOC);
print_r($resultarray);
$lobcontent = $resultarray['MYCLOB']->load();
OCIFreeStatement($stmt);

print_r($lobcontent);

OCILogoff($sock);
exit;
?>
