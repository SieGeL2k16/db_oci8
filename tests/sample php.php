<?php
$conn = oci_connect("user", "pass", "serv");
if (!$conn)
{
	echo "<h1>ERROR - Could not connect to Oracle</h1>";
	exit;
}

$SQL="begin vms.pkg_vms_query.l_vms_request_info(:id,:csr); end;";

$stmt = oci_parse($conn,$SQL);
if(!$stmt)
{
	echo "<h1>ERROR - Could not parse SQL statement.</h1>";
	exit;
}

$p_cursor=oci_new_cursor($conn);
if (!$p_cursor)
{
   $err=oci_error();
   die ($err['message']);
}

if (!oci_bind_by_name($stmt,":id", $_REQUEST['reqid'] ,10))
{
   $err=oci_error($stmt);
   die ($err['message']);
}

if (!oci_bind_by_name($stmt,":csr",$p_cursor,-1,OCI_B_CURSOR))
{
   $err=oci_error($stmt);
   die ($err['message']);
}

@oci_execute($stmt);
@oci_execute($p_cursor);
while ($row=oci_fetch_array($p_cursor,OCI_BOTH))
{
    print '<tr><td>Request ID</td><td>'. $_REQUEST['reqid'] .'</td></tr>';
    print '<tr><td>Vehicle ID</td><td>'. $row['VEHICLE_ID'] .'</td></tr>';
    print '<tr><td>Vehicle Name</td><td>'. $row['VEHICLE_NAME'] .'</td></tr>';
    print '<tr><td>Requested By</td><td>'. $row['REQUESTER_NAME'] .'</td></tr>';
    print '<tr><td>Date From</td><td>'. $row['DATE_FROM'] .'</td></tr>';
    print '<tr><td>Date To</td><td>'. $row['DATE_TO'] .'</td></tr>';
    print '<tr><td>Remarks</td><td>'. $row['REMARKS'] .'</td></tr>';
    print '<tr><td>Driver\'s Name</td><td>'. $row['DRIVER_NAME'] .'</td></tr>';
    $_REQUEST['nextflow'] = $row['NEXT_FLOW'];
    $_REQUEST['status'] = $row['REQ_STATUS'];
}
OCIFreeStatement($stmt);
OCILogoff($conn);
?>