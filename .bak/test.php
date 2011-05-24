<?
include('oci8_class.php');
$db = new db_oci8;
//$db->Print_Error('SAFT');
$testquery=<<<EOM
INSERT INTO TRANSACTIONS
  (
  SKY_TRANSID, 
  SKY_CUSTOMERID, 
  SKY_ISSUER, 
  SKY_TRANSSTATUS, 
  SKY_RECORDTYP, 
  WP_STARTDATE, 
  WP_FUTUREPAYID, 
  WP_TRANSID, 
  WP_AVS, 
  WP_TRANSSTATUS, 
  WP_TRANSTIME, 
  WP_AUTHMODE, 
  WP_RAWAUTHCODE, 
  WP_CARTID, 
  WP_DESC, 
  WP_AUTHAMOUNT, 
  WP_AUTHCURRENCY, 
  WP_AMOUNT, 
  WP_CURRENCY
  ) 
VALUES
  (:sky_transid,:sky_customerid,:sky_issuer,0,'H',:wp_startdate,:wp_futurepayid,:wp_transid,:wp_avs,:wp_transstatus,:wp_transtime,:wp_authmode,:wp_rawauthcode,:wp_cartid,:wp_desc, 
  TO_NUMBER(:wp_authamount,'9999990.90'),:wp_authcurrency,TO_NUMBER(:wp_amount,'9999990.90'),:wp_currency)
EOM;
echo("<pre>\n");
$test = $db->GetBindVars($testquery);
print_r($test);
echo("<hr>\n");
$tquery2=<<<EOM
SELECT  USR,
        TO_CHAR(TIME,'DD-Mon-YYYY HH24:MI:SS') AS ZEIT,
        MONTH,
        CLASS,
        SH,
        SD,
        SPAN,
        SV,
        CASE WHEN ZONE=1 THEN 'Nebenzeit' 
        ELSE 
          CASE WHEN ZONE=2 THEN 'Hauptzeit' 
          ELSE 
            CASE WHEN ZONE=3 THEN 'Feiertag' 
            END 
          END 
        END AS ZONE,
        TZOFFSET,
        C_ID,
        DECODE(ORD_NUMBER,NULL,'&nbsp;',ORD_NUMBER) AS ORD_NUMBER
  FROM  SKY_CDR
 WHERE  USR=:myusr
 ORDER  BY TIME DESC 
EOM;
$test = $db->GetBindVars($tquery2);
print_r($test);

/*
$result = $oci->query(
			            OCI_ASSOC, 
			            1, 
			            $transid, 
			            $custid, 
                  $issuer,
			            $sdate, 
			            $_POST["futurePayId"], 
			            $_POST["transId"], 
			            $_POST["AVS"], 
			            $_POST["transStatus"], 
			            $_POST["transTime"], 
			            $_POST["authMode"], 
			            $_POST["rawAuthCode"], 
			            $_POST["cartId"], 
			            $_POST["desc"], 
			            $_POST["authAmount"], 
			            $_POST["authCurrency"], 
			            $_POST["amount"], 
			            $_POST["currency"]
			          );
*/
?>
