<?php
if ( empty ( $PHP_SELF ) )
  $PHP_SELF = $_SERVER["PHP_SELF"];
if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}
// php-dbi.php
//
// (C) Craig Knudsen, cknudsen@radix.net, http://www.radix.net/~cknudsen/
// License: GNU GPL (see www.gnu.org)
//
// The functions defined in this file are meant to provide a single
// API to the different PHP database APIs.  Unfortunately, this is
// necessary since PHP does not yet have a common db API.
// The value of $GLOBALS["db_type"] should be defined somewhere
// to one of the following:
//	mysql
//	mssql
//	oracle	(This uses the Oracle8 OCI API, so Oracle 8 libs are required)
//	postgresl
//	odbc
//	ibase (Interbase)
// Limitations:
//	This assumes a single connection to a single database
//	for the sake of simplicity.  Do not make a new connection until you
//	are completely finished with the previous one.  However, you can
//	execute more than query at the same time.
//	Rather than use the associative arrays returned with
//	xxx_fetch_array(), normal arrays are used with xxx_fetch_row().
//	(Some db APIs don't support xxx_fetch_array().)
//
// History:
//	23-Jan-2005	Craig Knudsen <cknudsen@cknudsen.com>
//			Added documentation to be used with php2html.pl
//	19-Jan-2005	Craig Knudsen <cknudsen@cknudsen.com>
//			Add option for verbose error messages.
//	19-Jan-2004	Craig Knudsen <cknudsen@cknudsen.com>
//			Added mssql support
//			Code from raspail@users.sourceforge.net
//	02-Jul-2004	Craig Knudsen <cknudsen@cknudsen.com>
//			Added mysqli support
//			Code from Francesco Riosa
//	31-May-2002	Craig Knudsen <cknudsen@radix.net>
//			Added support for Interbase contributed by
//			Marco Forlin
//	11-Jul-2001	Craig Knudsen <cknudsen@radix.net>
//			Removed pass by reference for odbc_fetch_into()
//			Removed ++ in call to pg_fetch_array()
//	22-Apr-2000	Ken Harris <kharris@lhinfo.com>
//			PostgreSQL fixes
//	23-Feb-2000	Craig Knudsen <cknudsen@radix.net>
//			Initial release
//
// Limitations:
// Fetched rows are returned in non-associative arrays.


// Enable the following to show the actual database error in the browser.
// It is more secure to not show this info, so this should only be turned
// on for debugging purposes.
$phpdbiVerbose = false;

/** dbi_connect
  * Description:
  *	Open up a database connection.
  *	Use a pooled connection if the db supports it and
  *	the <tt>db_persistent</tt> setting is enabled.
  *	<br/>Notes:<ul>
  *	<li> The database type is determined by the global
  *	     variable <tt>db_type</tt>
  *	<li> For ODBC, $host is ignored, $database = DSN</li>
  *	<li> For Oracle, $database = tnsnames name</li>
  *	<li> Use the dbi_error function to get error information if
  *	     the connection fails </li>
  *	</ul>
  * Parameters:
  *	$host - Hostname of database server
  *	$login - Database login
  *	$password - Database login password
  *	$database - Name of database
  * Returns:
  *	The connection
  */
function dbi_connect ( $host, $login, $password, $database ) {
  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
    if ($GLOBALS["db_persistent"]) {
      $c = mysql_pconnect ( $host, $login, $password );
    } else {
      $c = mysql_connect ( $host, $login, $password );
    }
    if ( $c ) {
      if ( ! mysql_select_db ( $database ) )
        return false;
      return $c;
    } else {
      return false;
    }
  } else if ( strcmp ( $GLOBALS["db_type"], "mysqli" ) == 0 ) {
    if ($GLOBALS["db_persistent"]) {
      $c = @mysqli_connect ( $host, $login, $password, $database);
    } else {
      $c = @mysqli_connect ( $host, $login, $password, $database);
    }
    if ( $c ) {
      /*
      if ( ! mysqli_select_db ( $c, $database ) )
        return false;
      */
      $GLOBALS["db_connection"] = $c;
      return $c;
    } else {
      return false;
    }
  } else if ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0 ) {
    if ($GLOBALS["db_persistent"]) {
      $c = mssql_pconnect ( $host, $login, $password );
    } else {
      $c = mssql_connect ( $host, $login, $password );
    }
    if ( $c ) {
      if ( ! mssql_select_db ( $database ) )
        return false;
      return $c;
    } else {
      return false;
    }
  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
    if ( strlen ( $host ) && strcmp ( $host, "localhost" ) )
      $c = OCIPLogon ( "$login@$host", $password, $database );
    else
      $c = OCIPLogon ( $login, $password, $database );
    $GLOBALS["oracle_connection"] = $c;
    return $c;
  } else if ( strcmp ( $GLOBALS["db_type"], "postgresql" ) == 0 ) {
    if ( strlen ( $password ) ) {
      if ( strlen ( $host ) ) {
        $dbargs = "host=$host dbname=$database user=$login password=$password";
      } else {
        $dbargs = "dbname=$database user=$login password=$password";
      }
    } else {
      if ( strlen ( $host ) ) {
        $dbargs = "host=$host dbname=$database user=$login";
      } else {
        $dbargs = "dbname=$database user=$login";
      }
    }
    if ($GLOBALS["db_persistent"]) {
      $c = pg_pconnect ( $dbargs );
    } else {
      $c = pg_connect ( $dbargs );
    }
    $GLOBALS["postgresql_connection"] = $c;
    if ( ! $c ) {
        echo "Error connecting to database\n";
        exit;
    }
    return $c;
  } else if ( strcmp ( $GLOBALS["db_type"], "odbc" ) == 0 ) {
    $database = ( strlen ( $host ) ) ? "$host:$database" : $database;
    if ($GLOBALS["db_persistent"]) {
      $c = odbc_pconnect ( $database, $login, $password );
    } else {
      $c = odbc_connect ( $database, $login, $password );
    }
    $GLOBALS["odbc_connection"] = $c;
    return $c;
  } else if ( strcmp ( $GLOBALS["db_type"], "ibase" ) == 0 ) {
    if ($GLOBALS["db_persistent"]) {
      $c = ibase_pconnect ( $host, $login, $password );
    } else {
      $c = ibase_connect ( $host, $login, $password );
    }
    return $c;
  } else {
    if ( empty ( $GLOBALS["db_type"] ) )
      dbi_fatal_error ( "dbi_connect(): db_type not defined." );
    else
      dbi_fatal_error ( "dbi_connect(): invalid db_type '" .
        $GLOBALS["db_type"] . "'" );
  }
}

/** dbi_close
  * Description:
  *	Close a database connection.
  *	(Not necessary for any database that uses pooled connections
  *	such as MySQL, but a good programming practice.)
  * Parameters:
  *	$conn - The database connection
  * Returns:
  *	true on success, false on error
  */
function dbi_close ( $conn ) {
  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
    return mysql_close ( $conn );
  } else if ( strcmp ( $GLOBALS["db_type"], "mysqli" ) == 0 ) {
    return mysqli_close ( $conn );
  } else if ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0 ) {
    return mssql_close ( $conn );
  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
    return OCILogOff ( $conn );
  } else if ( strcmp ( $GLOBALS["db_type"], "postgresql" ) == 0 ) {
    return pg_close ( $GLOBALS["postgresql_connection"] );
  } else if ( strcmp ( $GLOBALS["db_type"], "odbc" ) == 0 ) {
    return odbc_close ( $GLOBALS["odbc_connection"] );
  } else if ( strcmp ( $GLOBALS["db_type"], "ibase" ) == 0 ) {
    return ibase_close ( $conn );
  } else {
    dbi_fatal_error ( "dbi_close(): db_type not defined." );
  }
}

// Select the database that all queries should use
//function dbi_select_db ( $database ) {
//  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
//    return mysql_select_db ( $database );
//  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
//    // Not supported.  Must sent up a tnsname and user that uses
//    // the correct tablesapce.
//    return true;
//  } else {
//    dbi_fatal_error ( "dbi_select_db(): db_type not defined." );
//  }
//}

/** dbi_query
  * Description:
  *	Execute a SQL query.
  *	<br/>Note: Use the dbi_error function to get error information if
  *	     the connection fails.
  * Parameters:
  *	$sql - SQL of query to execute
  *	$fatalOnError - Abort execution if there is a database error
  *	$showError - Display error to use (including possibly the
  *	  SQL) if there is a database error
  * Returns:
  *	The query result resource on queries (which can then be
  *	passed to the dbi_fetch_row function to obtain the results),
  *	or true/false on insert or delete queries.
  */
function dbi_query ( $sql, $fatalOnError=true, $showError=true ) {
  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
    $res = mysql_query ( $sql );
    if ( ! $res )
      dbi_fatal_error ( "Error executing query." .
        $phpdbiVerbose ? ( dbi_error() . "\n\n<br />\n" . $sql ) : "" .
        "", $fatalOnError, $showError );
    return $res;
  } else if ( strcmp ( $GLOBALS["db_type"], "mysqli" ) == 0 ) {
    $res = mysqli_query ( $GLOBALS["db_connection"], $sql );
    if ( ! $res )
      dbi_fatal_error ( "Error executing query." .
        $phpdbiVerbose ? ( dbi_error() . "\n\n<br />\n" . $sql ) : "" .
        "", $fatalOnError, $showError );
    return $res;
  } else if ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0 ) {
    $res = mssql_query ( $sql );
    if ( ! $res )
      dbi_fatal_error ( "Error executing query." .
        $phpdbiVerbose ? ( dbi_error() . "\n\n<br />\n" . $sql ) : "" .
        "", $fatalOnError, $showError );
    return $res;
  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
    $GLOBALS["oracle_statement"] =
      OCIParse ( $GLOBALS["oracle_connection"], $sql );
    return OCIExecute ( $GLOBALS["oracle_statement"],
      OCI_COMMIT_ON_SUCCESS );
  } else if ( strcmp ( $GLOBALS["db_type"], "postgresql" ) == 0 ) {
    $GLOBALS["postgresql_row[\"$res\"]"] = 0;
    $res =  pg_exec ( $GLOBALS["postgresql_connection"], $sql );
    if ( ! $res )
      dbi_fatal_error ( "Error executing query." .
        $phpdbiVerbose ? ( dbi_error() . "\n\n<br />\n" . $sql ) : "" .
        "", $fatalOnError, $showError );
    $GLOBALS["postgresql_numrows[\"$res\"]"] = pg_numrows ( $res );
    return $res;
  } else if ( strcmp ( $GLOBALS["db_type"], "odbc" ) == 0 ) {
    return odbc_exec ( $GLOBALS["odbc_connection"], $sql );
  } else if ( strcmp ( $GLOBALS["db_type"], "ibase" ) == 0 ) {
    $res = ibase_query ( $sql );
    if ( ! $res )
      dbi_fatal_error ( "Error executing query." .
        $phpdbiVerbose ? ( dbi_error() . "\n\n<br />\n" . $sql ) : "" .
        "", $fatalOnError, $showError );
    return $res;
  } else {
    dbi_fatal_error ( "dbi_query(): db_type not defined." );
  }
}

// Determine the number of rows from a result
//function dbi_num_rows ( $res ) {
//  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
//    return mysql_num_rows ( $res );
//  } else {
//    dbi_fatal_error ( "dbi_num_rows(): db_type not defined." );
//  }
//}

/** dbi_fetch_row
  * Description:
  *	Retrieve a single row from the database and return it
  *	as an array.
  *	<br/>Note: we don't use the more useful xxx_fetch_array because not all
  *	databases support this function.
  *	<br/>Note: Use the dbi_error function to get error information if
  *	     the connection fails.
  * Parameters:
  *	$res - The database query resource returned from
  *	       the dbi_query function.
  * Returns:
  *	An array of database columns representing a single row in
  *	the query result or false on an error.
  */
function dbi_fetch_row ( $res ) {
  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
    return mysql_fetch_array ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "mysqli" ) == 0 ) {
    return mysqli_fetch_array ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0 ) {
    return mssql_fetch_array ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
    if ( OCIFetchInto ( $GLOBALS["oracle_statement"], $row,
      OCI_NUM + OCI_RETURN_NULLS  ) )
      return $row;
    return 0;
  } else if ( strcmp ( $GLOBALS["db_type"], "postgresql" ) == 0 ) {
    if ( $GLOBALS["postgresql_numrows[\"$res\"]"]  > $GLOBALS["postgresql_row[\"$res\"]"] ) {
        $r =  pg_fetch_array ( $res, $GLOBALS["postgresql_row[\"$res\"]"] );
        $GLOBALS["postgresql_row[\"$res\"]"]++;
        if ( ! $r ) {
            echo "Unable to fetch row\n";
            return '';
        }
    }
    else {
        $r = '';
    }
    return $r;
  } else if ( strcmp ( $GLOBALS["db_type"], "odbc" ) == 0 ) {
    if ( ! odbc_fetch_into ( $res, $ret ) )
      return false;
    return $ret;
  } else if ( strcmp ( $GLOBALS["db_type"], "ibase" ) == 0 ) {
    return ibase_fetch_row ( $res );
  } else {
    dbi_fatal_error ( "dbi_fetch_row(): db_type not defined." );
  }
}

//   $conn - db connection
//   $res - returned from dbi_query
/** dbi_affected_rows
  * Description:
  *	Returns the number of rows affected by the last INSERT, UPDATE or
  *	DELETE.
  *	<br/>Note: Use the dbi_error function to get error information if
  *	     the connection fails.
  * Parameters:
  *	$conn - The database connection
  *	$res - The database query resource returned from
  *	       the dbi_query function.
  * Returns:
  *	The number or database rows affected.
  */
function dbi_affected_rows ( $conn, $res ) {
  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
    return mysql_affected_rows ( $conn );
  } else if ( strcmp ( $GLOBALS["db_type"], "mysqli" ) == 0 ) {
    return mysqli_affected_rows ( $conn );
  } else if ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0 ) {
    return mssql_affected_rows ( $conn );
  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
    if ( $GLOBALS["oracle_statement"] >= 0 ) {
      return OCIRowCount ( $GLOBALS["oracle_statement"] );
    } else {
      return -1;
    }
  } else if ( strcmp ( $GLOBALS["db_type"], "postgresql" ) == 0 ) {
    return pg_affected_rows ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "odbc" ) == 0 ) {
    return odbc_num_rows ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "ibase" ) == 0 ) {
    return ibase_affected_rows ( $conn );
  } else {
    dbi_fatal_error ( "dbi_free_result(): db_type not defined." );
  }
}

/** dbi_free_result
  * Description:
  *	Free a result set.
  * Parameters:
  *	$res - The database query resource returned from
  *	       the dbi_query function.
  * Returns:
  *	true on success
  */
function dbi_free_result ( $res ) {
  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
    return mysql_free_result ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "mysqli" ) == 0 ) {
    return mysqli_free_result ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0 ) {
    return mssql_free_result ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
    // Not supported.  Ingore.
    if ( $GLOBALS["oracle_statement"] >= 0 ) {
      OCIFreeStatement ( $GLOBALS["oracle_statement"] );
      $GLOBALS["oracle_statement"] = -1;
    }
  } else if ( strcmp ( $GLOBALS["db_type"], "postgresql" ) == 0 ) {
    return pg_freeresult ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "odbc" ) == 0 ) {
    return odbc_free_result ( $res );
  } else if ( strcmp ( $GLOBALS["db_type"], "ibase" ) == 0 ) {
    return ibase_free_result ( $res );
  } else {
    dbi_fatal_error ( "dbi_free_result(): db_type not defined." );
  }
}

/** dbi_error
  * Description:
  *	Get the latest database error message.
  * Returns:
  *	The text of the last database error.
  *	(The type of information varies depending on the which
  *	type of database is being used.)
  */
function dbi_error () {
  if ( strcmp ( $GLOBALS["db_type"], "mysql" ) == 0 ) {
    $ret = mysql_error ();
  } else if ( strcmp ( $GLOBALS["db_type"], "mysqli" ) == 0 ) {
    $ret = mysqli_error ($GLOBALS["db_connection"]);
  } else if ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0 ) {
    $ret = mssql_error ();
  } else if ( strcmp ( $GLOBALS["db_type"], "oracle" ) == 0 ) {
    $ret = OCIError ( $GLOBALS["oracle_connection"] );
  } else if ( strcmp ( $GLOBALS["db_type"], "postgresql" ) == 0 ) {
    $ret = pg_errormessage ( $GLOBALS["postgresql_connection"] );
  } else if ( strcmp ( $GLOBALS["db_type"], "odbc" ) == 0 ) {
    // no way to get error from ODBC API
    $ret = "Unknown ODBC error";
  } else if ( strcmp ( $GLOBALS["db_type"], "ibase" ) == 0 ) {
    $ret = ibase_errmsg ();
  } else {
    $ret = "dbi_error(): db_type not defined.";
  }
  if ( strlen ( $ret ) )
    return $ret;
  else
    return "Unknown error";
}

/** dbi_fatal_error
  * Description:
  *	Display a fatal database error and abort execution.
  * Parameters:
  *	$msg - The database error message
  *	$doExit - Abort execution (true/false)
  *	$showError - Show the details of the error (possibly including
  *		the SQL that caused the error)
  */
function dbi_fatal_error ( $msg, $doExit=true, $showError=true ) {
  if ( $showError ) {
    echo "<h2>Error</h2>\n";
    echo "<!--begin_error(dbierror)-->\n";
    echo "$msg\n";
    echo "<!--end_error-->\n";
  }
  if ( $doExit )
    exit;
}
?>
