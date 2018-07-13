<?php
/**
 * dbi4php - Generic database access for PHP
 *
 * The functions defined in this file are meant to provide a single API to the
 * different PHP database APIs. Unfortunately, this is necessary since PHP does
 * not yet have a common db API. The value of <var>$GLOBALS['db_type']</var>
 * should be defined somewhere to one of the following:
 *  - ibase (Interbase)
 *  - ibm_db2
 *  - mssql
 *  - mysql
 *  - mysqli
 *  - odbc
 *  - oracle (This uses the Oracle8 OCI API, so Oracle 8 libs are required)
 *  - postgresql
 *  - sqlite
 *  - sqlite3
 *
 * <b>Limitations:</b>
 * - This assumes a single connection to a single database for the sake of
 *   simplicity. Do not make a new connection until you are completely
 *   finished with the previous one. However, you can execute more than query
 *   at the same time.
 * - Rather than use the associative arrays returned with xxx_fetch_array(),
 *   normal arrays are used with xxx_fetch_row(). (Some db APIs don't support
 *   xxx_fetch_array().)
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id: dbi4php.php,v 1.37 2010/04/07 13:39:08 cknudsen Exp $
 * @package WebCalendar
 *
 * History:
 *  See ChangeLog
 *
 * License:
 *   Copyright (C) 2006  Craig Knudsen
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License,
 *   or (at your option) any later version.
 *
 *   This library is distributed in the hope that it will be useful, but
 *   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 *   or FITNESS FOR A PARTICULAR PURPOSE.
 *   See the GNU Lesser General Public License for more details.
 *
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to:
 *   Free Software Foundation, Inc.
 *   51 Franklin Street, Fifth Floor
 *   Boston, MA  02110-1301, USA
 */

/**
 * Opens up a database connection.
 *
 * Use a pooled connection if the db supports it and
 * the <var>db_persistent</var> setting is enabled.
 *
 * <b>Notes:</b>
 * - The database type is determined by the global variable
 *   <var>db_type</var>
 * - For ODBC, <var>$host</var> is ignored, <var>$database</var> = DSN
 * - For Oracle, <var>$database</var> = tnsnames name
 * - Use the {@link dbi_error()} function to get error information
 *   if the connection fails.
 *
 * @param string  $host      Hostname of database server
 * @param string  $login     Database login
 * @param string  $password  Database login password
 * @param string  $database  Name of database
 * @param string  $lazy      Wait until a query to connect?
 *
 * @return resource The connection
 */
function dbi_connect( $host, $login, $password, $database, $lazy = true ) {
  global $db_cache_count, $db_connection_info, $db_query_count,
  $db_sqlite_error_str, $old_textlimit, $old_textsize;
 
  $db_cache_count =
  $db_query_count = 0;

  if( ! isset( $db_connection_info ) )
    $db_connection_info = array();

  $db_connection_info['connected']  = false;
  $db_connection_info['connection'] = 0;
  $db_connection_info['database']   = $database;
  $db_connection_info['host']       = $host;
  $db_connection_info['login']      = $login;
  $db_connection_info['password']   = $password;

  // mysqli requires $db_connection_info['connection'] to be set.
  if( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 )
    $lazy == false;

  // Lazy connections... do not connect until 1st call to dbi_query.
  if( $lazy )
    // echo "<!-- Waiting on db connection made (lazy) -->\nRETURN!<br />";
    return true;

  if( strcmp( $GLOBALS['db_type'], 'ibase' ) == 0 ) {
    $host = $host . ':' . $database;
    $c = ( $GLOBALS['db_persistent']
      ? ibase_pconnect( $host, $login, $password )
      : ibase_connect( $host, $login, $password ) );
    $db_connection_info['connected']  = true;
    $db_connection_info['connection'] = $c;
    return $c;
  } elseif( strcmp( $GLOBALS['db_type'], 'ibm_db2' ) == 0 ) {
    $c = ( $GLOBALS['db_persistent']
      ? db2_pconnect( $database, $login, $password )
      : db2_connect( $database, $login, $password ) );
    $db_connection_info['connected']  = true;
    $db_connection_info['connection'] =
    $GLOBALS['ibm_db2_connection']    = $c;
    return $c;
  } elseif( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 ) {
    static $old_textlimit, $old_textsize;
    
    $old_textlimit = ini_get( 'mssql.textlimit' );
    $old_textsize  = ini_get( 'mssql.textsize' );
    ini_set( 'mssql.textlimit', '2147483647' );
    ini_set( 'mssql.textsize', '2147483647' );
    $c = ( $GLOBALS['db_persistent']
      ? mssql_pconnect( $host, $login, $password )
      : mssql_connect( $host, $login, $password ) );

    if( $c ) {
      if( ! mssql_select_db( $database ) )
        return false;

      $db_connection_info['connected'] = true;
      $db_connection_info['connection'] = $c;
      return $c;
    } else
      return false;
  } elseif( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 ) {
    $c = ( $GLOBALS['db_persistent']
      ? mysql_pconnect( $host, $login, $password )
      : mysql_connect( $host, $login, $password ) );

    if( $c ) {
      if( ! mysql_select_db( $database ) )
        return false;

      $db_connection_info['connected']  = true;
      $db_connection_info['connection'] = $c;
      return $c;
    } else
      return false;
  } elseif( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 ) {
    $c  = new mysqli( $host, $login, $password, $database );

    if( $c ) {
      if( mysqli_connect_errno() && ! empty( $database ) )
        return false;

      $db_connection_info['connected']  = true;
      $db_connection_info['connection'] =
      $GLOBALS['db_connection']         = $c;
      return $c;
    } else
      return false;
  } elseif( strcmp( $GLOBALS['db_type'], 'odbc' ) == 0 ) {
    $c = ( $GLOBALS['db_persistent']
      ? odbc_pconnect( $database, $login, $password )
      : odbc_connect( $database, $login, $password ) );
    $db_connection_info['connected']  = true;
    $db_connection_info['connection'] =
    $GLOBALS['odbc_connection']       = $c;
    return $c;
  } elseif( strcmp( $GLOBALS['db_type'], 'oracle' ) == 0 ) {
    $_ora_conn_func =
      'OCI' . ( $GLOBALS['db_persistent'] ? 'P' : '' ) . 'Logon';
    $c = $_ora_conn_func( $login, $password,
      ( strlen( $host ) && strcmp( $host, 'localhost' )
        ? '(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP) (HOST = '
         . $host . ' ) (PORT = 1521))) (CONNECT_DATA = (SID = ' . $database
         . ')))'
        : $database ) );
   unset( $_ora_conn_func );
   $db_connection_info['connected']  = true;
   $db_connection_info['connection'] =
   $GLOBALS['oracle_connection']     = $c;
   return $c;
  } elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 ) {
    $dbargs = ( strlen( $host ) ? 'host=' . "$host " : '' )
     . 'dbname=' . $database . ' user=' . $login
     . ( strlen( $password ) ? ' password=' . $password : '' );
    $c = ( $GLOBALS['db_persistent']
      ? pg_pconnect( $dbargs ) : pg_connect( $dbargs ) );
    $GLOBALS['postgresql_connection'] = $c;

    if( ! $c )
      return false;

    $db_connection_info['connected']  = true;
    $db_connection_info['connection'] = $c;
    return $c;
  } elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 ) {
    $c = ( $GLOBALS['db_persistent']
      ? sqlite_popen( $database, 0666, $db_sqlite_error_str )
      : sqlite_open( $database, 0666, $db_sqlite_error_str ) );

    if( ! $c ) {
      echo str_replace( 'XXX', $db_sqlite_error_str,
        translate( 'Error connecting to database XXX' ) ) . "\n";
      exit;
    }
    $db_connection_info['connected']  = true;
    $db_connection_info['connection'] =
    $GLOBALS['sqlite_c']              = $c;
    return $c;
  } elseif( strcmp( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
    $c = new SQLite3 ( $database );

    if( ! $c ) {
      echo str_replace( 'XXX', $db_sqlite_error_str,
        translate( 'Error connecting to database XXX' ) ) . "\n";
      exit;
    }
    $db_connection_info['connected']  = true;
    $db_connection_info['connection'] = $GLOBALS['sqlite3_c'] = $c;
    return $c;
  } else
    dbi_fatal_error( 'dbi_connect(): '
     . ( empty( $GLOBALS['db_type'] )
        ? translate( 'db_type not defined.' )
        : str_replace( 'XXX', $GLOBALS['db_type'],
          translate( 'invalid db_type XXX' ) ) ) );
}

/**
 * Closes a database connection.
 *
 * This is not necessary for any database that uses pooled connections, such as
 * MySQL, but a good programming practice.
 *
 * @param resource  $conn  The database connection.
 *
 * @return bool True on success, false on error.
 */
function dbi_close( $conn ) {
  global $db_connection_info, $db_query_count,
  $old_textlimit, $old_textsize, $SQLLOG;

  $connection =  $db_connection_info['connection'];

  if( is_array( $db_connection_info ) ) {
    if( ! $db_connection_info['connected'] )
      // never connected
      // echo '<!-- No db connection made (cached) -->' . "\n";
      return true;
    else {
      $conn = $db_connection_info['connection'];
      $db_connection_info['connected']  = false;
      $db_connection_info['connection'] = 0;
      // echo '<!-- Closing lazy db connection made after '
       $db_query_count . " queries -->\n";
    }
  }

  if( strcmp( $GLOBALS['db_type'], 'ibase' ) == 0 )
    return ibase_close( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'ibm_db2' ) == 0 )
    return db2_close( $GLOBALS['ibm_db2_connection'] );
  elseif( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 ) {
    if( ! empty( $old_textlimit ) ) {
      ini_set( 'mssql.textlimit', $old_textlimit );
      ini_set( 'mssql.textsize', $old_textsize );
    }
    return mssql_close( $conn );
  } elseif( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 )
    return mysql_close( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 )
    return $conn->close();
  elseif( strcmp( $GLOBALS['db_type'], 'odbc' ) == 0 )
    return odbc_close( $GLOBALS['odbc_connection'] );
  elseif( strcmp( $GLOBALS['db_type'], 'oracle' ) == 0 )
    return OCILogOff( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 )
    return pg_close( $GLOBALS['postgresql_connection'] );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 )
    return sqlite_close( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
    return $connection->close ();
  } else
    dbi_fatal_error( 'dbi_close(): '
       . translate( 'db_type not defined.' ) );
}

/**
 * Return the number of database queries that were executed.
 * (This does not included cached queries.)
 */
function dbi_num_queries() {
  global $db_query_count;

  return $db_query_count;
}

/**
 * Return the number of queries that were cached.
 */
function dbi_num_cached_queries() {
  global $db_cache_count;

  return $db_cache_count;
}

/**
 * Executes an SQL query.
 *
 * <b>Note:</b> Use the {@link dbi_error()} function to get error information
 * if the connection fails.
 *
 * @param string  $sql           SQL of query to execute.
 * @param bool    $fatalOnError  Abort execution if there is a database error?
 * @param bool    $showError     Display error to user (including possibly the
 *                               SQL) if there is a database error?
 *
 * @return mixed The query result resource on queries (which can then be
 *               passed to the {@link dbi_fetch_row()} function to obtain the
 *               results), or true/false on insert or delete queries.
 */
function dbi_query( $sql, $fatalOnError = true, $showError = true ) {
  global $c, $db_connection_info, $db_query_count, $phpdbiVerbose, $SQLLOG;

  if( ! isset( $SQLLOG ) && ! empty( $db_connection_info['debug'] ) )
    $SQLLOG = array();

  if( ! empty( $db_connection_info['debug'] ) )
    $SQLLOG[] = $sql;

  // echo "dbi_query!: " . htmlentities( $sql ) . "<br />";
  // Connect now if not connected.
  if( is_array( $db_connection_info ) && ! $db_connection_info['connected'] ) {
    $c = dbi_connect(
      $db_connection_info['host'],
      $db_connection_info['login'],
      $db_connection_info['password'],
      $db_connection_info['database'],
      false // * not lazy
      );
    $db_connection_info['connected']  = true;
    $db_connection_info['connection'] = $c;
    // echo '<!-- Created delayed db connection (lazy) -->' . "\n";
  }
  $db_query_count++;

  // If caching is enabled, then clear out the cache for any request
  // that may update the datatabase.
  if( ! empty( $db_connection_info['cachedir'] ) ) {
    if( ! preg_match( '/^select/i', $sql ) ) {
      dbi_clear_cache();

      if( ! empty( $db_connection_info['debug'] ) )
        $SQLLOG[] = translate( 'Cache cleared from previous SQL!' );
    }
  }

  // do_debug( "SQL:" . $sql);
  $found_db_type = false;

  if( strcmp( $GLOBALS['db_type'], 'ibase' ) == 0 ) {
    $found_db_type = true;
    $res = ibase_query( $sql );
  } elseif( strcmp( $GLOBALS['db_type'], 'ibm_db2' ) == 0 ) {
    $found_db_type = true;
    $res = db2_exec( $GLOBALS['ibm_db2_connection'], $sql );
  } elseif( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 ) {
    $found_db_type = true;
    $res = mssql_query( $sql );
  } elseif( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 ) {
    $found_db_type = true;
    $res = mysql_query( $sql, $db_connection_info['connection'] );
  } elseif( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 ) {
    $found_db_type = true;
    $res = $GLOBALS['db_connection']->query( $sql );
  } elseif( strcmp( $GLOBALS['db_type'], 'odbc' ) == 0 ) {
    return odbc_exec( $GLOBALS['odbc_connection'], $sql );
  } elseif( strcmp( $GLOBALS['db_type'], 'oracle' ) == 0 ) {
    if( false === $GLOBALS['oracle_statement'] =
        OCIParse( $GLOBALS['oracle_connection'], $sql ) )
      dbi_fatal_error( translate( 'Error executing query.' )
       . $phpdbiVerbose ? ( dbi_error() . "\n\n<br />\n" . $sql ) : ''
       . '', $fatalOnError, $showError );
      return OCIExecute( $GLOBALS['oracle_statement'], OCI_COMMIT_ON_SUCCESS );
  } elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 ) {
    $found_db_type = true;
    $res = pg_exec( $GLOBALS['postgresql_connection'], $sql );
  } elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 ) {
    $found_db_type = true;
    $res = sqlite_query( $GLOBALS['sqlite_c'], $sql, SQLITE_NUM );
  } elseif ( strcmp ( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
    $found_db_type = true;
    $res = $GLOBALS['sqlite3_c']->query ( $sql );
  }

  if( $found_db_type ) {
    if( ! $res )
      dbi_fatal_error( translate( 'Error executing query.' )
       . ( $phpdbiVerbose ? ( dbi_error() . "\n\n<br />\n" . $sql ) : '' ),
         $fatalOnError, $showError );

    return $res;
  } else
    dbi_fatal_error( 'dbi_query(): ' . translate( 'db_type not defined.' ) );
}

/**
 * Retrieves a single row from the database and returns it as an array.
 *
 * <b>Note:</b> We don't use the more useful xxx_fetch_array because not all
 * databases support this function.
 *
 * <b>Note:</b> Use the {@link dbi_error()} function to get error information
 * if the connection fails.
 *
 * @param resource $res The database query resource returned from
 *                      the {@link dbi_query()} function.
 *
 * @return mixed An array of database columns representing a single row in
 *               the query result or false on an error.
 */
function dbi_fetch_row( $res ) {
  if( strcmp( $GLOBALS['db_type'], 'ibase' ) == 0 )
    return ibase_fetch_row( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'ibm_db2' ) == 0 )
    return db2_fetch_array( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 )
    return mssql_fetch_array( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 )
    return mysql_fetch_array( $res, MYSQL_NUM );
  elseif( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 )
    return $res->fetch_array( MYSQLI_NUM );
  elseif( strcmp( $GLOBALS['db_type'], 'odbc' ) == 0 )
    return ( ! odbc_fetch_into( $res, $ret ) ? false : $ret );
  elseif( strcmp( $GLOBALS['db_type'], 'oracle' ) == 0 )
    return ( OCIFetchInto( $GLOBALS['oracle_statement'], $row,
      OCI_NUM + OCI_RETURN_NULLS ) ? $row : 0 );
  elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 ) {
    // Note: row became optional in PHP 4.1.0.
    $r = pg_fetch_array( $res, null, PGSQL_NUM );
    return ( $r ? $r : false );
  } elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 )
    return sqlite_fetch_array( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
    return $res->fetchArray ();
  } else
    dbi_fatal_error( 'dbi_fetch_row(): '
     . translate( 'db_type not defined.' ) );
}

/**
 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE.
 *
 * <b>Note:</b> Use the {@link dbi_error()} function to get error information
 * if the connection fails.
 *
 * @param resource $conn The database connection
 * @param resource $res  The database query resource returned from the
 *                      {@link dbi_query()} function.
 *
 * @return int The number or database rows affected.
 */
function dbi_affected_rows( $conn, $res ) {
  if( strcmp( $GLOBALS['db_type'], 'ibase' ) == 0 )
    return ibase_affected_rows( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'ibm_db2' ) == 0 )
    return db2_num_rows( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 )
    return mssql_rows_affected ( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 )
    return mysql_affected_rows( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 )
    return $conn->affected_rows;
  elseif( strcmp( $GLOBALS['db_type'], 'odbc' ) == 0 )
    return odbc_num_rows( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'oracle' ) == 0 )
    return ( $GLOBALS['oracle_statement'] >= 0
      ? OCIRowCount( $GLOBALS['oracle_statement'] ) : -1 );
  elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 )
    return pg_affected_rows( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 )
    return sqlite_changes( $conn );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite3' ) == 0 )
    return $conn->changes();
  else
    dbi_fatal_error( 'dbi_free_result(): '
     . translate( 'db_type not defined.' ) );
}

/**
 * Update a BLOB (binary large object) in the database with the contents
 * of the specified file.
 * A BLOB field should be created in a separete INSERT statement using
 * NULL as the initial value prior to this call.
 *
 * @param resource $table   the table name that contains the blob
 * @param resource $column  the table column name for the blob
 * @param resource $key     the key for updating the table row
 * @param resource $data    the data to insert
 *
 * @return bool True on success
 */
function dbi_update_blob( $table, $column, $key, $data ) {
  global $unavail_DBI_Update_blob, $db_connection_info;

  $unavail_DBI_Update_blob = str_replace( array( 'XXX', 'YYY' ),
    array( '"dbi_update_blob"', $GLOBALS['db_type'] ),
    translate( 'Unfortunately, XXX is not implemented for YYY' ) );

  assert( '! empty( $table )' );
  assert( '! empty( $column )' );
  assert( '! empty( $key )' );
  assert( '! empty( $data )' );

  $sql = 'UPDATE ' . $table . ' SET ' . $column;

  if( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 )
    return dbi_execute( $sql . ' = 0x' . bin2hex( $data ) . ' WHERE ' . $key );
  elseif ( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 ) {
    return dbi_execute( $sql . ' = \''
     . ( function_exists( 'mysqli_real_escape_string' )
       ? $db_connection_info['connection']->real_escape_string( $data ) : addslashes( $data ) )
     . '\' WHERE ' . $key );
  } elseif ( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 ) {
    return dbi_execute( $sql . ' = \''
     . ( function_exists( 'mysql_real_escape_string' )
       ? mysql_real_escape_string( $data ) : addslashes( $data ) )
     . '\' WHERE ' . $key );
   } elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 )
     return dbi_execute( $sql . ' = \''
      . pg_escape_bytea( $data ) . '\' WHERE ' . $key );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 )
    return dbi_execute( $sql . ' = \''
     . sqlite_udf_encode_binary( $data ) . '\' WHERE ' . $key );
  elseif ( strcmp ( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
    $ar = explode ( '=', $key, 2 );
    $colKey = trim($ar[0]);
    $valueKey = trim($ar[1]);
    $statement = $GLOBALS['sqlite3_c']->prepare($sql .
      " = ? WHERE $colKey = ?");
    $statement->bindParam ( 1, $data, SQLITE3_BLOB );
    $statement->bindParam ( 2, $valueKey );
    $ret = $statement->execute();
    return ( $ret == FALSE ? FALSE : TRUE );
  } else
    // TODO!
    die_miserable_death( $unavail_DBI_Update_blob );
}

/**
 * Get a BLOB (binary large object) from the database.
 *
 * @param resource $table   the table name that contains the blob
 * @param resource $column  the table column name for the blob
 * @param resource $key     the key for updating the table row
 *
 * @return bool True on success
 */
function dbi_get_blob( $table, $column, $key ) {
  global $unavail_DBI_Update_blob, $db_connection_info;

  assert( '! empty( $table )' );
  assert( '! empty( $column )' );
  assert( '! empty( $key )' );

  $res =
    dbi_execute( 'SELECT ' . $column . ' FROM ' . $table . ' WHERE ' . $key );

  if( ! $res ) {
    return false;
  }

  $ret = '';

  if( $row = dbi_fetch_row( $res ) ) {
    if( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 
        || strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 ||
        strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 ) {
      $ret = $row[0];
    } elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 )
      $ret = pg_unescape_bytea ( $row[0] );
    elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 )
      $ret = sqlite_udf_decode_binary( $row[0] );
    elseif( strcmp( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
      $ret = $row[0];
    } else
      // TODO!
      die_miserable_death( $unavail_DBI_Update_blob );
  }
  dbi_free_result( $res );
  return $ret;
}

/**
 * Frees a result set.
 *
 * @param resource $res The database query resource returned from
 *                      the {@link dbi_query()} function.
 *
 * @return bool True on success
 */
function dbi_free_result( $res ) {
  if( $res === true ) // Not needed for UPDATE, DELETE, etc
    return;

  if( strcmp( $GLOBALS['db_type'], 'ibase' ) == 0 )
    return ibase_free_result( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'ibm_db2' ) == 0 )
    return db2_free_result( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 )
    return mssql_free_result( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 )
    return mysql_free_result( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 )
    return mysqli_free_result( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'odbc' ) == 0 )
    return odbc_free_result( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'oracle' ) == 0 ) {
    // Not supported. Ingore.
    if( $GLOBALS['oracle_statement'] >= 0 ) {
      OCIFreeStatement( $GLOBALS['oracle_statement'] );
      $GLOBALS['oracle_statement'] = -1;
    }
  } elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 )
    return pg_freeresult( $res );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 ) {
    // Not supported
  }
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
    // Not needed
  } else
    dbi_fatal_error( 'dbi_free_result(): '
       . translate( 'db_type not defined.' ) );
}

/**
 * Gets the latest database error message.
 *
 * @return string The text of the last database error. (The type of information
 *                varies depending on which type of database is being used.)
 */
function dbi_error() {
  if( strcmp( $GLOBALS['db_type'], 'ibase' ) == 0 )
    $ret = ibase_errmsg();
  elseif( strcmp( $GLOBALS['db_type'], 'ibm_db2' ) == 0 ) {
    $ret = db2_conn_errormsg();

    if( $ret == '' )
      $ret = db2_stmt_errormsg();
  } elseif( strcmp( $GLOBALS['db_type'], 'mssql' ) == 0 )
    // No real mssql_error function. This is as good as it gets.
    $ret = mssql_get_last_message();
  elseif( strcmp( $GLOBALS['db_type'], 'mysql' ) == 0 )
    $ret = mysql_error();
  elseif( strcmp( $GLOBALS['db_type'], 'mysqli' ) == 0 )
    $ret = $GLOBALS['db_connection']->error;
  elseif( strcmp( $GLOBALS['db_type'], 'odbc' ) == 0 )
    // No way to get error from ODBC API.
    $ret = translate( 'Unknown ODBC error.' );
  elseif( strcmp( $GLOBALS['db_type'], 'oracle' ) == 0 ) {
    $e = OCIError( $GLOBALS['oracle_connection']
      ? $GLOBALS['oracle_connection'] : '' );
    $ret = htmlentities( $e['message'] );
  } elseif( strcmp( $GLOBALS['db_type'], 'postgresql' ) == 0 )
    $ret = pg_errormessage( $GLOBALS['postgresql_connection'] );
  elseif( strcmp( $GLOBALS['db_type'], 'sqlite' ) == 0 ) {
    if( empty( $GLOBALS['db_sqlite_error_str'] ) ) {
      $ret = sqlite_last_error( $GLOBALS['sqlite_c'] );
    } else {
      $ret = $GLOBALS['db_sqlite_error_str'];
      $GLOBALS['db_sqlite_error_str'] = '';
    }
  } elseif ( strcmp ( $GLOBALS['db_type'], 'sqlite3' ) == 0 ) {
    $ret = $GLOBALS['sqlite3_c']->lastErrorMsg ();
  } else
    $ret = 'dbi_error(): ' . translate( 'db_type not defined.' );

  return ( strlen( $ret ) ? $ret : translate( 'Unknown error.' ) );
}

/**
 * Displays a fatal database error and aborts execution.
 *
 * @param string  $msg        The database error message.
 * @param bool    $doExit     Abort execution?
 * @param bool    $showError  Show the details of the error (possibly including
 *                            the SQL that caused the error)?
 */
function dbi_fatal_error( $msg, $doExit = true, $showError = true ) {
  if( $showError ) {
    echo '<h2>' . translate( 'Error' ) . '</h2>
<!--begin_error (dbierror)-->
' . $msg . '
<!--end_error-->
';
  }
  if( $doExit )
    exit;
}

/**
 * Escapes a string accordingly to the DB type.
 *
 * @param string $string  SQL of query to execute
 *
 * @return string         The escaped string
 */
function dbi_escape_string( $string ) {
  global $db_connection_info;
  // Return the string in original form; all possible escapings by
  // magic_quotes_gpc (and possibly magic_quotes_sybase) will be rolled back.
  // But, also, we may roll back escaping we have done ourselves.
  // (maybe this should be removed)
  // if( get_magic_quotes_gpc() )
  $string = stripslashes( $string );
  switch( $GLOBALS['db_type'] ) {
    case 'ibase':
    case 'mssql':
    case 'oracle':
      return str_replace( "'", "''", $string );
    case 'mysql':
      // MySQL requires an active connection.
      return ( empty( $db_connection_info['connected'] )
        ? addslashes( $string )
        : ( version_compare( phpversion(), '4.3.0' ) >= 0
          ? mysql_real_escape_string( $string, $db_connection_info['connection'] )
          : mysql_escape_string( $string ) ) );
    case 'mysqli':
      return ( empty( $db_connection_info['connected'] )
        ? addslashes( $string )
        : $db_connection_info['connection']->real_escape_string( $string ) );
    case 'postgresql':
      return pg_escape_string( $string );
    case 'sqlite':
      return sqlite_escape_string( $string );
    case 'sqlite3':
      return SQLite3::escapeString ( $string );
    case 'ibm_db2':
    case 'odbc':
    default:
      return addslashes( $string );
  }
}

/**
 * Executes a SQL query, supporting parameter binding in the ?-style
 *
 * <b>Note:</b> Use the {@link dbi_error()} function to get error information
 * if the connection fails.
 *
 * @param string  $sql           SQL of query to execute.
 *                               May contain ?-placeholders
 * @param array   $params        An array containing the values to put in
 *                               placeolders. These values will be escaped with
 *                               dbi_escape_string() and will be put in single
 *                               quotes. A NULL param will be replaced with NULL
 *                               without quotes around it.
 * @param bool    $fatalOnError  Abort execution if there is a database error?
 * @param bool    $showError     Display error to user (including possibly the
 *                               SQL) if there is a database error?
 *
 * @return mixed  The query result resource on queries (which can then be passed
 *                to the {@link dbi_fetch_row()} function to obtain the
 *                results), or true/false on insert or delete queries.
 */
function dbi_execute( $sql, $params = array(), $fatalOnError = true,
  $showError = true ) {

  if( count( $params ) == 0 )
    return dbi_query( $sql, $fatalOnError, $showError );

  $prepared = '';
  $offset  =
  $phindex = 0;
  while( ( $pos = strpos( $sql, '?', $offset ) ) !== false ) {
    $prepared .= substr( $sql, $offset, $pos - $offset )
     . ( ( is_null( $params[ $phindex ] ) )
      ? "NULL" : ( "'" . dbi_escape_string( $params[ $phindex ] ) . "'" ) );
    $offset = $pos + 1;
    $phindex++;
  }
  $prepared .= substr( $sql, $offset );

  return dbi_query( $prepared, $fatalOnError, $showError );
}

/**
 * Execute a SQL query. First, look to see if the results of this query are in
 * a cache. If they are, then return them. If not, then run the query and store
 * them in the cache. Of course, caching is only performed for SELECT queries.
 * Anything other than that will clear out the entire cache
 * (until we add more intelligent caching logic).
 */
function dbi_get_cached_rows( $sql, $params = array(),
  $fatalOnError = true, $showError = true ) {
  global $db_cache_count, $db_connection_info;
  $file = '';
  $save_query = false;

  if( ! empty( $db_connection_info['cachedir'] )
      && function_exists( 'file_get_contents' ) ) {
    // Cache enabled.
    $hash = md5( $db_connection_info['database']
      . $db_connection_info['password'] .$sql . serialize( $params ) );
    $file = $db_connection_info['cachedir'] . '/' . $hash . '.dat';

    if( file_exists( $file ) ) {
      $db_cache_count++;
      return unserialize( file_get_contents( $file ) );
    }
    $save_query = true;
  }

  $res = dbi_execute( $sql, $params, $fatalOnError, $showError );

  if( $res ) {
    $rows = array();
    while( $row = dbi_fetch_row( $res ) ) {
      $rows[] = $row;
    }
    dbi_free_result( $res );

    // Serialize and save in cache for later use.
    if( ! empty( $file ) && $save_query ) {
      $fd = @fopen( $file, 'w+b', false );

      if( empty( $fd ) ) {
        die_miserable_death ( "Cache Error.<br/><br/>The permissions for the db_cachedir will not allow creation of the following file:<br/><blockquote>" .
          $file . "</blockquote>", 'dbCacheError' );
      }

      fwrite( $fd, serialize( $rows ) );
      fclose( $fd );
      chmod( $file, 0666 );
    }
    return $rows;
  } else
    return false;
}

/**
 * Specify the location of the cache directory.
 * This directory will need to world-writable if this is a web-based application.
 */
function dbi_init_cache( $dir ) {
  global $db_connection_info;

  if( ! isset( $db_connection_info ) )
    $db_connection_info = array();

  $db_connection_info['cachedir'] = $dir;
}

/**
 * Enable SQL debugging.
 * This will keep an array of all SQL queries in the global variable $SQLLOG.
 */
function dbi_set_debug( $status = false ) {
  global $db_connection_info;

  if( ! isset( $db_connection_info ) )
    $db_connection_info = array();

  $db_connection_info['debug'] = $status;
}

/**
 * Get the SQL debug status.
 */
function dbi_get_debug() {
  global $db_connection_info;

  return ( isset( $db_connection_info )
    ? ( ! empty( $db_connection_info['debug'] ) ) : false );
}

/**
 * Clear out the db cache.
 * Return the number of files deleted.
 */
function dbi_clear_cache() {
  global $db_connection_info;

  if( empty( $db_connection_info['cachedir'] ) )
    return 0;

  $cnt = 0;
  $fd = @opendir( $db_connection_info['cachedir'] );

  if( empty( $fd ) )
    dbi_fatal_error( str_replace( 'XXX', $db_connection_info['cachedir'],
      translate( 'Error opening cache dir XXX.' ) ) );

  $b = 0;
  $errcnt = 0;
  $errstr = '';
  while( false !== ( $file = readdir( $fd ) ) ) {
    if( preg_match( '/^\S\S\S\S\S\S\S\S\S\S+.dat$/', $file ) ) {
      // echo 'Deleting ' . $file . '<br />';
      $cnt++;
      $fullpath = $db_connection_info['cachedir'] . '/' . $file;
      $b += filesize ( $fullpath );

      if( ! @unlink( $fullpath ) ) {
         $errcnt++;
         $errstr .= '<!-- ' . str_replace( array( 'XXX', 'YYY' ),
           array( translate( 'delete' ), $file ),
           translate( 'Cache error Could not XXX file YYY.' ) ) . " -->\n";
        // TODO: log this somewhere???
      }
    }
  }
  if ( $errcnt > 10 ) {
    // They don't have correct permissions set.
    die_miserable_death ( "Error removing temporary file.<br/><br/>The permissions for the following directory do not support the db_cachedir option in includes/settings.php:<br/><blockquote>" .
      $db_connection_info['cachedir'] . "</blockquote>", 'dbCacheError' );
  }


  return $cnt;
}

?>
