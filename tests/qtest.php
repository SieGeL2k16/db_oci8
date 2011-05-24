<?php
  function GetBindVars($query)
    {
    $pattern = array("/(TO_.*?\()(.*?)(,)(.*?\))/is","/(TO_.*?\('.*?'\))/is","/(TO_.*?\()(.*?\))/is");
    $replace = array("$2","","$2");
    $mydummy = $query;    // Make copy of current SQL
    echo("ORIG=\n".$query."\n<hr>\n");
    $mydummy = @preg_replace($pattern,$replace,$mydummy);
    print_r($mydummy);
    @preg_match_all("/[^'][,|\W]?(:\w+)[,|\W]?[^']/i",$mydummy,$res);
    return($res[1]);
    }

/*
$tquery=<<<EOM
  SELECT TO_CHAR(cdr.time+(cdr.TZOFFSET/86400),'DD-Mon-YYYY') AS MYDATE,
         TO_CHAR(cdr.time+(cdr.TZOFFSET/86400),'HH24:MI:SS') AS MYTIME,
         cdr.SD,
         TRIM(TO_CHAR(ROUND(cdr.sv/1048576,2),'999990.90')) AS VOLUME,
         cdr.CLASS,
         DECODE(cdr.zone,2,'HZ','NZ') AS ZONE,
         TO_CHAR(cdr.SV * pricing.weight/p.unit_sv/100000,'999990.9990') AS KOHLE
    FROM SKY_CDR cdr, SKY_PARAMETER p, SKY_PRICING pricing, ORDERS2 u, SKY_ISSUER i
   WHERE cdr.usr        = :myusr
     AND cdr.month      = TO_CHAR(TO_DATE(:mymonth,'YYYYMMDDHH24:MI:SS'),'YYYYMM')
     AND u.usr          = cdr.usr
     AND u.issuer       = pricing.issuer
     AND u.issuer       = i.id
     AND cdr.zone       = pricing.zone
     AND pricing.month  = :brmax
     AND pricing.pcode  = u.pcode
     AND u.BEGINDATE BETWEEN pricing.SDATE AND pricing.EDATE
     AND p.month = (SELECT MAX(month) FROM SKY_PARAMETER WHERE month <= TO_CHAR(TO_DATE(:mymonth,'YYYYMMDDhh24:mi:ss'),'YYYYMM'))
     AND cdr.class = pricing.class
ORDER BY cdr.time, cdr.class desc
EOM;

$tquery=<<<EOM
SELECT MAX(p.MONTH) AS MONTH
  FROM SKY_PRICING p, ORDERS2 o
 WHERE p.MONTH <= TO_CHAR(TO_DATE(:mymonth,'YYYYMMDDhh24:mi:ss'),'YYYYMM')
   AND p.ISSUER = o.ISSUER
   AND p.PCODE  = o.PCODE
   AND (o.BEGINDATE >= p.SDATE AND o.BEGINDATE <= p.EDATE)
   AND o.USR=:myusr
EOM;
$tquery=<<<EOM
  SELECT TO_CHAR(cdr.time+(cdr.TZOFFSET/86400),'DD-Mon-YYYY') AS MYDATE,
         TO_CHAR(cdr.time+(cdr.TZOFFSET/86400),'HH24:MI:SS') AS MYTIME,
         cdr.SD,
         TRIM(TO_CHAR(ROUND(cdr.sv/1048576,2),'999990.90')) AS VOLUME,
         cdr.CLASS,
         DECODE(cdr.zone,2,'HZ','NZ') AS ZONE,
         TO_CHAR(cdr.SV * pricing.weight/p.unit_sv/100000,'999990.9990') AS KOHLE
    FROM SKY_CDR cdr, SKY_PARAMETER p, SKY_PRICING pricing, ORDERS2 u, SKY_ISSUER i
   WHERE cdr.usr        = :myusr
     AND u.usr          = cdr.usr
     AND cdr.TIME+(cdr.TZOFFSET/86400) BETWEEN TO_DATE(:sd,'YYYYMMDDHH24:MI:SS') AND TO_DATE(:ed,'YYYYMMDDHH24:MI:SS')
     AND u.issuer       = pricing.issuer
     AND u.issuer       = i.id
     AND cdr.zone       = pricing.zone
     AND pricing.month  = :brmax
     AND pricing.pcode  = u.pcode
     AND u.BEGINDATE BETWEEN pricing.SDATE AND pricing.EDATE
     AND p.month = (SELECT MAX(month) FROM SKY_PARAMETER WHERE month <= TO_CHAR(TO_DATE(:mymonth,'YYYYMMDDhh24:mi:ss'),'YYYYMM'))
     AND cdr.class = pricing.class
     AND cdr.TIME+(cdr.TZOFFSET/86400) BETWEEN u.BEGINDATE AND TO_DATE('31.12.9000 23:59:59','dd.mm.yyyy hh24:mi:ss')
ORDER BY cdr.time, cdr.class desc
EOM;

echo("<pre>\n");



$resarray = GetBindVars($tquery);
print_r($resarray);
*/

require("oci8_class.php");
$db = new db_oci8;
$db->Connect('SCOTT','TIGER');
$query=<<<EOM
SELECT COUNT(*) AS ANZ FROM DEPT
EOM;
$result = $db->QueryHash($query);
$db->Disconnect();
print_r($result);
?>
