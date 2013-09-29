<?php
/**
 * OCI8 class for PHP 5.1.2+.
 * This class is a wrapper for OCI8 functionality in PHP5.
 * The old oci8_class.php is intended for PHP4, this class will work ONLY with PHP 5.1.2 or higher.
 * Requires dbdefs.inc.php for global access data (user,pw,host,appname)
 * @package db_oci8
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 1.05 (07-Jan-2013)
 * $Id$
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */

/**
 * OCI8 class.
 * @package db_oci8
 */
class db_oci8
  {
  /**
   * Class version.
   * @private
   * @var string
   */
  private $classversion = '1.05';

  /**
   * Internal connection handle.
   * @access protected
   * @var resource
   */
  protected $sock = NULL;

  /**
   * The TNS name of the target database.
   * @access protected
   * @var string
   */
  protected $host = '';

  /**
   * The username used to connect to database.
   * @access protected
   * @var string
   */
  protected $user = '';

  /**
   * The password used to connect to database.
   * @access protected
   * @var string
   */
  protected $password = '';

  /**
   * The Name of the application using this class.
   * @access protected
   * @var string
   */
  protected $appname = '';

  /**
   * Debugstate, default is OFF.
   * @access protected
   * @var integer
   */
  protected $debug = db_oci8::DBOF_DEBUGOFF;

  /**
   * How many queries where executed.
   * @private
   * @var integer
   */
  private $querycounter = 0;

  /**
   * Amount of time spent executing class methods.
   * @private
   * @var integer
   */
  private $querytime = 0.000;

  /**
   * If php ini parameter "oci8.privileged_connect" is set or not.
   * @private
   * @var boolean
   */
  private $php_with_priv_connect = FALSE;
  /**
   * Set to TRUE if Connect() should use persistant connection, else new one (Default)
   * @private
   * @var boolean
   */
  private $usePConnect = FALSE;

  /**
   * How many retries the class should perform when connecting to Oracle.
   * Defaults to 1 but can be overriden via the OCIDB_CONNECT_RETRIES define or by using the method "setConne
   * @private
   * @var integer
   */
  private $connectRetries = 1;

  /** The SAPI type of php (used to detect CLI sapi)
   * @private
   * @var string
   */
  private $SAPI_type;

  /**
   * All passed variables except QUERY and Flags.
   * @private
   * @var array
   */
  private $errvars = array();

  /**
   * TRUE if at least PHP 5.3.2 is running, required to set the various client/module/action things.
   * @private
   * @var boolean
   */
  private $php532 = FALSE;

  /**
   * How many Rows where affected by previous DML operation.
   * @private
   * @var integer
   */
  private $AffectedRows = 0;

  /**
   * Contains possible SQL query that failed.
   * @internal
   * @var string
   */
  protected $sqlerr = '';

  /** Contains oci_error['message'] info in case of an error.
   * @internal
   * @var string
   */
  protected $sqlerrmsg = '';

  /**
   * A hash array with all output parameters (used in QueryHash())
   * @private
   * @var array
   */
  private $output_hash = array();

  /**
   * Internal SQL cache for Prepare()/Execute().
   * @private
   * @var array
   */
  private $sqlcache = array();

  /** How many SQL queries have been executed.
   * @private
   * @var integer
   */
  private $sqlcount = 0;

  /**
   * Stores active statement handle.
   * @private
   * @var mixed
   */
  private $stmt = NULL;

  /** DEBUG: No Debug Info */
  const DBOF_DEBUGOFF           = 1;
  /** DEBUG: Debug to screen */
  const DBOF_DEBUGSCREEN        = 2;
  /** DEBUG: Debug to error.log */
  const DBOF_DEBUGFILE          = 4;

  /**#@+
   * These constants are used in DescTable()
   * @see DescTable()
   */
  const DBOF_COLNAME            = 0;
  const DBOF_COLTYPE            = 1;
  const DBOF_COLSIZE            = 2;
  const DBOF_COLPREC            = 3;
  /**#@-*/

  /**#@+
   * Used for internal Query Cache.
   */
  const DBOF_CACHE_QUERY        = 0;
  const DBOF_CACHE_STATEMENT    = 1;
  /**#@-*/

  /**#@+
   * Connect and error handling.
   * If NO_ERRORS is set and an error occures, the class still reports an
   * an error of course but the error shown is reduced to avoid showing
   * sensible informations in a productive environment.
   * Set RETURN_ALL_ERRORS if you want to handle errors yourself.
   */
  const DBOF_SHOW_NO_ERRORS     = 0;
  const DBOF_SHOW_ALL_ERRORS    = 1;
  const DBOF_RETURN_ALL_ERRORS  = 2;
  /**#@-*/

  /**
   * Constructor of class.
   * @param string $ext_config Pass here the full name to your define file where all external class defines are set. If empty uses "dbdefs.inc.php".
   */
  public function __construct($ext_config = '')
    {
    // Check first the PHP version, if it is lower than 5.1.2 we abort here:
    if(version_compare(phpversion(), '5.1.2', '<'))
      {
      die("ERROR: db_oci8 Class requires at least PHP 5.1.2!\n");
      }
    // Check if the OCI8 extension is loaded:
    if(function_exists("oci_connect") == FALSE)
      {
      die("ERROR: OCI8 extension not loaded in your PHP - This class requires the OCI8 extension!\n");
      }
    if($ext_config == '')
      {
      include_once('dbdefs.inc.php');
      }
    else
      {
      include_once($ext_config);
      }
    if(!defined('OCIAPPNAME'))
      {
      $this->setErrorHandling(db_oci8::DBOF_SHOW_ALL_ERRORS);
      $this->Print_Error('dbdefs.inc.php is wrong configured! Please check Class installation!');
      }
    if(defined('DB_ERRORMODE'))                     // You can set a default behavour for error handling in dbdefs.inc.php
      {
      $this->setErrorHandling(DB_ERRORMODE);
      }
    else
      {
      $this->setErrorHandling(db_oci8::DBOF_SHOW_NO_ERRORS); // Default is not to show too much informations
      }
    if(defined('OCIDB_ADMINEMAIL'))
      {
      $this->AdminEmail = OCIDB_ADMINEMAIL;         // If set use this address instead of default webmaster
      }
    if(defined('OCIDB_USE_PCONNECT') && OCIDB_USE_PCONNECT != 0)
      {
      $this->usePConnect = TRUE;
      }
    if(defined('OCIDB_CONNECT_RETRIES') && OCIDB_CONNECT_RETRIES > 1)
      {
      $this->connectRetries = OCIDB_CONNECT_RETRIES;
      }
    $this->php_with_priv_connect = (ini_get('oci8.privileged_connect') == 1) ? TRUE : FALSE;
    $this->SAPI_type = php_sapi_name();
    $this->php532 = version_compare(phpversion(), '5.3.2', '>=');
    } // __Construct()

  /**
   * Performs the connection to Oracle.
   * If anything goes wrong calls Print_Error().
   * Also an Oracle procedure is called to register the Application name
   * as defined in dbdefs.inc.php, This helps DBAs to better fine tune
   * their databases according to application needs.
   *
   * @param string $user Username used to connect to DB
   * @param string $pass Password to use for given username
   * @param string $host Hostname of database to connect to
   * @param integer $exit_on_error If set to 1 Class will automatically exit with error code, else return error array
   * @param string $use_charset Optional character set to use.
   * @param integer $session_mode Optional the session mode (OCI_SYSOPER/OCI_SYSDBA).
   * @return mixed Either the DB connection handle or an error array/exit, depending how $exit_on_error is set
   * @see oci_connect()
   * @see oci_pconnect()
   * @see dbdefs.inc.php
   * @see Print_Error()
   */
  public function Connect($user=NULL, $pass=NULL, $host=NULL, $exit_on_error = 1, $use_charset = '', $session_mode = -1)
    {
    $connquery = '';
    $connretry = 0;
    if($this->sock)
      {
      return($this->sock);
      }
    if(isset($user) && $user!=NULL)
      {
      $this->user = $user;
      }
    else
      {
      $this->user = OCIDB_USER;
      }
    if(isset($pass) && $pass!=NULL)
      {
      $this->pass = $pass;
      }
    else
      {
      $this->pass = OCIDB_PASS;
      }
    if(isset($host) && $host!=NULL)
      {
      $this->host = $host;
      }
    else
      {
      $this->host = OCIDB_HOST;
      }
    if(defined('OCIDB_CHARSET')==TRUE && OCIDB_CHARSET != '')
      {
      $use_charset = OCIDB_CHARSET;
      }
    $this->appname = OCIAPPNAME;               // Name of our Application
    // Check if PHP has privileged connection allowed, else overwrite session_mode to use OCI_DEFAULT and warn the user about this.
    if($this->php_with_priv_connect == FALSE)
      {
      if($session_mode == OCI_SYSDBA || $session_mode == OCI_SYSOPER)
        {
        trigger_error("Your PHP installation has privileged connections disabled (oci8.privileged_connect) !",E_USER_WARNING);
        }
      $session_mode = -1;
      }
    if($session_mode == -1)
      {
      $session_mode = OCI_DEFAULT;
      }
    $this->printDebug('oci_login('.sprintf("%s/%s@%s (SESSIONMODE=%d)",$this->user,$this->pass,$this->host,$session_mode).')');
    $start = $this->getmicrotime();
    do
      {
      if($this->usePConnect == TRUE)
        {
        $this->sock = @oci_pconnect($this->user,$this->pass,$this->host,$use_charset,$session_mode);
        }
      else
        {
        $this->sock = @oci_connect($this->user,$this->pass,$this->host,$use_charset,$session_mode);
        }
      if(!$this->sock && $this->connectRetries > 1)
        {
        sleep(2);   // Wait short time and retry:
        }
      $connretry++;
      }while($connretry < $this->connectRetries);
    if(!$this->sock)
      {
      $this->Print_Error('Connection to "'.$this->host.'" failed!',NULL,$exit_on_error);
      return(0);
      }
    if(defined('DB_REGISTER') && DB_REGISTER == 1)
      {
      $connquery.= $this->SetModuleAction($this->appname,'Connect',TRUE);
      }
    if(defined('DB_SET_NUMERIC') && DB_SET_NUMERIC == 1)
      {
      if(!defined('DB_NUM_DECIMAL') || !defined('DB_NUM_GROUPING'))
        {
        $this->Disconnect();
        $this->setErrorHandling(db_oci8::DBOF_SHOW_ALL_ERRORS);
        $this->Print_Error('You have to define DB_NUM_DECIMAL/DB_NUM_GROUPING in dbdefs.inc.php first !!!');
        exit;
        }
      $connquery.= " EXECUTE IMMEDIATE 'ALTER SESSION SET NLS_NUMERIC_CHARACTERS = ''".DB_NUM_DECIMAL.DB_NUM_GROUPING."'''; ";
      }
    if($connquery != "")
      {
      $dummy = "BEGIN ";
      $dummy.= $connquery;
      $dummy.= " END;";
      $this->Query($dummy,OCI_ASSOC,0);
      }
    $this->querytime+= ($this->getmicrotime() - $start);
    return($this->sock);
    } // Connect()

  /**
   * Disconnects from Oracle.
   * You may optionally pass an external link identifier.
   * @param mixed $other_sock Optionally your own connection handle to close,
   * else internal socket will be used.
   * @see oci_close()
   */
  public function Disconnect($other_sock=-1)
    {
    $start = $this->getmicrotime();
    if($other_sock!=-1)
      {
      @oci_close($other_sock);
      }
    else
      {
      if($this->sock)
        {
        @oci_close($this->sock);
        $this->sock = 0;
        }
      }
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->AffectedRows = 0;
    $this->sqlerr       = 0;
    $this->sqlerrmsg    = '';
    }

  /**
   * Sets connection behavour.
   * If FALSE class uses oci_logon to connect.
   * If TRUE class uses oci_plogon to connect (Persistant connection)
   * @param boolean $conntype TRUE => Enable persistant connections, FALSE => Disable persistant connections
   * @return boolean The previous state
   */
  public function SetPConnect($conntype)
   {
   if(is_bool($conntype)==FALSE)
     {
     return($this->usePConnect);
     }
   $oldtype = $this->usePConnect;
   $this->usePConnect = $conntype;
   return($oldtype);
   }

  /**
   * Returns current persistant connection flag.
   * @return boolean The current setting (TRUE/FALSE).
   * @since 1.00
   */
  public function GetPConnect()
    {
    return($this->usePConnect);
    }

  /**
   * Checks if we are already connected to our database.
   * If not terminates by calling Print_Error().
   * @see Print_Error
   */
  private function CheckSock()
    {
    if(!$this->sock)
      {
      return($this->Print_Error('<b>!!! NOT CONNECTED TO AN ORACLE DATABASE !!!</b>'));
      }
    }

  /**
   * Returns current connection handle.
   * Returns either the internal connection socket or -1 if no active handle exists.
   * Useful if you want to work with OCI* functions in parallel to this class.
   * @return resource Internal socket value
   */
  public function GetConnectionHandle()
    {
    return($this->sock);
    }

  /**
   * Allows to set internal socket to external value.
   * Note that the internal socket descriptor is only overriden if the class has
   * no active connection stored! If already a connection was performed the class
   * does not override it's internal handle to avoid problems!
   * @param resource $extsock The connection handle as returned from oci_login().
   */
  public function SetConnectionHandle($extsock)
    {
    if(!$this->sock)
      {
      $this->sock = $extsock;
      }
    }

  /**
   * Registers the name either throught DBMS_APPLICATION_INFO package or by calling the new PHP 5.3.2+ functions.
   * @param string $module The module name to use, Connect() passes the Application name here (max. 48 characters).
   * @param string $action Optional action info to use (max. 32 characters).
   * @param boolean $returnURL If FALSE (default) and no PHP 5.3.2 is in use, calls "DBMS_APPLICATION_INFO()" directly, else returns the call to it.
   * @return string If PHP532 is used nothing is returned, else the ready-to-use PL/SQL call to DBMS_APPLICATION_INFO.SET_MODULE().
   * @since 1.00
   */
  public function SetModuleAction($module,$action = "",$returnURL = FALSE)
    {
    if($module == '')
      {
      return('');
      }
    if($this->php532 == TRUE && function_exists("oci_set_module_name") == TRUE)
      {
      @oci_set_module_name($this->sock,$module);
      if($action != "")
        {
        $this->SetAction($action);
        }
      return('');
      }
    else
      {
      if($action == "")
        {
        $myaction = 'NULL';
        }
      else
        {
        $myaction = "'".$action."'";
        }
      if($returnURL == FALSE)
        {
        $sp = array('mod' => $this->appname, 'action' => $action);
        $this->QueryHash("BEGIN DBMS_APPLICATION_INFO.SET_MODULE(:mod,:action); END;",OCI_ASSOC,0,$sp);
        }
      else
        {
        return(" DBMS_APPLICATION_INFO.SET_MODULE('".$this->appname."',".$myaction."); ");
        }
      }
    }

  /**
   * Sets a given string as action (max. 32 bytes) to the current Oracle connection.
   * Note that this works only for PHP 5.3.2+ and OCI8 must be linked against Oracle 10g or newer, else returns TRUE without doing anything.
   * @param string $action Action info to use (max. 32 characters).
   * @return boolean TRUE on success, else FALSE.
   * @since 1.04
   */
  public function SetAction($action)
    {
    if(function_exists('oci_set_action') === TRUE)
      {
      return(@oci_set_action($this->sock,$action));
      }
    return(TRUE);
    }

  /**
   * Sets a given string as client identifier (max. 64 bytes) to the current Oracle connection.
   * Note that this works only for PHP 5.3.2+ and OCI8 must be linked against Oracle 10g or newer, else returns TRUE without doing anything.
   * @param string $cinfo Client info to use (max. 64 bytes).
   * @return boolean TRUE on success, else FALSE.
   * @since 1.04
   */
  public function SetClientInfo($cinfo)
    {
    if(function_exists('oci_set_client_info') === TRUE)
      {
      return(@oci_set_client_info($this->sock,$cinfo));
      }
    return(TRUE);
    }

  /**
   * Performs a single row query without Bindvar support.
   * Resflag can be OCI_NUM or OCI_ASSOC depending on what kind of array you want to be returned.
   * WARNING!!!!
   * All previous class versions up to 0.78 had support for Bind vars on this method! This is no longer supported
   * and if you pass more than 3 parameters to this method a warning is generated and execution is halted.
   * Please use ONLY (!) QueryHash() if you want to use bind variables.
   * @param string $querystring The query to be executed against the RDBMS
   * @param integer $resflag OCI_NUM for numeric array or OCI_ASSOC (default) for associative array result
   * @param integer $no_exit 1 => Function returns errorcode instead of calling Print_Error() or 0 => Will always call Print_Error()
   * @return array The result of the query as either associative or numeric array.
   * In case of an error can be also an assoc. array of error informations.
   */
  public function Query($querystring, $resflag = OCI_ASSOC, $no_exit = 0)
    {
    $querystring        = ltrim($querystring);    // Leading spaces seems to be a problem??
    $resarr             = array();
    $this->errvars      = array();
    $funcargs           = @func_num_args();
    $this->sqlerr       = $querystring;
    $this->AffectedRows = 0;
    $stmt               = NULL;

    $this->CheckSock();
    if($querystring == '')
      {
      return($this->Print_Error('Query(): No querystring was supplied!'));
      }
    if($funcargs > 3)
      {
      return($this->Print_Error("Support for bind variables in Query() method has been deprecated and removed! Use QueryHash() instead!"));
      }
    if($this->debug)
      {
      $this->PrintDebug($querystring);
      }
    $start = $this->getmicrotime();
    $stmt = @oci_parse($this->sock,$querystring);
    if(!$stmt)
      {
      if($no_exit)
        {
        $err = @oci_error($this->sock);
        $this->sqlerrmsg  = $err['message'];
        $this->sqlerr     = $err['code'];
        return($err['code']);
        }
      else
        {
        return($this->Print_Error('Query(): Parse failed!'));
        exit;
        }
      }
    if(!@oci_execute($stmt,OCI_DEFAULT))
      {
      if($no_exit)
        {
        $err = @oci_error($stmt);
        $this->sqlerrmsg = $err['message'];
        return($err['code']);
        }
      else
        {
        $this->stmt = $stmt;
        return($this->Print_Error('Query(): Execute failed!'));
        exit;
        }
      }
    $this->querycounter++;
    if(StriStr(substr($querystring,0,6),"SELECT"))
      {
      $resarr = @oci_fetch_array($stmt,$resflag+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
      }
    else
      {
      $res = 0;
      }
    $this->AffectedRows = @oci_num_rows($stmt);
    @oci_free_statement($stmt);
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->errvars = array();
    return($resarr);
    } // Query()

  /**
   * Performs a single row query with Bindvar support passed as associative hash.
   * Resflag can be OCI_NUM or OCI_ASSOC depending on what kind of array you want to be returned.
   * Remember to pass all required variables for all defined bind vars after the $no_exit parameter
   * as an assoc. array (Key = name of bindvar without ':', value = value to add).
   * @param string $querystring The query to be executed against the RDBMS
   * @param integer $resflag OCI_NUM for numeric array or OCI_ASSOC (default) for associative array result
   * @param integer $no_exit 1 => Function returns errorcode instead of calling Print_Error() or 0 => Will always call Print_Error()
   * @param array &$bindvarhash The bind vars as associative array (keys = bindvar names, values = bindvar values)
   * @return array The result of the query as either associative or numeric array.
   * In case of an error can be also an assoc. array of error informations.
   * @see setOutputHash()
   * @see getOutputHash()
   * @see clearOutputHash()
   */
  public function QueryHash($querystring, $resflag = OCI_ASSOC, $no_exit = 0, &$bindvarhash)
    {
    $querystring        = ltrim($querystring);    // Leading spaces seems to be a problem??
    $resarr             = array();
    $this->errvars      = array();
    $this->sqlerr       = $querystring;
    $this->AffectedRows = 0;

    $this->CheckSock();
    if($querystring == '')
      {
      return($this->Print_Error('QueryHash(): No querystring was supplied!'));
      }
    if($this->debug)
      {
      $this->PrintDebug($querystring);
      }
    $start = $this->getmicrotime();
    $stmt = @oci_parse($this->sock,$querystring);
    if(!$stmt)
      {
      if($no_exit)
        {
        $err = @oci_error($this->sock);
        $this->sqlerrmsg  = $err['message'];
        $this->sqlerr     = $err['code'];
        return($err['code']);
        }
      else
        {
        return($this->Print_Error('QueryHash(): Parse failed!'));
        exit;
        }
      }
    if(is_array($bindvarhash))
      {
      reset($bindvarhash);
      $this->errvars = $bindvarhash;
      while(list($key,$val) = each($bindvarhash))
        {
        @oci_bind_by_name($stmt,$key,$bindvarhash[$key],-1);
        }
      }
    if(count($this->output_hash))
      {
      reset($this->output_hash);
      while(list($key,$val) = each($this->output_hash))
        {
        @oci_bind_by_name($stmt,$key,$this->output_hash[$key],$val);
        }
      }
    $ret = @oci_execute($stmt,OCI_DEFAULT);
    if($ret === FALSE)
      {
      if($no_exit)
        {
        $err = @oci_error($stmt);
        $this->sqlerrmsg = $err['message'];
        return($err['code']);
        }
      else
        {
        $this->stmt = $stmt;
        return($this->Print_Error('QueryHash(): Execute failed!'));
        exit;
        }
      }
    $this->querycounter++;
    if(StriStr(substr($querystring,0,6),"SELECT"))
      {
      $resarr = @oci_fetch_array($stmt,$resflag+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
      }
    else
      {
      $res = 0;
      }
    $this->AffectedRows = @oci_num_rows($stmt);
    @oci_free_statement($stmt);
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->errvars = array();
    return($resarr);
    } // QueryHash()

  /**
   * Performs a multirow-query and returns result handle.
   * Required if you want to fetch many data rows. Does not return in case
   * of error, so no further checking is required.
   * NOTE: Bind Var support is deprecated and no longer supported, use QueryResultHash() instead!
   * @param string $querystring SQL-Statement to be executed
   * @return mixed Returns the statement handle or an error array in case of an error.
   * @see Query()
   * @see FetchResult()
   * @see FreeResult()
   * @see QueryResultHash()
   */
  public function QueryResult($querystring)
    {
    $querystring        = ltrim($querystring);    // Leading spaces seems to be a problem??
    $funcargs           = @func_num_args();
    $this->sqlerr       = $querystring;
    $this->errvars      = array();
    $this->AffectedRows = 0;
    $stmt               = NULL;

    $this->CheckSock();
    if($querystring == "")
      {
      return($this->Print_Error('QueryResult(): No querystring was supplied!'));
      }
    if($funcargs > 1)
      {
      return($this->Print_Error("ERROR: Support for bind variables in QueryResult() method has been deprecated and removed! Use QueryHash() instead!"));
      }
    if($this->debug)
      {
      $this->PrintDebug($querystring);
      }
    $start = $this->getmicrotime();
    $stmt = @oci_parse($this->sock,$querystring);
    if(!$stmt)
      {
      if($no_exit)
        {
        $err = @oci_error($this->sock);
        $this->sqlerrmsg  = $err['message'];
        $this->sqlerr     = $err['code'];
        return($err['code']);
        }
      else
        {
        return($this->Print_Error('QueryResult(): Parse failed!'));
        exit;
        }
      }
    // Check if user wishes to set a default prefetching value:
    if(defined('DB_DEFAULT_PREFETCH'))
      {
      $this->SetPrefetch(DB_DEFAULT_PREFETCH,$stmt);
      }
    if(!@oci_execute($stmt,OCI_DEFAULT))
      {
      $this->stmt = $stmt;
      return($this->Print_Error('QueryResult(): Execute failed!'));
      }
    $this->querycounter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->sqlcache[$this->sqlcount][db_oci8::DBOF_CACHE_QUERY]     = $querystring;
    $this->sqlcache[$this->sqlcount][db_oci8::DBOF_CACHE_STATEMENT] = $stmt;
    $this->sqlcount++;
    // Result set is returned, so we return it to the caller and also store it into internal class variable if another stmt isn't already stored there:
    if(is_null($this->stmt))
      {
      $this->stmt = $stmt;
      }
    return($stmt);
    } // QueryResult()

  /**
   * Executes a query with parameters passed as hash values.
   * Also IN/OUT and RETURNING INTO <...> clauses are supported.
   * You have to use FetchResult()/FreeResult() after using this function.
   * @param string $query The Query to be executed.
   * @param array &$inhash The bind vars as associative array (keys = bindvar names, values = bindvar values)
   * @return mixed Either the statement handle or an error code / calling Print_Error().
   * @see FetchResult()
   * @see FreeResult()
   */
  public function QueryResultHash($query,&$inhash)
    {
    $this->CheckSock();
    $query        = ltrim($query);    // Leading spaces seems to be a problem??
    $this->sqlerr = $query;
    if($this->debug)
      {
      $this->PrintDebug($query);
      }
    $start = $this->getmicrotime();
    if(!($stmt = @oci_parse($this->sock,$query)))
      {
      return($this->Print_Error('QueryResultHash(): Parse failed!'));
      exit;
      }
    if(is_array($inhash))
      {
      $this->errvars = $inhash;
      reset($inhash);
      while(list($key,$val) = each($inhash))
        {
        @oci_bind_by_name($stmt,$key,$inhash[$key],-1);
        }
      }
    // Check if user wishes to set a default prefetching value:
    if(defined('DB_DEFAULT_PREFETCH'))
      {
      $this->SetPrefetch(DB_DEFAULT_PREFETCH,$stmt);
      }
    if(!@oci_execute($stmt,OCI_DEFAULT))
      {
      $this->stmt = $stmt;
      return($this->Print_Error("QueryResultHash(): Execute failed!"));
      }
    $this->querycounter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    if(is_null($this->stmt))
      {
      $this->stmt = $stmt;
      }
    return($stmt);
    }

  /**
   * Fetches next datarow.
   * Returns either numeric (OCI_NUM) or associative (OCI_ASSOC) array
   * for one data row as pointed to by either internal or passed result var.
   * @param integer $resflag OCI_ASSOC => Return associative array or OCI_NUM => Return numeric array.
   * @param mixed $extstmt If != -1 then we try to fetch from that passed handle, else the class uses
   * internal saved handle. Useful if you want to perform a lot of different queries.
   * @return array The fetched datarow or NULL if no more data exist.
   * @see QueryResult()
   * @see FreeResult()
   */
  public function FetchResult($resflag = OCI_ASSOC,$extstmt = -1)
    {
    if($extstmt == -1)
      {
      $mystate = $this->stmt;
      }
    else
      {
      $mystate = $extstmt;
      }
    $start = $this->getmicrotime();
    $res = @oci_fetch_array($mystate, $resflag+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
    $this->querytime+= ($this->getmicrotime() - $start);
    return($res);
    }

  /**
   * Frees result obtained by QueryResult().
   * You may optionally pass external Result handle, if you omit this parameter
   * the internal handle is freed. This function also checks the built-in statement
   * cache for the handle and removes it from cache, too.
   * @param mixed $extstmt Optional your external saved handle to be freed.
   * @return mixed The result of oci_free_statement() is returned.
   * @see QueryResult()
   * @see FetchResult()
   * @see oci_free_statement()
   */
  public function FreeResult($extstmt = -1)
    {
    if($extstmt == -1)
      {
      $mystate = $this->stmt;
      $this->stmt = NULL;
      }
    else
      {
      $mystate = $extstmt;
      $fq = $this->SearchQueryCache($extstmt);
      if($fq != -1)
        {
        $this->RemoveFromQueryCache($fq);
        }
      }
    $this->errvars = array();
    if($mystate)
      {
      $start = $this->getmicrotime();
      $this->AffectedRows = @oci_num_rows($mystate);
      $rc = @oci_free_statement($mystate);
      $this->querytime+= ($this->getmicrotime() - $start);
      return($rc);
      }
    }

  /**
   * Searches internal query cache for given statement id.
   * Returns index of found statement id or -1 to indicate an error.
   * This function is considered private and should NOT (!) be called from outside this class!
   * @param mixed $stmt The statement handle to search for
   * @return integer The index number of the found statement or -1 if no handle could be found.
   */
  private function SearchQueryCache($stmt)
    {
    $f = 0;
    for($i = 0; $i < $this->sqlcount; $i++)
      {
      if($this->sqlcache[$i][db_oci8::DBOF_CACHE_STATEMENT] === $stmt)
        {
        return($i);
        }
      }
    return(-1);
    }

  /**
   * Removes query from cache.
   * Tries to remove a query from cache that was found by a previous call
   * to SearchQueryCache().
   * @param integer $nr Number of statement handle to be removed from cache.
   */
  private function RemoveFromQueryCache($nr)
    {
    $newdata = array();
    $lv = 0;
    for($i = 0; $i < $this->sqlcount; $i++)
      {
      if($i != $nr)
        {
        $newdata[$lv][db_oci8::DBOF_CACHE_QUERY]    = $this->sqlcache[$i][db_oci8::DBOF_CACHE_QUERY];
        $newdata[$lv][db_oci8::DBOF_CACHE_STATEMENT]= $this->sqlcache[$i][db_oci8::DBOF_CACHE_STATEMENT];
        $lv++;
        }
      }
    $this->sqlcache = $newdata;
    $this->sqlcount--;
    }

  /**
   * Returns count of affected rows.
   * Info is set in Query() and QueryResult() / FreeResult()
   * and should return the amount of rows affected by previous DML command
   * @return integer Number of affected rows of previous DML command
   */
  public function AffectedRows()
    {
    return($this->AffectedRows);
    }

  /**
   * Commits transaction.
   * @param resource $extstmt Optional an external oracle connection resource handle, else the internal one will be used.
   * @return integer The value of oci_commit() is returned.
   * @see oci_commit()
   */
  public function Commit($extstmt = -1)
    {
    if($extstmt != -1)
      {
      $mysock = $extstmt;
      }
    else
      {
      $this->CheckSock();
      $mysock = $this->sock;
      }
    if($this->debug)
      {
      $this->PrintDebug('COMMIT called');
      }
    $start = $this->getmicrotime();
    $rc = @oci_commit($mysock);
    $this->querytime+= ($this->getmicrotime() - $start);
    return($rc);
    }

  /**
   * Rollback transaction.
   * @param resource $extstmt Optional an external oracle connection resource handle, else the internal one will be used.
   * @return integer The value of oci_rollback() is returned.
   * @see oci_rollback()
   */
  public function Rollback($extstmt = -1)
    {
    if($extstmt != -1)
      {
      $mysock = $extstmt;
      }
    else
      {
      $this->CheckSock();
      $mysock = $this->sock;
      }
    if($this->debug)
      {
      $this->PrintDebug('ROLLBACK called');
      }
    $start = $this->getmicrotime();
    $rc = @oci_rollback($this->sock);
    $this->querytime+= ($this->getmicrotime() - $start);
    return($rc);
    }

  /**
   * Function allows debugging of SQL Queries.
   * $state can have these values:
   * - db_oci8::DBOF_DEBUGOFF    = Turn off debugging
   * - db_oci8::DBOF_DEBUGSCREEN = Turn on debugging on screen (every Query will be dumped on screen)
   * - db_oci8::DBOF_DEBUFILE    = Turn on debugging on PHP errorlog
   * You can mix the debug levels by adding the according defines!
   * @param integer $state The DEBUG level to set
   */
  public function SetDebug($state)
    {
    $this->debug = $state;
    }

  /**
   * Returns the current debug setting.
   * @return integer The current debug level.
   */
  public function GetDebug()
    {
    return($this->debug);
    }

  /**
   * Handles debug output according to internal debug flag.
   * @param string $msg The string to be send out to selected output.
   */
  public function PrintDebug($msg)
    {
    if(!$this->debug) return;
    $errbuf = '';
    if($this->SAPI_type != 'cli')
      {
      $crlf   = '<br>';
      $header = "<div align=\"left\" style=\"background-color:#ffffff; color:#000000\"><pre>DEBUG: %s%s</pre></div>\n";
      }
    else
      {
      $crlf   = "\n";
      $header = "DEBUG: %s%s\n";
      }
    if($this->errvars)
      {
      $errbuf = $crlf.'VARS: ';
      reset($this->errvars);
      $i = 0;
      while(list($key,$val) = each($this->errvars))
        {
        if(!is_numeric($key))
          {
          $errbuf.=sprintf("P(%s)='%s' [%d]".$crlf,($key),$val,strlen($val));
          }
        else
          {
          $errbuf.=sprintf("P%d='%s'".$crlf,($i+1),$val);
          }
        $i++;
        }
      }
    if($this->debug & db_oci8::DBOF_DEBUGSCREEN)
      {
      printf($header,$msg,$errbuf);
      }
    if($this->debug & db_oci8::DBOF_DEBUGFILE)
      {
      @error_log('DEBUG: '.$msg,0);
      if($errbuf!="")
        {
        @error_log('DEBUG: '.strip_tags($errbuf),0);
        }
      }
    }

  /**
   * Allows to en- or disable the SQL_TRACE feature of Oracle.
   * Pass TRUE to enable or FALSE to disable. When enabled all Statements of your
   * session are saved in a tracefile stored in
   * $ORACLE_BASE/admin/<DBNAME>/udump/*.trc
   * After your session disconnects use the tkprof tool to generate
   * Human-readable output from the tracefile, i.e.:
   * $> tkprof oracle_ora_7527.trc out.txt
   * Now read 'out.txt' and see what happen in Oracle!
   * @param boolean $state TRUE to enable or FALSE to disable the SQL_TRACE feature.
   */
  public function SQLDebug($state)
    {
    switch($state)
      {
      case  TRUE:   $sdebug = 'TRUE';
                    break;
      case  FALSE:  $sdebug = 'FALSE';
                    break;
      default:      return;
      }
    if($this->sock)
      {
      $this->Query('ALTER SESSION SET SQL_TRACE = '.$sdebug);
      }
    }

  /**
   * Returns hash with error informations from last query.
   * @return array Assoc. array with error informations.
   */
  public function GetSQLError()
    {
    $ehash = array();
    $ehash['err'] = $this->sqlerr;
    $ehash['msg'] = $this->sqlerrmsg;
    return($ehash);
    }

  /**
   * This method tries to get the description for a given error message.
   * Simply pass the $err['message'] field to this function, it tries to
   * extract the required informations and call $ORACLE_HOME/bin/oerr to
   * get the error description. If either the exterr or the internal sqlerrmsg
   * variables are empty this function returns: "No error found."
   * @param string The error string from oracle. Maybe empty, in this case uses internal sqlerrmsg field.
   * @return string The extracted error text.
   */
  public function GetErrorText($exterr = "")
    {
    if($exterr != "")
      {
      $checkem = $exterr;
      }
    else
      {
      $checkem = $this->sqlerrmsg;
      }
    if($checkem=="")
      {
      return("No error found as error text is empty!");
      }
    $dummy = explode(":",$checkem);   // Oracle stores errors as: XXX-YYYY: ZZZZZZZ
    if($dummy[0] == $checkem)
      {
      return("No valid error description found! (".$checkem.")");
      }
    if(is_executable("\$ORACLE_HOME/bin/oerr")==FALSE)
      {
      return("No oerr executable found!");
      }
    $test   = str_replace("-"," ",$dummy[0]);
    $cmdstr = "NLS_LANG=AMERICAN_AMERICA.WE8ISO8859P1; \$ORACLE_HOME/bin/oerr ".$test;
    $data   = @exec($cmdstr,$retdata,$retcode);
    $dummy  = @explode(",",$retdata[0]);     // Oracle stores: 01721, 00000, "..."
    return(@trim(@preg_replace("/\"/","",$dummy[2])));
    }


  /**
   * Allows to set the handling of errors.
   *
   * - db_oci8::DBOF_SHOW_NO_ERRORS    => Show no security-relevant informations
   * - db_oci8::DBOF_SHOW_ALL_ERRORS   => Show all errors (useful for development)
   * - db_oci8::DBOF_RETURN_ALL_ERRORS => No error/autoexit, just return the OCI error code.
   * @param integer $val The Error Handling mode you wish to use.
   * @see GetErrorHandling()
   */
  public function SetErrorHandling($val)
    {
    $this->showError = $val;
    }

  /**
   * Returns the current error handling mode.
   * @return integer The current error handling mode.
   * @see SetErrorHandling()
   * @since 1.00
   */
  public function GetErrorHandling()
    {
    return($this->showError);
    }

  /**
   * Returns the number of retries in case of connection problems.
   * This value can be set globally inside the dbdefs.inc.php file
   * via the OCIDB_CONNECT_RETRIES define but may be changed run-time
   * also via the "setConnectRetries()" method.
   * @return integer The retry counter value currently set.
   * @see SetConnectRetries()
   */
  public function GetConnectRetries()
    {
    return($this->connectRetries);
    }

  /**
   * Change the number of retries the class performs in case of connection problems.
   * This value is globally setable in the dbdefs.inc.php script (see define OCIDB_CONNECT_RETRIES)
   * but can be set also run-time via this method.
   * @param integer $retcnt The new number of connect retries.
   * @return integer The previous value
   * @see GetConnectRetries()
   */
  public function SetConnectRetries($retcnt)
    {
    $oldval = $this->getConnectRetries();
    $this->connectRetries = intval($retcnt);
    return($oldval);
    }

  /**
   * Returns amount of queries executed by this class.
   * @return integer How many queries are executed currently by this class.
   */
  public function GetQueryCount()
    {
    if($this->debug)
      {
      $this->PrintDebug('GetQueryCount() called');
      }
    return(intval($this->querycounter));
    }

  /**
   * Returns amount of time spend on queries executed by this class.
   * @return float Time in seconds.msecs spent in executing SQL statements.
   */
  public function GetQueryTime()
    {
    return($this->querytime);
    }

  /**
   * Returns microtime in format s.mmmmm.
   * Used to measure SQL execution time.
   * @static
   * @return float the current time in microseconds.
   */
  public static function getmicrotime()
    {
    list($usec, $sec) = explode(" ",microtime());
    return (floatval($usec) + floatval($sec));
    }

  /**
   * Returns version of this class.
   * @return string The version string in format "major.minor"
   */
  public function GetClassVersion()
    {
    return($this->classversion);
    }

  /**
   * Returns Oracle Server Version.
   * Opens an own connection if no active one exists.
   * @return string The Oracle Release Version string
   */
  public function Version()
    {
    $weopen = 0;
    if(!$this->sock)
      {
      $this->Connect();
      $weopen = 1;
      }
    if($this->debug)
      {
      $this->PrintDebug('Version() called - Self-Connect: '.$weopen);
      }
    $start = $this->getmicrotime();
    $ver = @oci_server_version($this->sock);
    $ret = explode('-',$ver);
    if($weopen) $this->Disconnect();
    $this->querycounter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return(trim($ret[0]));
    }

  /**
   * Allows to set the prefetch value when returning results.
   * Default is 1 which may lead to performance problems when data is transmitted via WAN.
   * @param integer $rows Amount of rows to be used for prefetching.
   * @param mixed $extstmt Optionally your own statement handle. If you omit this parameter the internal statement handle is used.
   * @return boolean Return value of OCISetPrefetch()
   * @see oci_set_prefetch()
   */
  function SetPrefetch($rows,$extstmt=-1)
    {
    if($extstmt == -1)
      {
      $st = $this->stmt;
      }
    else
      {
      $st = $extstmt;
      }
    if($st < 0) return($st);
    return(@oci_set_prefetch($st,$rows));
    }

  /**
   * Prints out an Oracle error.
   * Tries to highlight the buggy SQL part of the query and dumps out
   * as much informations as possible. This may lead however to
   * security problems, in this case you can set DBOF_SHOW_NO_ERRORS
   * and the Error informations are returned to the callee instead of
   * being displayed on-screen.
   * An e-mail can be send if configured, for details see dbdefs.inc.php
   *
   * @param string $ustr Optional user-error string to be displayed
   * @param mixed $var2dump Optional a variable to be dumped out via print_r()
   * @param integer $exit_on_error If set to default of 1 this function terminates
   * execution of the script by calling exit, else it simply returns.
   * @see print_r()
   * @see oci_error()
   */
  public function Print_Error($ustr='',$var2dump=NULL, $exit_on_error = 1)
    {
    if($this->stmt)
      {
      $earr = @oci_error($this->stmt);
      }
    elseif($this->sock)
      {
      $earr = @oci_error($this->sock);
      }
    else
      {
      $earr = @oci_error();
      }
    $errstr   = $earr['message'];
    $errnum   = $earr['code'];
    $sqltext  = $earr['sqltext'];
    $sqlerrpos= intval($earr['offset']);
    if($errnum == '')
      {
      $errnum = -1;
      }
    if($errstr == '')
      {
      $errstr = 'N/A';
      }
    if($sqltext=="")
      {
      if($this->sqlerr!='')
        {
        $sqltext = $this->sqlerr;
        }
      else
        {
        $sqltext = 'N/A';
        }
      }
    $this->sqlerrmsg = $errstr;
    if($this->showError == db_oci8::DBOF_RETURN_ALL_ERRORS)
      {
      return($errnum);      // Return the error number
      }
    $this->SendMailOnError($earr);
    $filename = basename($_SERVER['SCRIPT_FILENAME']);
    if($this->sock)
      {
      $this->Rollback();
      $this->Disconnect();
      }
    $crlf = "\n";
    $space= " ";
    if($this->SAPI_type != 'cli')
      {
      $crlf = "<br>\n";
      $space= "&nbsp;";
      echo("<br>\n<div align=\"left\" style=\"background-color: #EEEEEE; color:#000000\" class=\"TB\">\n");
      echo("<font color=\"red\" face=\"Arial, Sans-Serif\"><b>".$this->appname.": Database Error occured!</b></font><br>\n<br>\n<code>\n");
      }
    else
      {
      echo("\n!!! ".$this->appname.": Database Error occured !!!\n\n");
      }
    echo('CODE: '.$errnum.$crlf);
    echo('DESC: '.rtrim($errstr).$crlf);
    echo('FILE: '.$filename.$crlf);
    if($this->showError == db_oci8::DBOF_SHOW_ALL_ERRORS)
      {
      if($ustr!='')
        {
        echo('INFO: '.$ustr.$crlf);
        }
      if($sqlerrpos)
        {
        if($this->SAPI_type != 'cli')
          {
          $dummy = substr($sqltext,0,$sqlerrpos);
          $dummy.='<font color="red">'.substr($sqltext,$sqlerrpos).'</font>';
          $errquery = $dummy;
          }
        else
          {
          $errquery = $sqltext;
          }
        }
      else
        {
        $errquery = $sqltext;
        }
      if($this->SAPI_type != 'cli')
        {
        echo("BACKTRACE: <pre>");
        debug_print_backtrace();
        echo("</pre>");
        }
      else
        {
        echo("BACKTRACE:\n");
        debug_print_backtrace();
        }
      echo($space."SQL: ".$errquery.$crlf);
      echo($space."POS: ".$sqlerrpos.$crlf);
      echo("QCNT: ".$this->querycounter.$crlf);
      if(count($this->errvars))
        {
        echo("VALS: ");
        reset($this->errvars);
        $i = 0;
        $errbuf = '';
        while(list($key,$val) = each($this->errvars))
          {
          if(!is_numeric($key))
            {
            $errbuf.=sprintf("P['%s']=>'%s' [%d]".$crlf,($key),$val,strlen($val));
            }
          else
            {
            $errbuf.=sprintf("P[%d]='%s'".$crlf,($i+1),$val);
            }
          $i++;
          }
        echo($errbuf.$crlf);
        }
      if(isset($var2dump))
        {
        if($this->SAPI_type != 'cli')
          {
          echo("DUMP: <pre>");
          print_r($var2dump);
          echo("</pre>");
          }
        else
          {
          echo("DUMP:\n");
          print_r($var2dump);
          }
        }
      }
    if($this->SAPI_type != 'cli')
      {
      echo("<br>\nPlease inform <a href=\"mailto:".$this->AdminEmail."\">".$this->AdminEmail."</a> about this problem.");
      echo("</code>\n");
      echo("</div>\n");
      echo("<div align=\"right\"><small>PHP V".phpversion()." / OCI8 Class v".$this->classversion."</small></div>\n");
      @error_log($this->appname.': Error in '.$filename.': '.$ustr.' ('.chop($errstr).')',0);
      }
    else
      {
      echo("\nPlease inform ".$this->AdminEmail." about this problem.\n\nRunning on PHP V".phpversion()." / OCI8 Class v".$this->classversion."\n");
      }
    if($exit_on_error)
      {
      exit;
      }
    } // Print_Error()

  /**
   * Sends an error email.
   * If OCIDB_SENTMAILONERROR is defined and != 0 the class sent out an error report
   * to the configured email address in case of an error.
   * @param array $errarray The error array from Oracle as returned by getSQLError()
   * @see GetSQLError()
   */
  private function SendMailOnError($errarray)
    {
    if(!defined('OCIDB_SENTMAILONERROR') || OCIDB_SENTMAILONERROR == 0)
      {
      return;
      }
    $sname    = (isset($_SERVER['SERVER_NAME']) == TRUE) ? $_SERVER['SERVER_NAME'] : '';
    $saddr    = (isset($_SERVER['SERVER_ADDR']) == TRUE) ? $_SERVER['SERVER_ADDR'] : '';
    $raddr    = (isset($_SERVER['REMOTE_ADDR']) == TRUE) ? $_SERVER['REMOTE_ADDR'] : '';
    if($sname == '')
      {
      if(function_exists('posix_uname') === TRUE)
        {
        $pos = posix_uname();
        $server = $pos['nodename'];
        }
      else
        {
        $server = (isset($_ENV['HOSTNAME']) === TRUE) ? $_ENV['HOSTNAME'] : 'n/a';
        }
      }
    else
      {
      $server  = $sname.' ('.$saddr.')';
      }
    $uagent  = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if($uagent == '')
      {
      $uagent = 'n/a';
      }
    $message = "OCI8 Class: Error occured on ".date('r')." !!!\n\n";
    $message.= "  AFFECTED SERVER: ".$server."\n";
    $message.= "       USER AGENT: ".$uagent."\n";
    $message.= "       PHP SCRIPT: ".$_SERVER['SCRIPT_FILENAME']."\n";
    $message.= "   REMOTE IP ADDR: ".$raddr." (".@gethostbyaddr($raddr).")\n";
    $message.= "    DATABASE DATA: ".$this->user." @ ".$this->host."\n";
    $message.= "SQL ERROR MESSAGE: ".preg_replace("/\n|\r/","",$errarray['message'])."\n";
    $message.= "   SQL ERROR CODE: ".$errarray['code']."\n";
    $message.= "    QUERY COUNTER: ".$this->querycounter."\n";
    $message.= "        SQL QUERY:\n";
    $message.= "------------------------------------------------------------------------------------\n";
    $message.= $errarray['sqltext']."\n";
    $message.= "------------------------------------------------------------------------------------\n";
    if($this->sqlerr != $errarray['sqltext'])
      {
      $message.= "     THIS->SQLERR: ".$this->sqlerr."\n";
      }
    if(count($this->errvars))
      {
      $errbuf = '';
      reset($this->errvars);
      $i = 0;
      while(list($key,$val) = each($this->errvars))
        {
        if(!is_numeric($key))
          {
          $errbuf.=sprintf("  P['%s'] => '%s'\n",($key),$val);
          }
        else
          {
          $errbuf.=sprintf("  P[%d] = '%s'\n",($i+1),$val);
          }
        $i++;
        }
      $errbuf = substr($errbuf,0,strlen($errbuf)-1);
      $message.= "    THIS->ERRVARS: ".$errbuf."\n";
      }
    if(defined('OCIDB_MAIL_EXTRAARGS') && OCIDB_MAIL_EXTRAARGS != '')
      {
      @mail($this->AdminEmail,'OCI8 Class v'.$this->classversion.' ERROR #'.$errarray['code'].' OCCURED!',$message,OCIDB_MAIL_EXTRAARGS);
      }
    else
      {
      @mail($this->AdminEmail,'OCI8 Class v'.$this->classversion.' ERROR #'.$errarray['code'].' OCCURED!',$message);
      }
    } // SendMailOnError()

  /**
   * Describes a table by returning an array with all table info.
   * @param string $tablename Name of table you want to describe.
   * @return array A 2-dimensional array with table informations.
   * @see test_desctable.php
   */
  public function DescTable($tablename)
    {
    $retarr = array();
    $weopen = 0;
    if(!$this->sock)
      {
      $this->Connect();
      $weopen = 1;
      }
    if($this->debug)
      {
      $this->PrintDebug('DescTable('.$tablename.') called - Self-Connect: '.$weopen);
      }
    $start = $this->getmicrotime();
    $stmt = @oci_parse($this->sock,"SELECT * FROM ".$tablename." WHERE ROWNUM < 1");
    if(!$stmt)
      {
      return($this->Print_Error('DescTable(): Parse failed!'));
      exit;
      }
    @oci_execute($stmt);
    $this->querycounter++;
    $ncols = @oci_num_fields($stmt);
    for ($i = 1; $i <= $ncols; $i++)
      {
      $retarr[$i-1][db_oci8::DBOF_COLNAME] = @oci_field_name($stmt, $i);
      $retarr[$i-1][db_oci8::DBOF_COLTYPE] = @oci_field_type($stmt, $i);
      $retarr[$i-1][db_oci8::DBOF_COLSIZE] = @oci_field_size($stmt, $i);
      $retarr[$i-1][db_oci8::DBOF_COLPREC] = @oci_field_precision($stmt,$i);
      }
    @oci_free_statement($stmt);
    if($weopen)
      {
      $this->Disconnect();
      }
    $this->querytime+= ($this->getmicrotime() - $start);
    return($retarr);
    }

  /**
   * Use this function to pass output hash data to QueryHash() function.
   * This is only required if you are using RETURNING INTO clauses or OUT variables,
   * if you only use the bind variables for input (IN) you do not need to set this.
   * WARNING: You are responsible to clear the array by using clearOutputHash()!
   * @param array &$outputhash The assoc. array to use for bind var return variables
   * @see GetOutputHash()
   */
  public function SetOutputHash(&$outputhash)
    {
    $this->output_hash = $outputhash;
    }

  /**
   * Returns the contents of the output_hash variable.
   * @return array The contents of the internal output_hash variable.
   * @see SetOutputHash()
   */
  public function GetOutputHash()
    {
    return($this->output_hash);
    }

  /**
   * Clears the internal output hash array.
   * You are responsible to manage this yourself, the class only uses the variable!
   * @see SetOutputHash()
   * @see GetOutputHash()
   */
  public function ClearOutputHash()
    {
    $this->output_hash = array();
    }

  /**
   * Preparses a query but do not execute it (yet).
   * This allows to use a compiled query inside loops without having to parse it everytime.
   * All prepared() queries will be put into our own QueryCache() so we can use the Prepare()/Execute()/ExecuteHash() pair for more than one query at once.
   * @param string $querystring The Query you want to prepare (can contain bind variables).
   * @param integer $no_exit 1 => Function returns errorcode instead of calling Print_Error() or 0 => Will always call Print_Error()
   * @return mixed Either the statement handle on success or an error code / calling print_error().
   * @see test_prep_execute.php
   */
  public function Prepare($querystring, $no_exit = 0)
    {
    $querystring    = ltrim($querystring);    // Leading spaces seems to be a problem??
    $this->sqlerr   = $querystring;

    $this->checkSock();
    $start = $this->getmicrotime();
    $stmt = @oci_parse($this->sock,$querystring);
    if(!$stmt)
      {
      if($no_exit)
        {
        $err = @oci_error($this->sock);
        $this->sqlerrmsg  = $err['message'];
        $this->sqlerr     = $err['code'];
        return($err['code']);
        }
      else
        {
        return($this->Print_Error('Prepare(): Parse failed!'));
        }
      }
    if($this->debug)
      {
      $this->PrintDebug("PREPARE: #".$this->sqlcount." ".$this->sqlerr);
      }
    $this->sqlcache[$this->sqlcount][db_oci8::DBOF_CACHE_QUERY]     = $querystring;
    $this->sqlcache[$this->sqlcount][db_oci8::DBOF_CACHE_STATEMENT] = $stmt;
    $this->sqlcount++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return($stmt);
    }

  /**
   * Executes a prepare()d statement and returns the result.
   * You may then Fetch rows with FetchResult() or call FreeResult() to free your allocated result.
   * Execute() searches first our QueryCache before executing, this way we can use almost unlimited Queries at once in the Prepare/Execute pair.
   * @param mixed $stmt The statement handle to be executed.
   * @return mixed Returns result set read for FetchResult() usage or an error state depending on class setting in case of an error.
   * @param integer $no_exit 1 => Function returns errorcode instead of calling Print_Error() or 0 => Will always call Print_Error()
   * @see Prepare()
   * @see test_prep_execute.php
   */
  public function Execute($stmt,$no_exit = 0)
    {
    $f = $this->SearchQueryCache($stmt);
    if($f == -1)
      {
      return($this->Print_Error("Cannot find query for given statement #".$stmt." inside query cache!!!"));
      }
    $this->sqlerr  = $this->sqlcache[$f][db_oci8::DBOF_CACHE_QUERY];
    $this->errvars = array();
    $funcargs = @func_num_args();
    if($funcargs > 1)
      {
      return($this->Print_Error("ERROR: Support for bind variables in Execute() method has been deprecated and removed! Use ExecuteHash() instead!"));
      }
    $start = $this->getmicrotime();
    if($this->debug)
      {
      $this->PrintDebug($this->sqlerr);
      }
    if(!@oci_execute($stmt,OCI_DEFAULT))
      {
      if($no_exit)
        {
        $err = @oci_error($stmt);
        $this->sqlerrmsg = $err['message'];
        return($err['code']);
        }
      else
        {
        $this->stmt = $stmt;
        return($this->Print_Error('Execute(): Execute failed!'));
        }
      }
    $this->querycounter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return($stmt);
    }

  /**
   * Executes a prepare()d statement and returns the result.
   * You may then fetch rows with FetchResult() or call FreeResult() to free your allocated result.
   * This method is almost identical to "Execute()" with the addition that bind variables are supported via an associative array.
   * @param mixed $stmt The statement handle to be executed.
   * @param array &$bindvarhash The bind variables as associative array (key = bindvar name, value = bindvar value).
   * @param integer $no_exit 1 => Function returns errorcode instead of calling Print_Error() or 0 => Will always call Print_Error()
   * @return mixed Returns result set read for FetchResult() usage or an error state depending on class setting in case of an error.
   * @see Prepare()
   * @see test_prep_execute.php
   */
  public function ExecuteHash($stmt,&$bindvarhash,$no_exit = 0)
    {
    $f = $this->SearchQueryCache($stmt);
    if($f == -1)
      {
      return($this->Print_Error("Cannot find query for given statement #".$stmt." inside query cache!!!"));
      }
    $this->sqlerr  = $this->sqlcache[$f][db_oci8::DBOF_CACHE_QUERY];
    $this->errvars = array();
    if(is_array($bindvarhash))
      {
      reset($bindvarhash);
      $this->errvars = $bindvarhash;
      while(list($key,$val) = each($bindvarhash))
        {
        @oci_bind_by_name($stmt,$key,$bindvarhash[$key],-1);
        }
      }
    if(count($this->output_hash))
      {
      reset($this->output_hash);
      $this->errvars = $this->output_hash;
      while(list($key,$val) = each($this->output_hash))
        {
        @oci_bind_by_name($stmt,$key,$this->output_hash[$key],-1);
        }
      }
    $start = $this->getmicrotime();
    if($this->debug)
      {
      $this->PrintDebug($this->sqlerr);
      }
    if(!@oci_execute($stmt,OCI_DEFAULT))
      {
      if($no_exit)
        {
        $err = @oci_error($stmt);
        $this->sqlerrmsg = $err['message'];
        return($err['code']);
        }
      else
        {
        $this->stmt = $stmt;
        return($this->Print_Error('ExecuteHash(): Execute failed!'));
        }
      }
    $this->querycounter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return($stmt);
    }

  /**
   * Allows to save a file to a binary object field (BLOB).
   * Does not commit!
   * @param string $file_to_save Full path and filename of file to save
   * @param string $blob_table Name of Table where the blobfield resides
   * @param string $blob_field Name of BLOB field
   * @param string $where_clause Criteria to get the right row (i.e. WHERE ROWID=ABCDEF12345)
   * @param array $bind_vars If given can contain bind variable definition used in WHERE clause
   * @return integer If all is okay returns 0 else an oracle error code.
   * @since 0.41
   * @see oci_new_descriptor()
   */
  public function SaveBLOB($file_to_save, $blob_table, $blob_field, $where_clause,$bind_vars = null)
    {
    $this->checkSock();
    if($where_clause == '')
      {
      return($this->Print_Error("SaveBLOB(): WHERE clause must be non-empty, else ALL rows would be updated!!!"));
      }
    $q1 = "UPDATE ".$blob_table." SET ".$blob_field."=EMPTY_BLOB() ".$where_clause." RETURNING ".$blob_field." INTO :oralob";
    $this->sqlerr = $q1;
    $start = $this->getmicrotime();
    $lobptr = @oci_new_descriptor($this->sock, OCI_D_LOB);
    if(!($lobstmt = @oci_parse($this->sock,$q1)))
      {
      return($this->Print_Error("SaveBLOB(): Unable to parse query !!!"));
      }
    @oci_bind_by_name($lobstmt, ":oralob", $lobptr, -1, OCI_B_BLOB);
    if(is_array($bind_vars))
      {
      reset($bind_vars);
      $this->errvars = $bind_vars;
      while(list($key,$val) = each($bind_vars))
        {
        @oci_bind_by_name($lobstmt,$key,$bind_vars[$key],-1);
        }
      }
    if(!@oci_execute($lobstmt, OCI_DEFAULT))
      {
      $lobptr->free();
      @oci_free_statement($lobstmt);
      return($this->Print_Error("SaveBLOB(): Unable to retrieve empty LOB locator !!!"));
      }
    if(!$lobptr->savefile($file_to_save))
      {
      $lobptr->free();
      @oci_free_statement($lobstmt);
      return($this->Print_Error("SaveBLOB(): Cannot save LOB data !!!"));
      }
    $lobptr->free();
    @oci_free_statement($lobstmt);
    $this->query_counter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return(0);
    }

  } // End-of-class
?>
