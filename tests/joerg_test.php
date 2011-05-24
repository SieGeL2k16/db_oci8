<?php
/**
 * Das beispiel verwendet folgende 2 Tabellen:
 *
 * create table idlist (id number);
 * create table sdolist(id number, data_x number, data_y number);
 *
 * In der Tabelle idlist sind die Werte 1,2,3,4,5,6,7,8,9,10 abgelegt.
 * In der Tabelle sdolist sind folgende Werte abgelegt:
 *
 * ID	DATA_X	DATA_Y
 * --+-------+------
 * 1	100	    101
 * 2	200	    201
 * 3	300	    301
 * 4	400	    401
 * 5	500	    501
 * 6	600	    601
 * 7	700	    701
 * 8	800	    801
 * 9	900	    901
 */
require_once('../oci8_class.php');
$db = new db_oci8;

// Scott / Tiger mit deinem Usernamen / Passwort ersetzen:
$db->Connect('scott','tiger');

// IDs ermitteln für Werte 1,3,5 (eigentlich unsinnig, soll nur das Ermitteln der IDs simulieren):

$where = ' WHERE il.ID IN (1,3,5)';

/*** Beispiel 1 mit Unterabfrage: ***/

$query=<<<EOM
SELECT sl.ID,
       sl.DATA_X,
       sl.DATA_Y
  FROM SDOLIST sl
 WHERE sl.ID IN
  (
  SELECT il.ID
    FROM IDLIST il
    $where
  )
ORDER BY sl.ID
EOM;
$db->QueryResult($query);
while($data = $db->FetchResult())
  {
  printf("ID=%d | DATA_X = %s | DATA_Y = %s\n",$data['ID'],$data['DATA_X'],$data['DATA_Y']);
  }
$db->FreeResult();


/*** Beispiel 2 mit 2 Queries verschachtelt: ***/

$id_arr = array();      // Die Liste mit den Ids
$sdo_arr= array();      // Die zusätzlichen GEO Daten
$bindarr= array();      // Array für Suchparameter

// Den 2. Query in den "Cache" schreiben:
$sdo_query= 'SELECT DATA_X, DATA_Y FROM SDOLIST WHERE ID=:id';
$q2_cached = $db->Prepare($sdo_query);

// Den 1. Query zusammenbauen:
$where  = ' WHERE ID IN (1,3,5)';
$id_query = 'SELECT ID FROM IDLIST '.$where;

// 1. Query ausführen:
$db->QueryResult($id_query);
$lv = 0;
while($id = $db->FetchResult())
  {
  $id_arr[$lv]['ID'] = $id['ID'];

  // Parameterliste für sdo_query aufbauen als associatives Array:

  $bindarr['id'] = $id['ID'];

  // 2. Query mit der ID aus dem 1. Query aufrufen:
  $stmt2 = $db->ExecuteHash($q2_cached, $bindarr);
  while($sdo = $db->FetchResult(OCI_ASSOC,$stmt2))
    {
    $dummy = sprintf("%s;%s",$sdo['DATA_X'],$sdo['DATA_Y']);

    // SDO Daten in das Array an Position $lv schreiben:

    $id_arr[$lv]['SDO'] = $dummy;
    }

  $lv++;
  }

// Statement handle freigeben von 2. Query
$db->FreeResult($q2_cached);

// Statement Handle vom 1. Query freigeben
$db->FreeResult();

// Jetzt die Verbindung zur Datenbank trennen.
$db->Disconnect();

// Das erstellte array ausgeben:
print_r($id_arr);
?>
