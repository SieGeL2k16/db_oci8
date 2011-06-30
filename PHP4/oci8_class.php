<?php
/**
 * Oracle Database Class.
 *
 * Database Class provides a consistent API to communicate with MySQL or Oracle Databases.
 * This one implements the OCI8 API.
 * Requires dbdefs.inc.php for global access data (user,pw,host,appname)
 *
 * - Connect() / Disconnect()
 * - Print_Error() / GetErrorText()
 * - Query() / QueryResult() / FetchResult() / FreeResult()
 * - Version() / GetClassVersion() / GetQueryCount()
 * - Commit() / Rollback()
 * - SetDebug() / GetDebug() / PrintDebug() / SQLDebug()
 * - GetBindVars() - PRIVATE!
 * - DescTable()
 * - Prepare() / Execute() / ExecuteHash()
 * - SearchQueryCache() / RemoveFromQueryCache()
 * - checkSock()
 * - GetConnectionHandle() / SetConnectionHandle()
 * - QueryHash() / QueryResultHash()
 * - AffectedRows()
 * - setErrorHandling() / GetErrorHandling() / getSQLError()
 * - setPrefetch()
 * - getOutputHash() / setOutputHash() / clearOutputHash()
 * - SendEmailOnError()
 * - setPConnect()
 * - setConnectRetries() / getConnectRetries()
 * @package db_oci8
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.79 (30-Jun-2011)
 * $Id$
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */
/**
 * DEBUG: No Debug Info
 */
if(!defined('DBOF_DEBUGOFF'))
  {
  define('DBOF_DEBUGOFF'    , (1 << 0));
  }

/**
 * DEBUG: Debug on-screen
 */
if(!defined('DBOF_DEBUGSCREEN'))
  {
  define('DBOF_DEBUGSCREEN' , (1 << 1));
  }
/**
 * DEBUG: Debug to error_log()
 */
if(!defined('DBOF_DEBUGFILE'))
  {
  define('DBOF_DEBUGFILE'   , (1 << 2));
  }

/**#@+
 * These defines are used in DescTable()
 * @see DescTable
 */
define('DBOF_COLNAME', 0);
define('DBOF_COLTYPE', 1);
define('DBOF_COLSIZE', 2);
define('DBOF_COLPREC', 3);
/**#@-*/

/**#@+
 * Used for Query Cache (V0.38+)
 */
if(!defined('DBOF_CACHE_QUERY'))
  {
  define('DBOF_CACHE_QUERY'     , 0);
  }
if(!defined('DBOF_CACHE_STATEMENT'))
  {
  define('DBOF_CACHE_STATEMENT' , 1);
  }
/**#@-*/

/**#@+
 * Connect and error handling (V0.57+).
 * If NO_ERRORS is set and an error occures, the class still reports an
 * an error of course but the error shown is reduced to avoid showing
 * sensible informations in a productive environment.
 * Set RETURN_ALL_ERRORS if you want to handle errors yourself.
 */
if(!defined('DBOF_SHOW_NO_ERRORS'))
  {
  define('DBOF_SHOW_NO_ERRORS'    , 0);
  define('DBOF_SHOW_ALL_ERRORS'   , 1);
  define('DBOF_RETURN_ALL_ERRORS' , 2);
  }
/**#@-*/

/**
 * OCI8 Database Class.
 * @package db_oci8
 */
class db_oci8
  {
  /** @var mixed $sock Internal connection handle. */
  var $sock;
  /** @var string $host The TNS name of the target database. */
  var $host;
  /** @var string $user The username used to connect to database. */
  var $user;
  /** @var string $user The password used to connect to database. */
  var $password;
  /** @var string $database Currently not in use. */
  var $database;
  /** @var integer $querycounter Counts amount of queries executed. */
  var $querycounter;
  /** @var float $querytime Contains the total SQL execution time in microseconds. */
  var $querytime;
  /** @var mixed $stmt Stores active statement handle. */
  var $stmt;
  /** @var string $appname Name of application that uses this class. */
  var $appname;
  /** @var integer $debug Debugstate, default is OFF. */
  var $debug;
  /** @var string $sqlerr Contains possible SQL query that failed. */
  var $sqlerr;
  /** @var string $sqlerrmsg Contains ocierror['message'] info in case of an error. */
  var $sqlerrmsg;
  /** @var array $sqlcache Internal Cache for Prepare()/Execute() calls. */
  var $sqlcache;
  /** @var string All passed variables except QUERY and Flags. */
  var $errvars;
  /** @var integer Set to 1 to not auto-exit on error (Default is 0) */
  var $no_exit;
  /** @var integer How many SQL queries have been executed */
  var $sqlcount;
  /** @var integer How many Rows where affected by previous DML operation */
  var $AffectedRows;
  /** @var integer Flag indates level of error information shown */
  var $showError;
  /** @var string Email Address for the administrator of this project */
  var $AdminEmail;
  /** @var string The SAPI type of php (used to detect CLI sapi) */
  var $SAPI_type;
  /** @var array A hash array with all output parameters (used in QueryHash()) */
  var $output_hash;
  /** @var boolean TRUE = Connect() uses Persistant connection, else new one (Default) */
  var $usePConnect;
  /**
   * @var integer How many connection retries we should try. Defaults to 1
   * @since 0.75
   */
  var $connectRetries;
  /**
   * If running on PHP 5 AND connecting to an Oracle database >= 9.2, you can give here a character set to use when connecting.
   * For older PHP or Oracle releases this parameter is ignored and the environment variable "NLS_LANG" is used instead.
   * @var string Character set to use when connecting to Oracle.
   * @since 0.78
   * @see oci_connect
   */
  /**
   * Class Constructor.
   * Whenever you instantiate this class the file dbdefs.inc.php will be included automatically.
   * This file contains the default login data and other configuration options, see description
   * inside this file for further informations.
   * Since V0.72 you may optionally give an alternate file as configuration file. If no file is
   * given inside the constructor the class still uses the file "dbdefs.inc.php" as default
   * configuration file.
   * @param string $extconfig Full path to dbdefs.inc.php, if empty class searches in current dir for dbdefs.inc.php
   * @see dbdefs.inc.php
   */
  function db_oci8($extconfig='')
    {
    if($extconfig == '')
      {
      include_once('dbdefs.inc.php');
      }
    else
      {
      include($extconfig);
      }
    $this->classversion   = '0.79';                   // Version of our class
    $this->host           = '';                       // TNS Name of DB to connect to
    $this->user           = '';                       // Username
    $this->pass           = '';                       // Password
    $this->appname        = OCIAPPNAME;               // Name of our Application
    $this->database       = '';                       // Oracle does not use this
    $this->sock           = 0;                        // Internal database handle
    $this->querycounter   = 0;                        // How many queries where executed
    $this->querytime      = 0.000;                    // Time required for all queries
    $this->stmt           = NULL;                     // Oracle Statement handler
    $this->debug          = 0;                        // Debug is off per default
    $this->sqlcache       = array();                  // Internal SQL cache for Prepare()/Execute()
    $this->sqlerr         = '';                       // Contains possible SQL query that failed
    $this->sqlerrmsg      = '';                       // Contains ocierror['message'] info
    $this->errvars        = array();                  // All passed variables except QUERY and Flags
    $this->no_exit        = 0;                        // Flag for Prepare/Execute pair to indicate if we should exit
    $this->sqlcount       = 0;                        // Counter for Prepare/Execute pair to reference correct query
    $this->AffectedRows   = 0;                        // Amount of rows processed during statement execution
    $this->showError      = 0;                        // Flag for Error processing.
    $this->AdminEmail     = (isset($_SERVER['SERVER_ADMIN'])) ? $_SERVER['SERVER_ADMIN'] : ''; // Defaults to Webadministrator of Server
    $this->SAPI_type      = @php_sapi_name();         // May contain 'cli', in this case disable HTML errors!
    $this->output_hash    = array();                  // Set to empty array in initial call
    $this->usePConnect    = FALSE;                    // Set to TRUE to use Persistant connections
    $this->connectRetries = 1;                        // How many retries we should perform when connecting to Oracle.

    if(!defined('OCIAPPNAME'))
      {
      $this->setErrorHandling(DBOF_SHOW_ALL_ERRORS);
      $this->Print_Error('dbdefs.inc.php is wrong configured! Please check Class installation!');
      }
    if(defined('DB_ERRORMODE'))                     // You can set a default behavour for error handling in dbdefs.inc.php
      {
      $this->setErrorHandling(DB_ERRORMODE);
      }
    else
      {
      $this->setErrorHandling(DBOF_SHOW_NO_ERRORS); // Default is not to show too much informations
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
    }

  /**
   * Performs the connection to Oracle.
   * If anything goes wrong calls Print_Error().
   * Also an Oracle procedure is called to register the Application name
   * as defined in dbdefs.inc.php, This helps DBAs to better fine tune
   * their databases according to application needs.
   * @see dbdefs.inc.php
   * @see Print_Error()
   * @param string $user Username used to connect to DB
   * @param string $pass Password to use for given username
   * @param string $host Hostname of database to connect to
   * @param integer $exit_on_error If set to 1 Class will automatically exit with error code, else return error array
   * @param string $use_charset Optional character set to use when PHP 5.1.2+ is running.
   * @param integer $session_mode Optional the session mode when PHP 5.1.2+ is running.
   * @return mixed Either the DB connection handle or an error array/exit, depending how $exit_on_error is set
   * @see oci_logon
   * @see oci_plogon
   */
  function Connect($user=NULL,$pass=NULL,$host=NULL,$exit_on_error = 1, $use_charset = '', $session_mode = -1)
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
    if(version_compare(phpversion(), '5.1.2', '<'))
      {
      $php512 = FALSE;
      }
    else
      {
      $php512 = TRUE;
      if(defined('OCIDB_CHARSET')==TRUE && OCIDB_CHARSET != '')
        {
        $use_charset = OCIDB_CHARSET;
        }
      if($session_mode == -1)
        {
        $session_mode = OCI_DEFAULT;
        }
      }
    $this->printDebug('OCILogon('.sprintf("%s/%s@%s (PHP512=%d|SESSIONMODE=%d)",$this->user,$this->pass,$this->host,$php512,$session_mode).')');
    $start = $this->getmicrotime();
    do
      {
      if($php512 == TRUE)
        {
        if($this->usePConnect == TRUE)
          {
          $this->sock = @OCI_Connect($this->user,$this->pass,$this->host,$use_charset,$session_mode);
          }
        else
          {
          $this->sock = @OCI_connect($this->user,$this->pass,$this->host,$use_charset,$session_mode);
          }
        }
      else
        {
        if($this->usePConnect == TRUE)
          {
          $this->sock = @OCIPLogon($this->user,$this->pass,$this->host);
          }
        else
          {
          $this->sock = @OCILogon($this->user,$this->pass,$this->host);
          }
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
      $connquery.= " DBMS_APPLICATION_INFO.SET_MODULE('".$this->appname."',NULL); ";
      }
    if(defined('DB_SET_NUMERIC') && DB_SET_NUMERIC == 1)
      {
      if(!defined('DB_NUM_DECIMAL') || !defined('DB_NUM_GROUPING'))
        {
        $this->Disconnect();
        $this->setErrorHandling(DBOF_SHOW_ALL_ERRORS);
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
    }

  /**
   * Disconnects from Oracle.
   * You may optionally pass an external link identifier
   * @param mixed $other_sock Optionally your own connection handle to close,
   * else internal socket will be used.
   * @see OCI_Logoff
   */
  function Disconnect($other_sock=-1)
    {
    $start = $this->getmicrotime();
    if($other_sock!=-1)
      {
      @OCILogoff($other_sock);
      }
    else
      {
      if($this->sock)
        {
        @OCILogoff($this->sock);
        $this->sock = 0;
        }
      }
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->AffectedRows = 0;
    $this->sqlerr       = 0;
    $this->sqlerrmsg    = '';
    }

  /**
   * Prints out an Oracle error.
   * Tries to highlight the buggy SQL part of the query and dumps out
   * as much informations as possible. This may lead however to
   * security problems, in this case you can set DBOF_SHOW_NO_ERRORS
   * and the Error informations are returned to the callee instead of
   * being displayed on-screen.
   * Since V0.64 the class is able to send an email error message, see dbdefs.inc.php
   *
   * @param string $ustr Optional user-error string to be displayed
   * @param mixed $var2dump Optional a variable to be dumped out via print_r()
   * @param integer $exit_on_error If set to default of 1 this function terminates
   * execution of the script by calling exit, else it simply returns.
   * @see print_r
   * @see oci_error
   */
  function Print_Error($ustr='',$var2dump=NULL, $exit_on_error = 1)
    {
    if($this->stmt)
      {
      $earr = @OCIError($this->stmt);
      }
    elseif($this->sock)
      {
      $earr = @OCIError($this->sock);
      }
    else
      {
      $earr = @OCIError();
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
    if($this->showError == DBOF_RETURN_ALL_ERRORS)
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
    if($this->showError == DBOF_SHOW_ALL_ERRORS)
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
    if($exit_on_error) exit;
    }

  /**
   * Performs a single row query with Bindvar support.
   * Resflag can be OCI_NUM or OCI_ASSOC depending on what kind of array you want to be returned.
   * Remember to pass all required variables for all defined bind vars after
   * the $no_exit parameter, else you will recieve errors because of wrong parameter count!
   * @param string $querystring The query to be executed against the RDBMS
   * @param integer $resflag OCI_NUM for numeric array or OCI_ASSOC (default) for associative array result
   * @param integer $no_exit 1 => Function returns errorcode instead of calling Print_Error() or 0 => Will always call Print_Error()
   * @return array The result of the query as either associative or numeric array.
   * In case of an error can be also an assoc. array of error informations.
   */
  function Query($querystring, $resflag = OCI_ASSOC, $no_exit = 0)
    {
    $querystring        = ltrim($querystring);    // Leading spaces seems to be a problem??
    $resarr             = array();
    $this->errvars      = array();
    $funcargs           = @func_num_args();
    $this->sqlerr       = $querystring;
    $this->AffectedRows = 0;
    $stmt               = NULL;

    $this->checkSock();
    if($querystring == '')
      {
      return($this->Print_Error('Query(): No querystring was supplied!'));
      }
    if($funcargs > 3)
      {
      $this->errvars = array_slice(func_get_args(),3);
      $res = $this->GetBindVars($querystring);
      if(($funcargs-3) != count($res))
        {
        return($this->Print_Error("Query(): Parameter count does not match bind var count in query! (Defined: ".count($res)." - Supplied: ".($funcargs).")",$res));
        exit;
        }
      }
    if($this->debug)
      {
      $this->PrintDebug($querystring);
      }
    $start = $this->getmicrotime();
    $stmt = @OCIParse($this->sock,$querystring);
    if(!$stmt)
      {
      return($this->Print_Error('Query(): Parse failed!'));
      exit;
      }
    if($funcargs > 3)
      {
      for($i = 3; $i < $funcargs; $i++)
        {
        $arg[$i] = @func_get_arg($i);
        @OCIBindByName($stmt,$res[$i-3],$arg[$i],-1);
        }
      }
    if(!@OCIExecute($stmt,OCI_DEFAULT))
      {
      if($no_exit)
        {
        $err = @OCIError($stmt);
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
      @OCIFetchInto($stmt,$resarr,$resflag+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
      }
    else
      {
      $res = 0;
      }
    $this->AffectedRows = @OCIRowCount($stmt);
    @OCIFreeStatement($stmt);
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->errvars = array();
    return($resarr);
    }

  /**
   * Performs a multirow-query and returns result handle.
   * Required if you want to fetch many data rows. Does not return in case
   * of error, so no further checking is required.
   * Supports also binding, see Query() for further details.
   * @param string $querystring SQL-Statement to be executed
   * @return mixed Returns the statement handle or an error array in case of an error.
   * @see Query
   * @see FetchResult
   * @see FreeResult
   */
  function QueryResult($querystring)
    {
    $querystring        = ltrim($querystring);    // Leading spaces seems to be a problem??
    $funcargs           = @func_num_args();
    $this->sqlerr       = $querystring;
    $this->errvars      = array();
    $this->AffectedRows = 0;
    $stmt               = NULL;

    $this->checkSock();
    if($querystring == "")
      {
      return($this->Print_Error('QueryResult(): No querystring was supplied!'));
      }
    if($funcargs > 1)
      {
      $this->errvars = array_slice(func_get_args(),1);
      $res = $this->GetBindVars($querystring);
      if(($funcargs-1) != count($res))
        {
        return($this->Print_Error("QueryResult(): Parameter count does not match bind var count in query! (Defined:".count($res)." - Supplied: ".($funcargs).")",$res));
        }
      }
    if($this->debug)
      {
      $this->PrintDebug($querystring);
      }
    $start = $this->getmicrotime();
    $stmt = @OCIParse($this->sock,$querystring);
    if(!$stmt)
      {
      return($this->Print_Error('QueryResult(): Parse failed!'));
      }

    // Check if user wishes to set a default prefetching value:

    if(defined('DB_DEFAULT_PREFETCH'))
      {
      $this->SetPrefetch(DB_DEFAULT_PREFETCH,$stmt);
      }

    // If we have any of the bind vars given, bind them NOW:

    if($funcargs > 1)
      {
      for($i = 1; $i < $funcargs; $i++)
        {
        $arg[$i] = @func_get_arg($i);
        @OCIBindByName($stmt,$res[$i-1],$arg[$i],-1);
        }
      }
    if(!@OCIExecute($stmt,OCI_DEFAULT))
      {
      $this->stmt = $stmt;
      return($this->Print_Error('QueryResult(): Execute failed!'));
      }
    $this->querycounter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->sqlcache[$this->sqlcount][DBOF_CACHE_QUERY]     = $querystring;
    $this->sqlcache[$this->sqlcount][DBOF_CACHE_STATEMENT] = $stmt;
    $this->sqlcount++;
    // Result set is returned, so we return it to the caller and also store it into internal class variable if another stmt isn't already stored there:
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
   * @param integer $resflag OCI_ASSOC => Return associative array or OCI_NUM => Return numeric array
   * @param mixed $extstmt If != -1 then we try to fetch from that passed handle, else the class uses
   * internal saved handle. Useful if you want to perform a lot of different queries.
   * @return array The fetched datarow or NULL if no more data exist.
   * @see QueryResult
   * @see FreeResult
   */
  function FetchResult($resflag = OCI_ASSOC,$extstmt = -1)
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
    @OCIFetchInto($mystate, $res, $resflag+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
    $this->querytime+= ($this->getmicrotime() - $start);
    return($res);
    }

  /**
   * Frees result obtained by QueryResult().
   * You may optionally pass external Result handle, if you omit this parameter
   * the internal handle is freed. This function also checks the built-in statement
   * cache for the handle and removes it from cache, too.
   * @param mixed $extstmt Optional your external saved handle to be freed.
   * @return mixed The result of OCIFreeStatement() is returned.
   * @see QueryResult
   * @see FetchResult
   * @see SearchQueryCache
   * @see RemoveFromQueryCache
   * @see OCI_Free_Statement
   */
  function FreeResult($extstmt = -1)
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
    $this->no_exit = 0;
    if($mystate)
      {
      $start = $this->getmicrotime();
      $this->AffectedRows = @OCIRowCount($mystate);
      return(@OCIFreeStatement($mystate));
      $this->querytime+= ($this->getmicrotime() - $start);
      }
    }

  /**
   * Returns Oracle Server Version.
   * Opens an own connection if no active one exists.
   * @return string The Oracle Release Version string
   */
  function Version()
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
    $ver = @OCIServerVersion($this->sock);
    $ret = explode("-",$ver);
    if($weopen) $this->Disconnect();
    $this->querycounter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return(trim($ret[0]));
    }

  /**
   * Returns amount of queries executed by this class.
   * @return integer How many queries are executed currently by this class.
   */
  function GetQueryCount()
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
   * @since 0.68
   */
  function GetQueryTime()
    {
    return($this->querytime);
    }

  /**
   * Commits current transaction.
   * @return integer The value of OCICommit() is returned.
   * @see oci_commit
   */
  function Commit($extstmt = -1)
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
    $rc = @OCICommit($mysock);
    $this->querytime+= ($this->getmicrotime() - $start);
    return($rc);
    }

  /**
   * Rollback current transaction.
   * @return integer The value of OCIRollback() is returned.
   * @see oci_rollback
   */
  function Rollback($extstmt = -1)
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
    $rc = @OCIRollback($mysock);
    $this->querytime+= ($this->getmicrotime() - $start);
    return($rc);
    }

  /**
   * Function extracts all bind vars out of given query.
   * To avoid wrong determined bind vars this function first kills out all TO_*() functions
   * together with their (possible) format strings which results in a query
   * containing only valid bind vars, format tags or other similar constructed
   * tags are removed.
   * @param string $query The query to check for bind vars.
   * @return array Returns an array with all found bind vars in the order they are defined inside the query.
   */
  function GetBindVars($query)
    {
    $pattern = array("/(TO_.*?\()(.*?)(,)(.*?\))/is","/(TO_.*?\('.*?'\))/is","/(TO_.*?\()(.*?\))/is");
    $replace = array("$2","","$2");
    $mydummy = $query;    // Make copy of current SQL

    $mydummy = @preg_replace($pattern,$replace,$mydummy);
    @preg_match_all('/[,|\W]?(:\w+)[,|\W]?/i',$mydummy,$res);
    return($res[1]);
    }

  /**
   * Function allows debugging of SQL Queries.
   * $state can have these values:
   * - DBOF_DEBUGOFF    = Turn off debugging
   * - DBOF_DEBUGSCREEN = Turn on debugging on screen (every Query will be dumped on screen)
   * - DBOF_DEBUFILE    = Turn on debugging on PHP errorlog
   * You can mix the debug levels by adding the according defines!
   * @param integer $state The DEBUG level to set
   */
  function SetDebug($state)
    {
    $this->debug = $state;
    }

  /**
   * Returns the current debug setting.
   * @return integer The current debug level.
   * @since 0.78
   */
  function GetDebug()
    {
    return($this->debug);
    }

  /**
   * Handles debug output according to internal debug flag.
   * @param string $msg The string to be send out to selected output.
   */
  function PrintDebug($msg)
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
    if($this->debug & DBOF_DEBUGSCREEN)
      {
      printf($header,$msg,$errbuf);
      }
    if($this->debug & DBOF_DEBUGFILE)
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
  function SQLDebug($state)
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
   * Returns version of this class.
   * @return string The version string in format "major.minor"
   */
  function GetClassVersion()
    {
    return($this->classversion);
    }

  /**
   * Describes a table by returning an array with all table info.
   * @param string $tablename Name of table you want to describe.
   * @return array A 2-dimensional array with table informations.
   */
  function DescTable($tablename)
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
    $stmt = @OCIParse($this->sock,"SELECT * FROM ".$tablename." WHERE ROWNUM < 1");
    @OCIExecute($stmt);
    $this->querycounter++;
    $ncols = @OCINumCols($stmt);
    for ($i = 1; $i <= $ncols; $i++)
      {
      $retarr[$i-1][DBOF_COLNAME] = @OCIColumnName($stmt, $i);
      $retarr[$i-1][DBOF_COLTYPE] = @OCIColumnType($stmt, $i);
      $retarr[$i-1][DBOF_COLSIZE] = @OCIColumnSize($stmt, $i);
      $retarr[$i-1][DBOF_COLPREC] = @OCIColumnPrecision($stmt,$i);
      }
    @OCIFreeStatement($stmt);
    if($weopen) $this->Disconnect();
    $this->querytime+= ($this->getmicrotime() - $start);
    return($retarr);
    }

  /**
   * Preparses a query but do not execute it (yet).
   * This allows to use a compiled query inside loops without having to parse it everytime
   * Since 0.38 all prepared() queries will be put into our own QueryCache() so
   * we can use the Prepare()/Execute()/ExecuteHash() pair for more than one query at once.
   * @param string $querystring The Query you want to prepare (can contain bind variables).
   * @param integer $no_exit 1 => Function returns errorcode instead of calling Print_Error() or 0 => Will always call Print_Error()
   * @return mixed Either the statement handle on success or an error code / calling print_error().
   */
  function Prepare($querystring, $no_exit = 0)
    {
    $querystring    = ltrim($querystring);    // Leading spaces seems to be a problem??
    $this->no_exit  = $no_exit;
    $this->sqlerr   = $querystring;

    $this->checkSock();
    $start = $this->getmicrotime();
    $stmt = @OCIParse($this->sock,$querystring);
    if(!$stmt)
      {
      if($no_exit)
        {
        $err = @OCIError($this->sock);
        $this->sqlerrmsg = $err['message'];
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
    $this->sqlcache[$this->sqlcount][DBOF_CACHE_QUERY]     = $querystring;
    $this->sqlcache[$this->sqlcount][DBOF_CACHE_STATEMENT] = $stmt;
    $this->sqlcount++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return($stmt);
    }

  /**
   * Executes a prepare()d statement and returns the result.
   * You may then Fetch rows with FetchResult() or call FreeResult() to free your allocated result.
   * V0.38: Execute() searches first our QueryCache before executing, this
   * way we can use almost unlimited Queries at once in the Prepare/Execute pair
   * @param mixed $stmt The statement handle to be executed.
   * @return mixed Returns result set read for FetchResult() usage or an error state depending on class setting in case of an error.
   * @see Prepare
   */
  function Execute($stmt)
    {
    $f = $this->SearchQueryCache($stmt);
    if($f == -1)
      {
      return($this->Print_Error("Cannot find query for given statement #".$stmt." inside query cache!!!"));
      }
    $this->sqlerr  = $this->sqlcache[$f][DBOF_CACHE_QUERY];
    $this->errvars = array();
    $funcargs = @func_num_args();
    if($funcargs > 1)
      {
      $this->errvars = @array_slice(@func_get_args(),1);
      $res = $this->GetBindVars($this->sqlcache[$f][DBOF_CACHE_QUERY]);
      if(($funcargs-1) != count($res))
        {
        $this->stmt = $stmt;
        return($this->Print_Error("Execute(): Parameter count does not match bind var count in query! (Defined:".count($res)." - Supplied: ".($funcargs).")",$res));
        }
      }
    $start = $this->getmicrotime();
    if($funcargs > 1)
      {
      for($i = 1; $i < $funcargs; $i++)
        {
        $arg[$i] = @func_get_arg($i);
        @OCIBindByName($stmt,$res[$i-1],$arg[$i],-1);
        }
      }
    if($this->debug)
      {
      $this->PrintDebug($this->sqlerr);
      }
    if(!@OCIExecute($stmt,OCI_DEFAULT))
      {
      if($this->no_exit)
        {
        $err = @OCIError($stmt);
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
   * Searches internal query cache for given statement id.
   * Returns index of found statement id or -1 to indicate an error.
   * This function is considered private and should NOT (!) be called from outside this class!
   * @param mixed $stmt The statement handle to search for
   * @return integer The index number of the found statement or -1 if no handle could be found.
   * @since 0.38
   */
  function SearchQueryCache($stmt)
    {
    $f = 0;
    for($i = 0; $i < $this->sqlcount; $i++)
      {
      if($this->sqlcache[$i][DBOF_CACHE_STATEMENT] === $stmt)
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
   * @since 0.38
   */
  function RemoveFromQueryCache($nr)
    {
    $newdata = array();
    $lv = 0;
    for($i = 0; $i < $this->sqlcount; $i++)
      {
      if($i != $nr)
        {
        $newdata[$lv][DBOF_CACHE_QUERY]    = $this->sqlcache[$i][DBOF_CACHE_QUERY];
        $newdata[$lv][DBOF_CACHE_STATEMENT]= $this->sqlcache[$i][DBOF_CACHE_STATEMENT];
        $lv++;
        }
      }
    $this->sqlcache = $newdata;
    $this->sqlcount--;
    }

  /**
   * Checks if we are already connected to our database.
   * If not terminates by calling Print_Error().
   * @see Print_Error
   * @since 0.40
   */
  function checkSock()
    {
    if(!$this->sock)
      {
      return($this->Print_Error('<b>!!! NOT CONNECTED TO AN ORACLE DATABASE !!!</b>'));
      }
    }

  /**
   * Allows to save a file to a binary object field (BLOB).
   * Does not commit!
   * @param string $file_to_save Full path and filename of file to save
   * @param string $blob_table Name of Table where the blobfield resides
   * @param string $blob_field Name of BLOB field
   * @param string $where_clause Criteria to get the right row (i.e. WHERE ROWID=ABCDEF12345)
   * @return integer If all is okay returns 0 else an oracle error code.
   * @since 0.41
   */
  function SaveBLOB($file_to_save, $blob_table, $blob_field, $where_clause)
    {
    $this->checkSock();
    if($where_clause == '')
      {
      return($this->Print_Error("SaveBLOB(): WHERE clause must be non-empty, else ALL rows would be updated!!!"));
      }
    $q1 = "UPDATE ".$blob_table." SET ".$blob_field."=EMPTY_BLOB() ".$where_clause." RETURNING ".$blob_field." INTO :oralob";
    $this->sqlerr = $q1;
    $start = $this->getmicrotime();
    $lobptr = @OCINewDescriptor($this->sock, OCI_D_LOB);
    if(!($lobstmt = @OCIParse($this->sock,$q1)))
      {
      return($this->Print_Error("SaveBLOB(): Unable to parse query !!!"));
      }
    @OCIBindByName($lobstmt, ":oralob", $lobptr, -1, OCI_B_BLOB);
    if(!@OCIExecute($lobstmt, OCI_DEFAULT))
      {
      @OCIFreeStatement($lobstmt);
      @OCIFreeDesc($lobptr);
      return($this->Print_Error("SaveBLOB(): Unable to retrieve empty LOB locator !!!"));
      }
    if(!$lobptr->savefile($file_to_save))
      {
      @OCIFreeStatement($lobstmt);
      @OCIFreeDesc($lobptr);
      return($this->Print_Error("SaveBLOB(): Cannot save LOB data !!!"));
      }
    @OCIFreeDesc($lobptr);
    @OCIFreeStatement($lobstmt);
    $this->query_counter++;
    $this->querytime+= ($this->getmicrotime() - $start);
    return(0);
    }

  /**
   * Returns current connection handle.
   * Returns either the internal connection socket or -1 if no active handle exists.
   * Useful if you want to work with OCI* functions in parallel to this class.
   * @return mixed Internal socket value
   * @since 0.42
   */
  function GetConnectionHandle()
    {
    return($this->sock);
    }

  /**
   * Allows to set internal socket to external value.
   * Note that the internal socket descriptor is only overriden if the class has
   * no active connection stored! If already a connection was performed the class
   * does not override it's internal handle to avoid problems!
   * @param mixed $extsock The connection handle as returned from OCILogon().
   * @since 0.49
   */
  function SetConnectionHandle($extsock)
    {
    if(!$this->sock) $this->sock = $extsock;
    }

  /**
   * Executes a query with parameters passed as hash values.
   * Also IN/OUT and RETURNING INTO <...> clauses are supported.
   * You have to use FetchResult()/FreeResult() after using this function.
   * @param string $query The Query to be executed.
   * @param array &$inhash The bind vars as associative array (keys = bindvar names, values = bindvar values)
   * @return mixed Either the statement handle or an error code / calling Print_Error().
   * @see FetchResult
   * @see FreeResult
   * @since 0.44
   */
  function QueryResultHash($query,&$inhash)
    {
    $this->checkSock();
    $query        = ltrim($query);    // Leading spaces seems to be a problem??
    $this->sqlerr = $query;
    if($this->debug)
      {
      $this->PrintDebug($query);
      }
    $start = $this->getmicrotime();
    if(!($stmt = @OCIParse($this->sock,$query)))
      {
      return($this->Print_Error("QueryResultHash(): Unable to parse query !!!"));
      }
    if(is_array($inhash))
      {
      $this->errvars = $inhash;
      reset($inhash);
      while(list($key,$val) = each($inhash))
        {
        @OCIBindByName($stmt,$key,$inhash[$key],-1);
        }
      }
    // Check if user wishes to set a default prefetching value:
    if(defined('DB_DEFAULT_PREFETCH'))
      {
      $this->SetPrefetch(DB_DEFAULT_PREFETCH,$stmt);
      }
    if(!@OCIExecute($stmt,OCI_DEFAULT))
      {
      $this->stmt = $stmt;
      return($this->Print_Error("QueryResultHash(): Execute query failed!"));
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
   * This function tries to get the description for a given error message.
   * Simply pass the $err['message'] field to this function, it tries to
   * extract the required informations and call $ORACLE_HOME/bin/oerr to
   * get the error description. If either the exterr or the internal sqlerrmsg
   * variables are empty this function returns: "No error found."
   * @param string The error string from oracle. Maybe empty, in this case uses internal sqlerrmsg field.
   * @return string The extracted error text.
   */
  function GetErrorText($exterr = "")
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
   * Returns count of affected rows.
   * Info is set in Query() and QueryResult() / FreeResult()
   * and should return the amount of rows affected by previous DML command
   * @return integer Number of affected rows of previous DML command
   */
  function AffectedRows()
    {
    return($this->AffectedRows);
    }

  /**
   * Returns hash with error informations from last query.
   * @return array Assoc. array with error informations.
   * @since 0.55
   */
  function getSQLError()
    {
    $ehash['err'] = $this->sqlerr;
    $ehash['msg'] = $this->sqlerrmsg;
    return($ehash);
    }

  /**
   * Allows to set the handling of errors.
   *
   * - DBOF_SHOW_NO_ERRORS    => Show no security-relevant informations
   * - DBOF_SHOW_ALL_ERRORS   => Show all errors (useful for develop)
   * - DBOF_RETURN_ALL_ERRORS => No error/autoexit, just return the mysql_error code.
   * @param integer $val The Error Handling mode you wish to use.
   * @since 0.57
   */
  function setErrorHandling($val)
    {
    $this->showError = $val;
    }

  /**
   * Returns the current error handling mode.
   * @return integer The current error handling mode.
   * @see setErrorHandling()
   * @since V0.78
   */
  function GetErrorHandling()
    {
    return($this->showError);
    }

  /**
   * Allows to set the prefetch value when returning results.
   * Default is 1 which may lead to performance problems when data is transmitted via WAN.
   * @param integer $rows Amount of rows to be used for prefetching.
   * @param mixed $extstmt Optionally your own statement handle. If you omit this parameter the internal statement handle is used.
   * @return boolean Return value of OCISetPrefetch()
   * @see OCISetPrefetch()
   */
  function setPrefetch($rows,$extstmt=-1)
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
    return(@OCISetPrefetch($st,$rows));
    }

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
   * @see setOutputHash
   * @see getOutputHash
   * @see clearOutputHash
   * @since 0.62
   */
  function QueryHash($querystring, $resflag = OCI_ASSOC, $no_exit = 0, &$bindvarhash)
    {
    $querystring        = ltrim($querystring);    // Leading spaces seems to be a problem??
    $resarr             = array();
    $this->errvars      = array();
    $funcargs           = @func_num_args();
    $this->sqlerr       = $querystring;
    $this->AffectedRows = 0;

    $this->checkSock();
    if($querystring == '')
      {
      return($this->Print_Error('QueryHash(): No querystring was supplied!'));
      }
    if($this->debug)
      {
      $this->PrintDebug($querystring);
      }
    $start = $this->getmicrotime();
    $stmt = @OCIParse($this->sock,$querystring);
    if(!$stmt)
      {
      return($this->Print_Error('QueryHash(): Parse failed!'));
      exit;
      }
    if(is_array($bindvarhash))
      {
      reset($bindvarhash);
      $this->errvars = $bindvarhash;
      while(list($key,$val) = each($bindvarhash))
        {
        @OCIBindByName($stmt,$key,$bindvarhash[$key],-1);
        }
      }
    if(count($this->output_hash))
      {
      reset($this->output_hash);
      while(list($key,$val) = each($this->output_hash))
        {
        @OCIBindByName($stmt,$key,$this->output_hash[$key],$val);
        }
      }
    $ret = @OCIExecute($stmt,OCI_DEFAULT);
    if($ret === FALSE)
      {
      if($no_exit)
        {
        $err = @OCIError($stmt);
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
      @OCIFetchInto($stmt,$resarr,$resflag+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
      }
    else
      {
      $res = 0;
      }
    $this->AffectedRows = @OCIRowCount($stmt);
    @OCIFreeStatement($stmt);
    $this->querytime+= ($this->getmicrotime() - $start);
    $this->errvars = array();
    return($resarr);
    }

  /**
   * Use this function to pass output hash data to QueryHash() function.
   * This is only required if you are using RETURNING INTO clauses or OUT variables,
   * if you only use the bind variables for input (IN) you do not need to set this.
   * WARNING: You are responsible to clear the array by using clearOutputHash()!
   * @param array &$outputhash The assoc. array to use for bind var return variables
   * @see getOutputHash
   */
  function setOutputHash(&$outputhash)
    {
    $this->output_hash = &$outputhash;
    }

  /**
   * Returns the contents of the output_hash variable.
   * @return array The contents of the internal output_hash variable.
   * @see setOutputHash
   */
  function getOutputHash()
    {
    return($this->output_hash);
    }

  /**
   * Clears the internal output hash array.
   * You are responsible to manage this yourself, the class only uses the variable!
   * @see setOutputHash
   * @see getOutputHash
   */
  function clearOutputHash()
    {
    $this->output_hash = array();
    }

  /**
   * Sends an error email.
   * If OCIDB_SENTMAILONERROR is defined and != 0 the class sent out an error report
   * to the configured email address in case of an error.
   * @param array $errarray The error array from Oracle as returned by getSQLError()
   * @see getSQLError
   */
  function SendMailOnError($errarray)
    {
    if(!defined('OCIDB_SENTMAILONERROR') || OCIDB_SENTMAILONERROR == 0)
      {
      return;
      }
    $server  = $_SERVER['SERVER_NAME']." (".$_SERVER['SERVER_ADDR'].")";
    if($server == '()' || $server == '')
      {
      $server = 'n/a';
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
    $message.= "   REMOTE IP ADDR: ".$_SERVER['REMOTE_ADDR']." (".@gethostbyaddr($_SERVER['REMOTE_ADDR']).")\n";
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
    }

  /**
   * Returns microtime in format s.mmmmm.
   * Used to measure SQL execution time.
   * @return float the current time in microseconds.
   */
  function getmicrotime()
    {
    list($usec, $sec) = explode(" ",microtime());
    return (floatval($usec) + floatval($sec));
    }

  /**
   * Sets connection behavour.
   * If FALSE class uses oci_logon to connect.
   * If TRUE class uses oci_plogon to connect (Persistant connection)
   * @param boolean $conntype TRUE => Enable persistant connections, FALSE => Disable persistant connections
   * @return boolean The previous state
   * @since 0.73
   */
  function setPConnect($conntype)
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
   * @since 0.78
   */
  function GetPConnect()
    {
    return($this->usePConnect);
    }

  /**
   * Executes a prepare()d statement and returns the result.
   * You may then fetch rows with FetchResult() or call FreeResult() to free your allocated result.
   * This method is almost identical to "Execute()", however the bind variables used are passed as an associative array
   * instead of "guessing" them from query and given parameter. This is now the prefered way to use bind variables!
   * @param mixed $stmt The statement handle to be executed.
   * @param array &$bindvarhash The bind variables as associative array (key = bindvar name, value = bindvar value).
   * @return mixed Returns result set read for FetchResult() usage or an error state depending on class setting in case of an error.
   * @since 0.77
   * @see Prepare
   */
  function ExecuteHash($stmt,&$bindvarhash)
    {
    $f = $this->SearchQueryCache($stmt);
    if($f == -1)
      {
      return($this->Print_Error("Cannot find query for given statement #".$stmt." inside query cache!!!"));
      }
    $this->sqlerr  = $this->sqlcache[$f][DBOF_CACHE_QUERY];
    $this->errvars = array();
    if(is_array($bindvarhash))
      {
      reset($bindvarhash);
      $this->errvars = $bindvarhash;
      while(list($key,$val) = each($bindvarhash))
        {
        @OCIBindByName($stmt,$key,$bindvarhash[$key],-1);
        }
      }
    if(count($this->output_hash))
      {
      reset($this->output_hash);
      while(list($key,$val) = each($this->output_hash))
        {
        @OCIBindByName($stmt,$key,$this->output_hash[$key],$val);
        }
      }
    $start = $this->getmicrotime();
    if($this->debug)
      {
      $this->PrintDebug($this->sqlerr);
      }
    if(!@OCIExecute($stmt,OCI_DEFAULT))
      {
      if($this->no_exit)
        {
        $err = @OCIError($stmt);
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
   * Returns the number of retries in case of connection problems.
   * This value can be set globally inside the dbdefs.inc.php file
   * via the OCIDB_CONNECT_RETRIES define but may be changed run-time
   * also via the "setConnectRetries()" method.
   * @return integer The retry counter value currently set.
   * @since V0.78
   */
  function getConnectRetries()
    {
    return($this->connectRetries);
    }

  /**
   * Change the number of retries the class performs in case of connection problems.
   * This value is globally setable in the dbdefs.inc.php script (see define OCIDB_CONNECT_RETRIES)
   * but can be set also run-time via this method.
   * @param integer $retcnt The new number of connect retries.
   * @return integer The previous value
   * @since V0.78
   */
  function setConnectRetries($retcnt)
    {
    $oldval = $this->getConnectRetries();
    $this->connectRetries = intval($retcnt);
    return($oldval);
    }

  } // EOF
?>
