# db_oci8 - Oracle OCI8 Class for PHP 5/7

### V1.1.0 
##### 11-Aug-2018
- Moved class to namespace spfalz
- Fixed all warnings from code profiler
- Prepared class for release on composer

### V1.09 
##### 31-Mar-2018
- Replaced all each() calls with foreach to remove PHP 7.2.x deprecation warnings

### V1.08 
##### 03-Feb-2017
- Next public release as 1.08
- Added missing AdminEmail class variable

### V1.07 
##### 11-Feb-2016
- Print_error(): If RETURN_ALL_ERRORS is active, set both errstr and errnum to internal class variables before return to the caller.

### V1.06 
##### 22-Mar-2015
- Added OCI8 error codes to SaveBLOB() method.

### V1.05 
##### 04-Jan-2015 
- Changed inline documentation to match phpDoc 2 standards.
- Fixed notice during wrong variable in SaveBLOB()

##### 30-Oct-2014 
- Returned old showError value in setErrorHandling()

##### 07-Jan-2013
- Added Hostname detection via posix_uname() or via env var HOSTNAME - used for cli based scripts

### V1.04 
##### 25-Jul-2013
- Next public release as V1.04

##### 30-May-2013
- Added new methods "SetAction()", "SetClientInfo()" and "SetClientIdentifier()"

### V1.03 
##### 06-Jan-2012
- Added bind var support to SaveBLOB() method.

### V1.02 
##### 29-Dec-2011
- Next public release as 1.02

##### 08-Dec-2011
- Added missing error handling in case oci_parse() failed. The following methods where enhanced: 
  Query(), QueryResult(), QueryHash(), QueryResultHash()

### V1.01 
##### 30-Jun-2011
- Next public release as V1.01
- Fixed bug in examples/test_inout_vars.php

##### 20-Jun-2011

- Added missing outputVar support code to ExecuteHash()

##### 20-May-2011

- PHP5: Execute() and ExecuteHash() have now a second parameter which allows to disable automatic error handling. The obsolete class variable "no_exit" is removed.
- PHP5: All oci_* calls are now prepended with the '@' sign to avoid emitting warnings to screen output.

##### 22-Oct-2010
- PHP5: Added debug_print_backtrace() output in Print_Error(). Output is only shown if the debug level is set to show all errors.

### V1.00 
##### 07-Aug-2010

- Next public release as V1.00 .
- Added last missing method "SaveBLOB()" to PHP 5 class, updated documentation, added textfile with informations about the differences between the PHP 4 and PHP 5 classes, updated examples to fully working with both class versions. Also updated PHP 4 class to have the missing Get* methods added.

##### 15-May-2010

- Ported Query* Methods to PHP5 class.
- setPrefetch() call in QueryResult() fixed to actually use the statement handle (both classes).
- Commit() / Rollback() now both accept an external connection resource as value, if none is given the internal resource is used (both classes).


##### 14-May-2010

- Initial version of PHP5 class. Filename is renamed to "db_oci8.class.php", this way one can use both classes at once. Class object is still the same (db_oci8), but now all OCI
  calls are using the PHP5 naming scheme. Also new functionality of PHP 5.3.2 like setting the client name/module name is used if available.

### V0.78 
##### 20-Apr-2010

- This will be the last PHP 4 compatible release of this class. All future releases will support only PHP5 and the new class model, so it won't work anymore under PHP4.
- Added support for a default charset in dbdefs.inc.php with new define "OCIDB_CHARSET".
  If this define is not set the NLS_LANG value will be used.
  See README for details. The Connect() method supports this with a new parameter, too.
- Added support for session modes in the connect() method.
  This allows to perform connections as SYSDBA/SYSOPER, but requires the php.ini setting oci8.privileged_connect to be enabled. Also this works only since PHP 5.1.2!

##### 26-Jul-2009
- Added checks for the various class defines so that PHP won't complain anymore about redefines when using i.e. my MySQL class together with the OCI8 class.

##### 28-Mar-2009 
- Added new methods "getConnectRetries()" and "setConnectRetries()" which allows to change the number of connect retries the class performs until it finally gives up. This overrides the global define in dbdefs.inc.php (OCIDB_CONNECT_RETRIES).

### V0.77
##### 31-Dec-2008 (Next public release)

##### 26-Dec-2008

- Added new method "ExecuteHash()". Now all methods which have bindvariable support have their counterpart "*Hash()" to pass bind variables as associative arrays.
  Users are encouraged to use only the "*Hash()" functionality when writing new code!
- Added three new examples: "test_general.php" dumps out all defined methods inside the class, "test_dml.php" shows how to use DML (INSERT/UPDATE) with the oci8 class and
  "test_prep_execute.php" shows how to use "prepared" statements with "Prepare()" and ExecuteHash()".

##### 20-Dec-2008
- Fixed a very annoying bug when one tries to handle more than one active query with the class. For whatever reason I've stored always the internal statement handle whenever
  one of the Query() methods was called, this overrides previous values and could lead to unexpected results :( Now the class checks if there is already a statement stored and only saves the current statement handle if no other exists.
  Query()/QueryHash() no longer set the internal statement variable, so it is now safe to use multiple queries at once.
- Fixed some warnings when error_reporting is set to E_NOTICE.

### V0.76 
##### 11-Feb-2007 (First public release)
- Completed documentation, now the class is ready for public release...finally...:|
- Added a missing return value for SaveBLOB(). Now it returns 0 on successful completition.
- Added new example "test_inout_vars.php" which shows how to use IN/OUT variables in PL/SQL procedures.

### V0.75 
##### 15-Dec-2006
- Added functionality to retry connection attempts in case of an error - This is required here at work because we have some network problems which result in connection failures. See dbdefs.inc.php to define the retry counter.

### V0.74 
##### 02-Dec-2006
- Added new examples "test_desctable.php", "test_query.php", "test_queryresult.php".

##### 03-Nov-2006
- Added define OCIDB_MAIL_EXTRAARGS which allows to add additional email header to the mail() command. Default is nothing for this define.
- Removed method setErrorDisplay() as this is a double method, use setErrorHandling() instead.
- Renamed method setConnectType() to setPConnect() as this describes much better what exactly this method is doing.

### V0.73 
##### 22-Aug-2006
- Added support for persistant connections. The class provides one new method called "setConnectType()" and also supports one new define from dbdefs.inc.php called "OCIDB_USE_PCONNECT". If this define is set to 1 the class always uses persistant, else normal connections.

### V0.72 
##### 30-Mar-2006
- Class constructor now supports passing of an alternate config file, this allows to have multiple configuration files when still using only one copy of the class.

### V0.71 
##### 28-Mar-2006
- QueryResultHash() now returns the $this->stmt handle, as all other functions do. Reported by ANDL.

### V0.70 
##### 22-Mar-2006
- If RETURN_ALL_ERRORS is set and an error occures the lass still sends an email to the maintainer (if configured). This is now fixed, emails are only sent if RETURN_ALL_ERRORS is NOT set.

### V0.69 
##### 14-Feb-2006
- QueryHash() extended with support for full output hash support. To supply your output hash a new method "setOutputHash()" has been added and to clear up the
  output hash (which is not done by the class!) another method called "clearOutputHash()" has been added.

### v0.68 
##### 26-Jan-2006
- Added timing for all Oracle operations. Use GetQueryTime() to return the time required.

### V0.67 
##### 16-Jan-2006
- PrintError() and other dump functions now support both associative and numeric arrays. Also fixed some error messages.

### V0.66 
##### 11-Jan-2006
- Print_Error() now detects if the class is running under CLI sapi, in this case no HTML code is used for formatting the error messages.

### V0.65
##### 07-Jan-2006
- QueryResultHash() now prints the SQL query when DEBUG mode is active and the Query counter is now also correctly incremented.

### V0.64
##### 01-Dec-2005
- Added new configuration parameter OCIDB_SENTMAILONERROR as flag indicating if in case of an error a error message should be sent to defined email address.
  Set to 1 to enable this. If not given or set to 0 no email is generated.

### V0.63 
##### 29-Nov-2005
- QueryHash() had a wrong function definition, the reference '&' was missing for the supplied hash array which results in no data being returned ARGH!

### V0.62 
##### 18-Aug-2005
- Added function QueryHash(), now both QueryResult() and Query() have their counterpart with assoc. array support.
- Added new define "OCIDB_ADMINEMAIL" which allows to set an email address to be shown in error messages. If not set the SERVER_ADMIN address is used.

### V0.61 
##### 10-Jul-2005
- Method "DescTable()" now increments internal query counter, too.
- Added additional define "DB_SET_NUMERIC", only if this is set to a value of 1 the DB_NUM_DECIMAL and DB_NUM_GROUPING characters are set. If define is not set or 0 these characters won't be set.
- DB_REGISTER is no longer mandatory, if not set the class simply does no register anymore instead of exiting.
- Renamed function "QueryInOutHash" to "QueryResultHash" This was hopefully the last major change before the class can be released to the public.

### V0.60
##### 13-Jun-2005
- Fixed some debug output problems

### V0.59 
##### 08-Jun-2005
- Renamed defines DB_HOST,DB_USER,DB_PASS and APPNAME to OCIDB_HOST,OCIDB_USER,OCIDB_PASS and OCIAPPNAME to avoid name clashes with my MySQL class when both are used in parallel.
- Fixed various bugs when errors are encountered during wrong configured dbdefs.inc.php file.

##### 01-Jun-2005
- Print_Error() calls $this->Rollback() before disconnecting from Database. This provides a consistent behavour in error conditions.

### V0.58 
##### 27-Apr-2005
- Added new method setPrefetch() which allows to control the number of rows to prefetch. Calls OCISetPrefetch()

### V0.57 
##### 18-Apr-2005
- Fine-tuned the error handling, it is now possible to degrade the level of information shown to the user in case of an error.

### V0.56 
##### 11-Apr-2005
- Added support for phpDocumentor and CVS
