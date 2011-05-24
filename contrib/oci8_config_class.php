<?php
/**
 * This class provides methods to read/save config values to a Oracle based table.
 * The configuration can be set/get either per-user or globally defined.
 * The confignames / config values are freely configurable.
 * Note that the Oracle OCI8 class is necessary to actually use this class!
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @package oci8_config_class
 * @version 0.10 (01-Jan-2008)
 * $Id: oci8_config_class.php,v 1.1 2010/08/07 13:00:48 siegel Exp $
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */

/**
 * Main class definition.
 * @package oci8_config_class
 */
class oci8_config_class extends db_oci8
  {
  /** Contains the table name to use */
  var $table_name;

  /** Contains the procedure name to use */
  var $procedure_name;

  /** Flag to indicate that the initcode has been called. */
  var $ConfigClassInit = FALSE;

   /**
   * Inserts/Updates a config value to configuration table.
   * @param $cfgname string Name of configuration item to write.
   * @param $cfgval string Value for the given configuration item.
   * @param $cfguser string Name of user for which this configuration belongs to. Defaults to NULL.
   * @return bool TRUE if all was okay else FALSE to indicate an error.
   */
  function setConfigItem($cfgname,$cfgval,$cfguser='')
    {
    $this->checkForConfigClassInit();
    $query = 'BEGIN '.$this->procedure_name.'(:name,:val,:uname); END;';
    $hash  = array();
    $hash['name']   = $cfgname;
    $hash['val']    = $cfgval;
    $hash['uname']  = $cfguser;
    $rc = $this->QueryHash($query,OCI_NUM,1,$hash);
    return(is_array($rc));
    }

  /**
   * Retrieves one config value for a given config item.
   * @param $cfgname string Name of configuration item to retrieve.
   * @param $cfguser string Name of user or NULL if not of any interest.
   * @return string The configuration value or NULL if no data exists.
   */
  function getConfigItem($cfgname, $cfguser='')
    {
    $this->checkForConfigClassInit();
    $hash = array();
    $hash['cname'] = $cfgname;
    if($cfguser == '')
      {
      $query = 'SELECT VALUE FROM '.$this->table_name.' WHERE NAME=:cname';
      }
    else
      {
      $query = 'SELECT VALUE FROM '.$this->table_name.' WHERE USERNAME=:uname AND NAME=:cname';
      $hash['uname'] = $cfguser;
      }
    $rc = $this->QueryHash($query,OCI_ASSOC,1,$hash);
    if(isset($rc['VALUE'])==false)
      {
      $rc['VALUE'] = '';
      }
    return($rc['VALUE']);
    }

  /**
   * Retrieves a list of configuration items from configuration table.
   * @param $cfgarray array Array with configitems to retrieve.
   * @param $cfguser string Name of user or NULL if not of any interest.
   * @return array Associative array of config item => config values.
   */
  function getConfigList($cfgarray, $cfguser = '')
    {
    $this->checkForConfigClassInit();
    if(is_array($cfgarray) == FALSE)
      {
      $this->Disconnect();
      die("getConfigList(): Argument error - 2nd parameter must be an array!!!");
      }
    // Before we start the query we first prefill the result with all config items from the list.
    // This way we make sure to have ALL supplied config items returned in our array, no matter if they exist inside the table.
    $result = array();
    $hash   = array();
    $inlist = '';
    for($i = 0; $i < count($cfgarray); $i++)
      {
      $result[$cfgarray[$i]] = NULL;
      $inlist.="'".$cfgarray[$i]."',";
      }
    $inlist = substr($inlist,0,strlen($inlist)-1);
    if($cfguser == '')
      {
      $query = sprintf('SELECT NAME,VALUE FROM '.$this->table_name.' WHERE NAME IN (%s)',$inlist);
      }
    else
      {
      $query = sprintf("SELECT NAME,VALUE FROM ".$this->table_name." WHERE USERNAME=:uname AND NAME IN (%s)",$inlist);
      $hash['uname']  = $cfguser;
      }
    $this->QueryResultHash($query,$hash);
    while($c = $this->FetchResult())
      {
      $result[$c['NAME']] = $c['VALUE'];
      }
    $this->FreeResult();
    return($result);
    }

  /**
   * Retrieves a value from _REQUEST, compares it with the stored one and updates DB if necessary.
   * @param $cfgname string Name of config item to check against the _REQUEST var
   * @param $request_key string Name of _REQUEST parameter to use for comparisation.
   */
  function updateConfigItemFromRequest($cfgname,$request_key,$uname='')
    {
    $this->checkForConfigClassInit();
    $dbval = $this->getConfigItem($cfgname,$uname);

    if(isset($_REQUEST[$request_key]) == true)
      {
      if($dbval != $_REQUEST[$request_key])
        {
        $this->setConfigItem($cfgname,$_REQUEST[$request_key],$uname);
        }
      $retval = $_REQUEST[$request_key];
      }
    else
      {
      $retval = ($dbval != NULL) ? $dbval : -1;
      }
    return($retval);
    }

  /**
   * Checks if the necessary database objects exists and initalises the class for usage.
   * This is the first method you have to call BEFORE actually using anything else!
   * If there are not existing this method will auto-create the objects.
   * Note that table uses UNIQUE instead of PRIMARY KEY as Username can be NULL for global settings.
   */
  function initConfigClass($tname='OCI8_CFG_CLASS_TABLE',$pname='OCI8_CFG_CLASS_SET_CONFIG')
    {
    // Set the names for the table and the procedure to internal variables:
    $this->table_name     = $tname;
    $this->procedure_name = $pname;


    $params = array('tname' => $this->table_name,
                    'pname' => $this->procedure_name
                   );
    $tst = $this->QueryHash('SELECT (SELECT COUNT(*) FROM USER_TABLES WHERE TABLE_NAME=:tname) AS TCNT, (SELECT COUNT(*) FROM USER_PROCEDURES WHERE OBJECT_NAME=:pname) AS PCNT FROM DUAL',OCI_ASSOC,0,$params);
    if(!intval($tst['TCNT']))
      {
      $table_ddl = array('','','','');
      $table_ddl[0].="CREATE TABLE ".$params['tname']." ";
      $table_ddl[0].="  (";
      $table_ddl[0].="  NAME      VARCHAR2(200)   NOT NULL,";
      $table_ddl[0].="  VALUE     VARCHAR2(200)   NOT NULL,";
      $table_ddl[0].="  USERNAME  VARCHAR2(100)   DEFAULT NULL,";
      $table_ddl[0].="  CONSTRAINT UK_".$params['tname']." UNIQUE(NAME,USERNAME)";
      $table_ddl[0].="  )";
      $table_ddl[0].=" MONITORING";
      $table_ddl[1] = "COMMENT ON TABLE ".$params['tname']." IS 'Stores all configuration items like choosen sorting, filter etc.'";
      $table_ddl[2] = "COMMENT ON COLUMN ".$params['tname'].".NAME IS 'Name of configuration item'";
      $table_ddl[3] = "COMMENT ON COLUMN ".$params['tname'].".VALUE IS 'Value of corresponding config item'";
      $table_ddl[4] = "COMMENT ON COLUMN ".$params['tname'].".USERNAME IS 'Name of user for whom this config belongs to.'";
      for($i = 0; $i < count($table_ddl); $i++)
        {
        $this->Query($table_ddl[$i]);
        }
      }
    if(!intval($tst['PCNT']))
      {
      $proc_ddl = array('');
      $proc_ddl[0] = "CREATE OR REPLACE PROCEDURE ".$params['pname']."(p_name VARCHAR2,p_val VARCHAR2,p_usr VARCHAR2)\n";
      $proc_ddl[0].= "IS\n";
      $proc_ddl[0].= "  PRAGMA AUTONOMOUS_TRANSACTION;\n";
      $proc_ddl[0].= "BEGIN\n";
      $proc_ddl[0].= "  BEGIN\n";
      $proc_ddl[0].= "    IF p_usr IS NOT NULL THEN\n";
      $proc_ddl[0].= "      INSERT INTO ".$params['tname']."(NAME,VALUE,USERNAME) VALUES(p_name,p_val,p_usr);\n";
      $proc_ddl[0].= "    ELSE\n";
      $proc_ddl[0].= "      INSERT INTO ".$params['tname']."(NAME,VALUE) VALUES(p_name,p_val);\n";
      $proc_ddl[0].= "    END IF;\n";
      $proc_ddl[0].= "  EXCEPTION\n";
      $proc_ddl[0].= "    WHEN DUP_VAL_ON_INDEX THEN\n";
      $proc_ddl[0].= "      IF p_usr IS NOT NULL THEN\n";
      $proc_ddl[0].= "        UPDATE ".$params['tname']." SET VALUE=p_val\n";
      $proc_ddl[0].= "         WHERE NAME=p_name\n";
      $proc_ddl[0].= "           AND USERNAME=p_usr;\n";
      $proc_ddl[0].= "      ELSE\n";
      $proc_ddl[0].= "        UPDATE ".$params['tname']." SET VALUE=p_val WHERE NAME=p_NAME;\n";
      $proc_ddl[0].= "      END IF;\n";
      $proc_ddl[0].= "  END;\n";
      $proc_ddl[0].= "  COMMIT COMMENT '".$params['pname']."()';\n";
      $proc_ddl[0].= "END;\n";
      for($i = 0; $i < count($proc_ddl); $i++)
        {
        $this->Query($proc_ddl[$i]);
        }
      $this->Commit();
      }
    $this->ConfigClassInit = TRUE;
    }

  /**
   * Checks if init code was called, and terminates via print_Error() if not called.
   */
  function checkForConfigClassInit()
    {
    if($this->ConfigClassInit == FALSE)
      {
      $this->sqltext = '';
      $this->sqlerr  = '';
      $this->print_Error('ERROR: METHOD "ConfigClassInit()" NOT CALLED !!!');
      $this->Disconnect();
      exit;
      }
    }

  }
?>
