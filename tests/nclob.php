<?
require('oci8_class.php');
$query = 'SELECT TO_NCHAR(MYCLOB) AS MYCLOB2 FROM NCLOB_TEST';
//$query = 'SELECT MYCLOB FROM NCLOB_TEST WHERE ID = 1';
$db = new db_oci8;
$db->Connect('SieGeL','microsoft');
$db->QueryResult($query);
while($r = $db->FetchResult())
  {
  print_r($r);
  echo("\n");
  }
$db->FreeResult();
echo("--------------------------------------------\n");
$test = $db->Query($query);
print_r($test);
$db->Disconnect();
/*
 * PHP OCI8 functions:
 */
echo("NOW NATIVE CODE FROM PHP 5.1.1\n");
//oci_internal_debug(1);
$sock = OCILogon('SieGeL','microsoft');
$stmt = OCIParse($sock,$query);
OCIExecute($stmt,OCI_DEFAULT);
OCIFetchInto($stmt,$resultarray,OCI_ASSOC+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
print_r($resultarray);

OCIExecute($stmt,OCI_DEFAULT);
$resultarray = oci_fetch_array($stmt,OCI_ASSOC+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
print_r($resultarray);

OCIFreeStatement($stmt);


OCILogoff($sock);
exit;
?>
