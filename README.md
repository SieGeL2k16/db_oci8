## PHP OCI8 Class written by Sascha 'SieGeL' Pfalz

Last Updated: 12-Aug-2018

###  INTRODUCTION

This class was mainly created to reduce the code size of complex PHP
applications that interact with Oracle Databases. Instead of writing down
always the same procedures like OCILogin()/OCIParse() etc. the class provides
a much simpler way of using Oracle while still supporting bind vars, BLOBs
and various other helpful methods.

This doc lists the various configuration methods and gives a small overview
how the class works, what to do in case of an error etc. For examples how
to use this class please refer to the examples directory, I have supplied
various examples that show how to use the class.
In addition you can download the "Oracle Information Site" package from
my homepage, this little application shows how to use this class in
a real world example.

Note that since the 1.00 release two versions of the class are shipped
within the distribution archive to allow either the usage of the class
under PHP 4 or PHP 5. New projects however should always use the PHP 5
class, as the PHP 4 is at end-of-life. See also the supplied file
"PHP4_PHP5_changes.txt" for details about the differences between the
two versions of this class.


#### FILES PROVIDED IN THIS RELEASE

The distribution archive contains several directories and files,
here's an overview of these files:

- **CHANGELOG.md**          -> Changelog in chronological order
- **_create_docs.sh**       -> Small bash script to create PHPDocumentor docs
- **dbdefs.inc.php**        -> Configuration file for the PHP 5 class
- **db_oci8.class.php**     -> The PHP 5 class
- **docs/**                 -> PHPDocumentor documentation in HTML format
- **examples/**             -> Contains various examples for this class
- **LICENCE**               -> The BSD licence
- **PHP4/**                 -> Contains PHP 4 class + config file
- **PHP4_PHP5_changes.txt** -> Readme explains differences between the PHP 4 and the PHP 5 version of the db_oci8 class.
- **README.md**             -> The file you are currently reading


### REQUIREMENTS

This class require the following components:

- PHP 4/5/7 (last tested on PHP 7.1.1) - Note that the distribution archive
  now ships PHP 4 AND PHP 5 versions of the class!
- Oracle 8i, 9i, 10g or 11g (last tested on 11gR2)
- Optionally the instantclient package (tested with 10g/11g/12g Instantclient)


### INSTALLATION AND USAGE

#### Using Composer

Just call the following command to add this class to your project:

`composer require spfalz/db_oci8
`

#### Manual installation

Copy the supplied class file to a directory of your choice. If you still
use PHP 4, use the file "PHP4/oci8_class.php", for PHP 5 and newer use the
file "db_oci8.class.php".

Also copy the file dbdefs.inc.php to the same directory you have copied
the class file.
The dbdefs_inc.php file serves as the configuration file for the class.
You may give an alternate path to this file in the constructor of this class.

The following defines can/must be set to use the class inside dbdefs.inc.php:

**OCIDB_USER [required]**

- Oracle Username used as default connection, mandatory.


**OCIDB_PASS [required]**

- Oracle Password for useraccount above, mandatory.


**OCIDB_HOST [optional]**

- TNS Name of target database. For correct names you may simply
  try to connect locally to your Oracle DB with SQL*Plus, the name you have
  used on the SQL*Plus connection string should also work here. If your
  environment is correctly configured you can leave out this parameter,
  the ORACLE_SID is then used to connect local to your oracle DB if your
  PHP runs on the same host as the Database. You can also set TWO_TASK to your TNS name in case you use Instantclient.


**OCIAPPNAME [required]**

- Name of your application, mandatory.
  This is used in error messages and also to register this name to Oracle
  if you set `DB_REGISTER = 1`


**DB_REGISTER [optional]**

- When set to 1 the class automatically calls the PL/SQL package
  `DBMS_APPLICATION_INFO.SET_MODULE()` to register the name of your application
  to Oracle (OCIAPPNAME define). This is useful to track Queries and stats
  inside Oracle. Set to 0 to disable auto register.
  If you are running PHP 5.3.2 or newer the class won't use anymore the
  `DBMS_APPLICATION_INFO` methods,instead the new functions
  `oci_set_module_name()` and `oci_set_action()` are used. See description
  of the class method `SetModuleAction()` for details.


**DB_NUM_DECIMAL / DB_NUM_GROUPING [optional]**

- Both flags are required to handle numeric values in localized environments,
  i.e. germans are using the '.' as grouping character and the ',' as decimal
  point, while americans do exactly the opposite. To avoid problems when your
  application makes use of `setlocale()` and other locale specific things you
  must enter the appropiate characters for your language to use. The class
  will then issue an `ALTER SESSION SET NLS_NUMERIC_CHARACTERS = <xY>` after
  successfully connecting to the database.


**DB_ERRORMODE [optional]**

- This flag is used to configure the handling of errors. Whenever an error
  occure the class automatically shows a box with a lot of informations, i.e.
  the query used, the position of the first error inside the query, the query
  counter, filename of PHP script etc, and the error is also reported to the
  PHP error log with the **OCIAPPNAME** in front of the output.
  It also may send an email to a given account to inform the developer
  automatically. Finally the last transaction is rolled back and the script
  execution is terminated.
  However it maybe not always feasible to have this automatic error handling
  active, maybe you want to handle everything on your own or you want to hide
  internal facts to the customer in productive environments.

  You have currently three choices to set here:

  **\spfalz\db_oci8::DBOF_SHOW_NO_ERRORS (0)**

   This hides important informations like the query which produced this error.
   Only the OCI error code, the PHP scriptname and the error description
   is shown, everything else is hidden. Useful for productive enviroments.
   This is also the default setting if not configured.

  **\spfalz\db_oci8::DBOF_SHOW_ALL_ERRORS (1)**

    This lists a lot more informations like query etc. Very useful when
    developing a PHP application, as this should easily show where the error
    occured. But be warned, the user may see informations he shouldn't
    normally see (query i.e.), so make sure that you disable this for
    productive environments!

  **\spfalz\db_oci8::DBOF_RETURN_ALL_ERRORS (2)**

    In this mode all errors are returned with their OCI error code, the class
    does not exit nor abort the program execution, you are on your own to
    handle the returned error. This is useful to use the class in automatic
    scripts like some kind of CSV im-/export script.


**DB_DEFAULT_PREFETCH [optional]**

- Allows to set how many rows should be prefetched when querying data. PHP's
default value is 1 row, you can set your own value here to override the default
PHP setting.


**OCIDB_ADMINEMAIL [optional]**

- Set here a valid email address where you want to have error reports sent to.
To let this work you have to set **OCIDB_SENTMAILONERROR = 1**. Whenever the
class encounters an error and automatic error handling is activated, the class
sends out an email to this address containing the error description. If not
given and **OCIDB_SENTMAILONERROR = 1** the `$_SERVER['SERVER_ADMIN']` variable
contents is used as email address.


**OCIDB_SENTMAILONERROR [optional]**

- Flag indicating if the class should auto-send emails to the defined EMail
address whenever an error occures. Set it to 1 to enable auto-sending,
and set it to 0 to disable this behavour.


**OCIDB_MAIL_EXTRAARGS  [optional]**

- Use this define to pass additional parameter to the `mail()` command in
`SendmailOnError()`. Some servers might need to set the -f parameter when
using PHP's mail() command, and to allow this also here in the class you
can use this define. Default is unset.


**OCIDB_USE_PCONNECT [optional]**

- if set to 1 persistant connections are used, else standard connects are used.
This can be set also on a script-by-script basis via the method `setPConnect()`.


**OCIDB_CONNECT_RETRIES [optional]**

- Defines how many connection retries the class should perform before giving up
with an appropiate error message. This is useful if your connection to the
database is of bad quality. The class auto-retries the connection up to the
number you define here. Default value is 1, that means that the class will
only try one connection attempt, if this fails an error is generated. This
is also the behavour of all previous class versions.


**OCIDB_CHARSET [optional]**

- Setup a default characterset to be used during the connection. Note that
this works Only for Oracle >= 9.2 and PHP 5.1.2+.
If this define is not set the NLS_LANG environment variable value is used.
You can also override this when using the `connect()` method.



The file dbdefs.inc.php is automatically included by the class once you
instantiate it the first time. See supplied file for a live example how to
use these defines.

To use the class you have to `require()` first the class code, the rest is done
automatically when you first instantiate the class. Normally you may have one
PHP script which includes several others, here would be the ideal place to put
the require() statement for the class, i.e.:


````
// ...Your require() statements

require_once("path/to/db_oci8.class.php");

// ..Rest of your code here
````

Once this is done and you have added the proper values in dbdefs.inc.php you
can now start using the class, this would look like this for example:

````
<?php
require("db_oci8.class.php");

$db = new \spfalz\db_oci8;
$db->Connect();
$mver = $db->Version();
$db->Disconnect();
echo("Your Oracle Server is V".$mver);
````

### METHOD OVERVIEW

I've provided an auto-generated method overview inside the docs subfolder of
the distribution archive generated by phpDocumentor.

You'll find below a more detailed overview and description about all existing
methods and how to use them. I recommend to read this chapter to get a better
idea how this all works.

The class provides the following methods (note that only public methods are
documented):

#### `db_oci8 __construct([string $extconfig = ''])`

This is the constructor of the class. Before you can use any of the class
functions you have to create a new instance of it.

Example:

`$db = new \spfalz\db_oci8;`

You may also give an alternate path to the database definition file:

`$db = new \spfalz\db_oci8("/path/to/your/own/dbdefs.inc.php");`

If you ommit the path to dbdefs.inc.php the class tries to include this file
from within the same directory where the class resides.


#### `integer AffectedRows ()`

Returns the amount of affected rows based on previous DML operation. Note
the word DML (Data Manipulation Language) which implies that this method
only returns values for INSERT, UPDATE or DELETE commands!
I also would like to point out that this method relies on the internal row
counter of the class. During the way Oracle works it is not that easy to
determine the affected rows, so the class keeps track of this automatically.


#### `void clearOutputHash ()`

When you are using the OutputHash functionality of `QueryHash()` and the
according queries are processed you have to clear the internal output
hash array. You are responsible to manage this yourself, the class only
uses the variable.
If you forgot to clear the hash it may happen that subsequent calls to
`QueryHash()` reuse the old data!

See **examples/test_inout_vars.php** for an example how to use this method.


#### `integer Commit ([resource $extstmt = -1])`

Commits the current transaction(s). Please note that this class does not use
**COMMIT_ON_SUCCESS**, therefor you have to either `commit()` or `rollback()` yourself
to finish your transactions.
you can pass an external oracle connection handle if you want to commit on
that handle instead of the internal one.


#### `mixed Connect ([string $user = NULL], [string $pass = NULL],[string $host = NULL], [integer $exit_on_error = 1],[string $use_charset = ""], [integer $session_mode = -1])`

Performs connection to an Oracle database server. Normally you do not have to
supply here any of the parameters, as these parameters are taken from the
**dbdefs.inc.php** file automatically.

If an error occures during the connection attempt the class either returns an
error code to the callee (if **DB_ERRORMODE** is set to **DBOF_RETURN_ALL_ERRORS**)
or prints out an error message and terminates execution.
If all goes well this method returns the connection handle. You do not have
to save this value, the class stores this handle internally and uses this
handle whenever you do not supply an handle on your own.

If you have persistant connections configured the class uses `oci_pconnect()`,
else the `oci_connect()` call is used.

If you have **OCIDB_CONNECT_RETRIES** defined to a value > 1 then the class will
retry failed connection attempts up to **OCIDB_CONNECT_RETRIES** retries until
it finally gives up. Default value for **OCIDB_CONNECT_RETRIES** is always 1.

If you set `$exit_on_error` to 0, the class won't exit in case of an
connection error but instead returns the error code to the callee.

You can pass an character set name to $use_charset which will be used during
the connect phase. This is, according to PHP docs, faster than using the
environment variable "**NLS_LANG**", so if you have at least Oracle 9i in place,
consider using this parameter or set the according global define inside
dbdefs.inc.php.

The variable $session_mode can be used to allow connections as **SYSOPER** or
**SYSDBA** user. However you have to set the PHP.ini parameter
"oci8.privileged_connect" to "on" to actually let this work. You can pass
**OCI_SYSOPER** as constant to connect as **SYSOPER** or **OCI_SYSDBA** to connect as
SYSDBA user.
Since PHP 5.3 you can also combine OCI_SYSDBA or OCI_SYSOPER with the
new value OCI_CRED_EXT to perform external/OS authentication, if this is
configured in your Oracle instance. Note that OCI_CRED_EXT is not supported
on Windows during security reasons :)


#### `array DescTable (string $tablename)`

This method describes a given table and returns the structure of the table
as array. The following fields are returned:

- 0 => Column name
- 1 => Column type
- 2 => Column size
- 3 => Column precision

You can use the class constants

- \spfalz\db_oci8::DBOF_COLNAME
- \spfalz\db_oci8::DBOF_COLTYPE
- \spfalz\db_oci8::DBOF_COLSIZE
- \spfalz\db_oci8::DBOF_COLPREC

instead of using numeric values if you wish.

Please note that this method only returns basic informations about the
structure of a table, no constraints or other meta informations are returned.
If you require more detailed metadata you have to query the according views
like USER_TAB_COLUMNS etc.

See **examples/test_desctable.php** for an example how to use this method.


#### `void Disconnect ([mixed $other_sock = -1])`

Disconnects from Oracle database. If no external connection handle is given
the class disconnects the internal connection handle, else the supplied one.
Even when PHP closes automatically all resources when a script terminates it
is always good programming practise to free all resources you've taken, so
please close the connection if you do not need it anymore.



#### `mixed Execute (mixed $stmt)`

Executes a cached statement which was previously prepared with `Prepare()`.

This method returns a result handle which you can use as parameter for the
`FetchResult()` method call.
In case of an error the return code is either an error array (if you have
set the `$no_exit` parameter to 1 - See `Prepare()`) or the class jumps to the
standard error method `Print_Error()` and returns an error code depending on
the class setting (either exits or returns ora error number).

NOTE: This function does not support BindVars! Use `ExecuteHash()` instead if
      you want to use Bindvars in your queries, which is recommended anyway!

See **examples/test_prep_execute.php** for a complete example how to use
prepared statements with `ExecuteHash()`.


#### `mixed ExecuteHash (mixed $stmt, array $bindvarhash)`

In fact the same method like `Execute()` but uses bind vars via an associative
array to pass the values to Oracle.
Except the second bindvarhash parameter this method is identical to `Execute()`.

Note that since 1.01 you can also use OutputVar functionality in the same
way you'll use it with `QueryHash()`. Useful if you `Prepare()` a lot of
statements and `ExecuteHash()` them later with the need of Output vars.

See **examples/test_prep_execute.php** for a complete example how to use
prepared statements with `ExecuteHash()`.

See **examples/test_inout_vars.php** for an example how to use Output Vars.


#### `array FetchResult ([integer $resflag = OCI_ASSOC], [mixed $extstmt = -1])`

Fetches the next data row from the statement. You can either use your own
statement handle by passing both values to this method or you can use the
internal handle, which is the default. You can specify how you wish to have
the returned data organized, either as numeric array (starting with 0) or
as associative array (the keys are the names of the columns). If no more
data exists NULL is returned, so you can easily iterate by using a `while()`
loop:

````
<?php
$db->QueryResult("SELECT ENAME FROM EMP");
while($data = $db->FetchResult())
  {
  echo("Employee: ".$data['ENAME']."<br>\n");
  }
$db->FreeResult();
````

This method can be used to fetch data from the methods `QueryResult()`,
`QueryResultHash()`, `Execute()` and `ExecuteHash()`.


#### `mixed FreeResult ([mixed $extstmt = -1])`

Frees the statement that was previously allocated by `Prepare()`, `QueryResult()`
and `QueryResultHash()`. If you did not pass an external statement handle the
class frees the internal one. After `FreeResult()` is called the preparsed
statement is no longer valid.


#### `string GetClassVersion ()`

Returns the class Version. The format of the version string is MAJOR.MINOR
versionnumber, i.e. "1.01".


#### `resource GetConnectionHandle ()`

Returns the internally saved connection handle as returned by `Connect()`. This
is useful if you want to use the oci8_* functions of PHP on an already
connected database handle. Returns -1 if no active connection handle exists.


#### `integer GetConnectRetries ()`

Returns the current number of retries the class would perform when a
connection problem occur. This can be globally set via the dbdefs.inc.php
define "**OCIDB_CONNECT_RETRIES**" or via the run-time method `SetConnectRetries()`.


#### `integer GetDebug ()`

Returns the current internal debug setting of the class. This value can be
set via the `SetDebug()` method.


#### `integer GetErrorHandling ()`

Returns the current internal error handling setting of the class.
This value can be set at run-time via the `SetDebug()` method or globally
via the dbdefs.inc.php define **DB_ERRORMODE**.


#### `string GetErrorText ([string $exterr = ""])`

This function tries to get the error description for a given error message.
Simply pass the `$err['message']` field to this function, it tries to
extract the required informations and call **$ORACLE_HOME/bin/oerr** to
get the error description.
If you omit the passed parameter the class tries to use the internal
**$sqlerrmsg** string to use.
If both the passed string and the internal sqlerrmsg variables are empty this
function returns: "No error found."

See **examples/functions.inc.php** in `CheckForDBobject()` for an example
how to use this method.
Please note that this method can only work if you are using either the
full client of Oracle or PHP is running on the database server itself!
The Instantclient installation of Oracle does not provide the executable
'oerr' which this method uses, so this method cannot work on a instant client
based installation and will return an appropiate error message.


#### `static float getmicrotime ()`

Internal function to measure times. Whenever the class performs any action
against the database server the time it took to perform the given action is
tracked and can be retrieved by calling `GetQueryTime()`. This is useful to
see how your queries perform.


#### `array GetOutputHash ()`

Retrieves the output hash variable after `QueryHash()` has been called. Before
getting the hash you have of course first set this variable by using the
method `SetOutputHash()`. If `QueryHash()` detects such an hash it uses
`oci_bind_by_name()` to bind the according keys to your query.

See **examples/test_inout_vars.php** for an example how to use this method.


#### `boolean GetPConnect()`

Returns TRUE if the class is configured to use persistant connections, else
returns FALSE if normal connections are used. This can be either set globally
via dbdefs.inc.php define **OCIDB_USE_PCONNECT** or via the run-time method
`SetPConnect()`.


#### `integer GetQueryCount ()`

Returns the current query counter. Whenever the class performs a query
against the database server an internal counter is incremented. This is
useful to track errors, as the `Print_Error()` function dumps out this value,
making it more easy to find the errornous query inside your scripts by simply
counting the queries down to the one where the error occures. Just keep in
mind that the class itself performs one query if you have either
the **DB_REGISTER** or **DB_SET_NUMERIC** defines activated. Both require to fire up
a query against Oracle, and therefor the Query counter will be always 1 after
the connect call!

See **examples/functions.inc.php** in function `DBFooter()` for an example.


#### `float GetQueryTime ()`

Returns amount of time spend on queries executed by this class.
The format is "seconds.microseconds".

See **examples/functions.inc.php** in function `DBFooter()` for an example.


#### `array GetSQLError ()`

Returns an associative array with error informations from last query executed.
The array has the following structure:

````
$ehash['err'] => OCI error code
$ehash['msg'] => Complete error description ala ORA-xxxxx: yyyyyyyy
````


#### `mixed Prepare ( $querystring, [ $no_exit = 0])`

Prepares a SQL statement and stores the statement handle in the class'
internal cache. These cached statements can be later executed with the
methods `Execute()` / `ExecuteHash()`.
This is very useful if you use i.e. an INSERT statement inside a loop.
Instead of calling `Query()` inside the loop, which involves a `oci_parse()`,
`oci_execute()` and the resulting `oci_fetch()`/`oci_free_statement()` calls you can
simply `Execute()` or `ExecuteHash()` the prepared INSERT statement, which is a
lot faster than `Query()` or `QueryHash()`.

Remember to `FreeResult()` the prepared statements!


#### `void PrintDebug (string $msg)`

Depending on the current DEBUG setting the class dumps out debugging
informations either on screen, to the error.log of PHP or to both. If debug
is not enabled this function does nothing. This is extremly useful when
tracking errors, you can simply call `SetDebug()` with an debug level of your
choice before the query in question is executed and the class dumps out the
queries, so you can track easily what happens behind the scene.

Example:

````
..
$db->SetDebug(\spfalz\db_oci8::DBOF_DEBUGSCREEN);
$db->Query('SELECT FOO FROM BAR WHERE DUMMY=1');
..
..
````

Would result in dumping out the Query on screen.


#### `void Print_Error ([string $ustr = ''], [mixed $var2dump = NULL], [integer $exit_on_error = 1])`

This method serves as the general error handling method inside the class.
Normally this method dumps out the error occured together with additional
informations like used Variables and current query etc. After displaying
these informations this method calls `exit()` and terminates execution.

However you can modify this behavour with `SetErrorHandling()`. If you have
defined `DB_ERRORMODE = \spfalz\db_oci8::DBOF_RETURN_ALL_ERRORS` no error message
is shown, instead the class returns the error code to you, and you have to
handle the error conditions on your own.

If you have set `\spfalz\db_oci8::DBOF_SHOW_NO_ERRORS` the class still displays an
error message, however the informations shown are limited so that an possible
attacker does not have all required informations in place to hack your site.
This is also default behavour.
In development environments it may useful to use the third flag
`\spfalz\db_oci8::DBOF_SHOW_ALL_ERRORS`, in this mode all possible informations are shown
including the query that produces the error and a dump of all passed variables.


#### `array Query (string $querystring, [integer $resflag = OCI_ASSOC], [integer $no_exit = 0])`

Performs a single-row query and returns result, either as numeric or as
associative array, depending on the $resflag setting.
With the $no_exit flag you can selectively instruct the class NOT to exit
in case of an error (set to 1), even if your master define **DB_ERRORMODE** has
a different setting.

NOTE: This method no longer supports any bind vars passed as 4th parameter!
      If you want to use bind vars, use the *Hash() methods provided!

See **examples/test_query.php** for an example how to use this method.


#### `array QueryHash (string $querystring, [integer $resflag = OCI_ASSOC], [integer $no_exit = 0],  [&$bindvarhash = null])`

Performs a single-row query and returns result as either numeric or
associative array. This is pretty much the same method as `Query()`, however
here you have to pass the bind variables as associative array.
This makes it easier to conditionally add new bind vars to dynamically build
queries for example.

Example:

````
<?php
..
..
$bindvars = array('empno' => 7900);
$query    = 'SELECT ENAME,JOB FROM EMP WHERE EMPNO = :empno';
$result = $db->QueryHash($query, OCI_ASSOC,0, $bindvars);
..
..
````

Another feature is the support of output bind variables which are often
used in PL/SQL procedures to return values to the callee. To use this you
have to define an associative array with the name of the output variables
as keys and the proper output types as values. After you have defined
this array you make it visible to the class by calling `SetOutputHash()`.
This method has to be called BEFORE you call `QueryHash()`.
After `QueryHash()` returned you can retrieve the result values by calling
`GetOutputHash()`.

Make sure that you define the output variables in the correct types! If the
output variable in PL/SQL defines a string, initialise it with an empty string
in your hash; if the PL/SQL defines a number, init the hash with the proper
numbers presented as 9. If you expect an 10-digit value as return value,
enter 9999999999 as init value. Larger values as returned are no problem but
smaller values will result in ORA-06502 errors!

See **examples/test_query.php** for an example how to use this method.

See **examples/test_inout_vars.php** how to use input and output variables when
calling PL/SQL code with IN/OUT variables.


#### `mixed QueryResult (string $querystring)`

Performs a multi-row query and returns a statement handle ready to pass to
`FetchResult()` / `FreeResult()`.

NOTE: This method does no longer support bind vars! If you want to use
      bind vars, use the `QueryResultHash()` method!

See **examples/test_queryresult.php** for an example how to use this method.


#### `void QueryResultHash (string $query, array &$inhash)`

Performs a multi-row query and returns a statement handle ready to pass to
`FetchResult()` / `FreeResult()`. In difference to `QueryResult()` this one supports
bind variables as an associative array.

See **examples/test_queryresult.php** for an example how to use this method.


#### `integer Rollback ([resource $extstmt = -1])`

Performs a rollback on the current transaction(s). Please note that this
class does not use **COMMIT_ON_SUCCESS**, therefor you have to either `commit()`
or `rollback()` yourself to finish your transaction(s).
Also in case of an error the class always calls `Rollback()` to have a
consistent behavour.
You can pass an external oracle connection handle to rollback that
transaction instead of using the internal connection handle.
Returns the return code from the `oci_rollback()` call.


#### `void SaveBLOB (string $file_to_save, string $blob_table, string $blob_field, string $where_clause)`

This method allows to save any binary file contents to a given table. You
have to pass the full filename of the file to be saved, the name of the
table where the BLOB field is defined, the name of the BLOB field itself and
finally the condition how to find a specific row, i.e.
**WHERE ROWID='123456789012345678'**
Take this method more as an example how to use even complex operations with
this class. Normally this method is a perfect candidate to add it into a
new class which derives from the db_oci8 class and provides additional
functionality.
In the forseen future i will release such a derived class with more methods
to handle collections and other really Oracle specific stuff.

See **examples/test_save_blob.php** for an example how to use this method.


#### `void SetConnectionHandle (mixed $extsock)`

This allows to set the class internal socket descriptor to an external value.
However the class protects itself a little bit, meaning if you have already
performed a `Connect()` and the internal socket is not zero the class did not
override then the internal value with the external one supplied!


#### `integer SetConnectRetries (integer $retcnt)`

The class has support to auto-retry a failed connection. The default value
for this retry counter is 1, so that the class will abort as soon as the
connection is failed. However sometimes it is more desirable to retry a
failed connection up to a configurable limit to bypass network outages etc.
For this you can setup the maximum amount of retries the class should
perform before giving up the connection. You can set this either globally
via the dbdefs.inc.php define "**OCIDB_CONNECT_RETRIES**" or set it at run-time
via this method. This method returns the old retry counter previously set.


#### `void SetDebug (integer $state)`

This method allows debugging of SQL Queries inside your scripts.

$state can have these values:

- **\spfalz\db_oci8::DBOF_DEBUGOFF**    = Turn off debugging
- **\spfalz\db_oci8::DBOF_DEBUGSCREEN** = Turn on debugging on screen (every Query will be dumped on screen)
- **\spfalz\db_oci8::DBOF_DEBUFILE**    = Turn on debugging on PHP errorlog

You can mix the debug levels by adding the according defines.


#### `void SetErrorHandling (integer $val)`

Allows to set the class handling of errors.

- **\spfalz\db_oci8::DBOF_SHOW_NO_ERRORS**    = Show no security-relevant informations
- **\spfalz\db_oci8::DBOF_SHOW_ALL_ERRORS**   = Show all errors (useful for development)
- **\spfalz\db_oci8::DBOF_RETURN_ALL_ERRORS** = No error/autoexit, just return the Oracle error code.


#### `string SetModuleAction (string $module, [string $action = ""], [boolean $returnURL = FALSE])`

Registers the name and optionally the action to Oracle either through the
use of the PL/SQL package "**DBMS_APPLICATION_INFO**" or via the new PHP 5.3.2
functions `oci_set_module_name()` and `oci_set_action()`.
The value of $module will be set as MODULE name, while the optional $action
value will be set as Module's current action.

If PHP 5.3.2+ is in use, an empty string is returned from this method.

In previous versions of PHP 5.3.2 the parameter $returnURL decides what
will be returned. If set to default value (FALSE) this method will build
the necessary PL/SQL call to register the $module and optionally the $action
value and calls it directly via `QueryHash()`.
If $returnURL is set to TRUE, the PL/SQL call is build but not send to the
database, but instead returned to the callee as string.

This method is used in the `Connect()` method to register the application
name to Oracle during the connect phase.


#### `void SetOutputHash ( array $outputhash)`

Use this call before using `QueryHash()` to pass the output hash variable
to the class. This is only required if you are using either PL/SQL OUT variables
or "RETURNING INTO" clauses in your queries.
After successful completition of `QueryHash()` or `ExecuteHash()` the results
can be retrieved by calling `getOutputHash()`.

See **examples/test_inout_vars.php** for an example how to use this method.


#### `boolean SetPConnect ( $conntype)`

Change the connection method to either persistant connections or standard
connections.

Set $conntype = TRUE to activate persistant connections.

Set $conntype = FALSE to deactivate persistant connections.

Default is standard connection.


#### `boolean SetPrefetch (integer $rows, [mixed $extstmt = -1])`

Allows to set the initial prefetch of rows that Oracle performs. Default is
1 row. If your application fetches a lot of datarows it might be useful to
set this to a higher value, i.e. 10 or more. If you want a specific connection
handle to change you can pass the connection identifier as second parameter
to this method, if nothing is given the currently active connection is used.


#### `void SQLDebug ( $state)`

Allows to en- or disable the SQL_TRACE feature of Oracle.
Pass TRUE to enable or FALSE to disable. When enabled all statements of your
session are saved in a tracefile stored in

`$ORACLE_BASE/admin/<DBNAME>/udump/*.trc`

After your session disconnects use the tkprof tool to generate human-readable
output from the tracefile, i.e.:

`$> tkprof oracle_ora_7527.trc out.txt`

Now read '**out.txt**' and see what happen inside Oracle during your session.


#### `string Version ()`

Returns Database Versionstring. If no active connection exists when calling
this function this method connects itself to the database, retrieve the
version string and disconnects afterwards. If an active connection exists
this connection is used and of course not terminated.


#### `boolean SetAction ($action)`

Sets the action name for Oracle tracing.

This method simply call the PHP function `oci_set_action()` when running on
PHP 5.3.2 or newer, else TRUE is returned and nothing is done.


#### `boolean SetClientInfo($cinfo)`

Sets the client information for Oracle tracing.

This method simply call the PHP function `oci_set_client_info()` when running on
PHP 5.3.2 or newer, else TRUE is returned and nothing is done.


#### `boolean SetClientIdentifier($identifier)`

Sets the client identifier for Oracle tracing.

This method simply call the PHP function `oci_set_client_info()` when running on
PHP 5.3.2 or newer, else TRUE is returned and nothing is done.


### FINAL WORDS AND CONTACT ADDRESS

I'm using this class now in all my Oracle related projects and never encountered
any problems. However we all know that no software is 100% bugfree, so if you
have found a bug or have suggestions or feature requests feel free to contact
me under one of the following addresses:

  WWW: http://www.saschapfalz.de/contact.php

EMAIL: php at saschapfalz dot de


A big thanks must go to the following people:

- Andreas L. for providing very good ideas how to improve the class
- Sven W. for helping finding bugs
- DOAG for giving me the opportunity to perform my first speaking about PHP/Oracle

-----------------------------------------------------------------------[EOF]-
