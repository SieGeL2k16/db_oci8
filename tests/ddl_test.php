<?php
/*
 * TEst case for DDL commands
 */

$sql = array();

$sql[0]=<<<SQL
CREATE TABLE DDL_TEST
  (
  ID    NUMBER(38) NOT NULL
  )
SQL;

$sql[1]=<<<SQL
create sequence seq_ddl_test start with 10000 increment by 1 nomaxvalue
SQL;

$sql[2]=<<<SQL
CREATE OR REPLACE TRIGGER TR_DDL_TEST
	BEFORE INSERT ON DDL_TEST
	FOR EACH ROW
		WHEN (new.id IS NULL)
			BEGIN
  			SELECT seq_ddl_test.NEXTVAL
  			INTO   :new.id
  			FROM   dual;
  			END;
SQL;
require_once('../oci8_class.php');
$db = new db_oci8;
$db->Connect();
$db->Query('DROP TABLE DDL_TEST',OCI_ASSOC,1);
$db->Query('DROP SEQUENCE SEQ_DDL_TEST',OCI_ASSOC,1);
for($i = 0; $i < count($sql); $i++)
  {
  $db->Query($sql[$i]);
  }
$db->Disconnect();
?>
