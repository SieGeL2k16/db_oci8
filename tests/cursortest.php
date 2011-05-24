<?php
/**
 * This script tests functionality with cursor variables declared in packages.
 * We try here to bind a cursor variable from PHP and pass this host variable to
 * PL/SQL, fetching data from the cursor and closing it afterwards.
 * The output must match the output from the anonymous procedure declared on bottom of the cursortest.sql files
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 */

require("../oci8_class.php");
$db = new db_oci8;
$sock = $db->Connect("SCOTT","TIGER");

// First we try this via the traditional oci_* commands from PHP to have a base what exactly has to be done inside the class later:

$query="BEGIN CURSORTEST.GetCursor(:cv); END;";

// Define a new cursor based on current connection

$cv 	= oci_new_cursor($sock);

// Prepare the statement

$stmt = oci_parse($sock,$query);

// Bind the cursor

oci_bind_by_name($stmt,'cv',$cv, -1,OCI_B_CURSOR);

// Now execute, first our statement:

oci_execute($stmt);

// Next we execute our cursor variable (makes no sense to me as PL/SQL allows to read ahead right after opening it...)

oci_execute($cv);

// Now fetch data from cursor:

echo("\n");
while($d = oci_fetch_assoc($cv))
	{
	printf("EMPNO=%s\n",$d['EMPNO']);
	}
echo("\n");

// Now close the cursor:

$cstmt = oci_parse($sock,"BEGIN CURSORTEST.CloseCursor(:cv); END;");
oci_bind_by_name($cstmt,'cv',$cv, -1,OCI_B_CURSOR);
oci_execute($cstmt);

// Cursor variables have to be freed same way as statements:

oci_free_statement($cv);

oci_free_statement($stmt);

$db->Disconnect();
?>
