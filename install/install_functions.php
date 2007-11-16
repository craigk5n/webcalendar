<?php
/* $Id$
 *
 * The file contains all the functions used in the installation script
 */
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  // error_log ( date ( "Y-m-d H:i:s" ) . "> $msg\n",
  // 3, "d:\php\logs\debug.txt" );
}


function db_load_admin () {
  global $webcalConfig;

  $res = dbi_execute ( 'SELECT MAX( cal_login_id ) FROM webcal_user' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $next_id = $row[0] + 1;

    dbi_free_result ( $res );
  }
  $upassword = ( $wewbcalConfig['PASSWORDS_CLEARTEXT'] == 'Y'? 'admin' : md5 ( 'admin' ) );
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user
    WHERE cal_login = \'admin\'', array (), false, false );
  $sql = 'INSERT INTO webcal_user ( cal_login_id, cal_login, cal_passwd, cal_lastname,
    cal_firstname, cal_is_admin ) VALUES ( \''. $next_id . '\', \'admin\', \''
    . $upassword . '\', \'ADMINISTRATOR\', \'DEFAULT\', \'Y\' )';
  // Preload access_function premissions.
  $sql2 = 'INSERT INTO webcal_access_function ( cal_login_id, cal_permissions )
    VALUES ( \'' . $next_id 
	. '\', \'YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY\' )';
  if ( ! $res ) {
    dbi_execute ( $sql );
    dbi_execute ( $sql2 );
  } else { // Sqlite returns $res always.
    $row = dbi_fetch_row ( $res );
    if ( ! isset ( $row[0] ) ) {
      dbi_execute ( $sql );
      dbi_execute ( $sql2 );
    }
    dbi_free_result ( $res );
  }
}

function db_check_admin () {
  $res = dbi_execute ( 'SELECT COUNT( cal_login ) FROM webcal_user
    WHERE cal_is_admin = \'Y\'', array (), false, false );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    dbi_free_result ( $res );
    return ( $row[0] > 0 );
  }
  return false;
}


/* Functions moved from index.php script */

function get_php_setting ( $val, $string = false ) {
  $setting = ini_get ( $val );
  return ( $string == false
    ? ( $setting == '1' || $setting == 'ON' ? 'ON' : 'OFF' )
    : // Test for $string in ini value.
    ( in_array ( $string, explode ( ',', $setting ) ) ? $string : false ) );
}

function get_php_modules ( $val ) {
  $ret = 'OFF';
	//Allow multiple comma seperated values to be passed
  $vals = explode ( ',', $val );
	foreach ( $vals as $values ) {
	  if ( function_exists ( $values ) ) $ret = 'ON';
	}
  return $ret;
}
// We will generate many errors while trying to test database
// Disable them temporarily as needed
function show_errors ( $error_val = 0 ) {
  global $show_all_errors;

  if ( empty ( $_SESSION['error_reporting'] ) )
    $_SESSION['error_reporting'] = get_php_setting ( 'error_reporting' );

  ini_set ( 'error_reporting', ( $show_all_errors == true
      ? 64 : ( $error_val ? $_SESSION['error_reporting'] : 64 ) ) );
}


function get_installed_version ( $postinstall = false ) {
  global $database_upgrade_matrix, $settings, $show_all_errors;
  // disable warnings
  // show_errors ();
  // Set this as the default value.
  $_SESSION['application_name'] = 'Title';
  $_SESSION['blank_database'] = '';
  // We will append the db_type to come up the proper filename.
  $_SESSION['install_file'] = 'tables';
  $_SESSION['old_program_version'] = ( $postinstall
    ? PROGRAM_VERSION : 'new_install' );

  // Suppress errors based on $show_all_errors.
  if ( ! $show_all_errors )
    show_errors ( false );

  // v1.1 and after will have an entry in webcal_config to make this easier
  $res = @dbi_execute ( 'SELECT cal_value FROM webcal_config
    WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'', array(), false, false );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
   if ( ! empty ( $row[0] ) ) {
     $_SESSION['old_program_version'] = $row[0];
     $_SESSION['install_file'] = 'upgrade_' . $row[0];
   }
   dbi_free_result ( $res );
 }

  // We need to determine this is a blank database.
  // This may be due to a manual table setup.
  $res = @dbi_execute ( 'SELECT COUNT( cal_value ) FROM webcal_config',
    array (), false, $show_all_errors );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] === 0 )
      $_SESSION['blank_database'] = true;
    else {

      // Clear db_cache. This will prevent looping when launching WebCalendar
      // if upgrading and WEBCAL_PROGRAM_VERSION is cached.
      if ( ! empty ( $settings['db_cachedir'] ) && @is_dir ( $cfg['db_cachedir'] ) )
        dbi_init_cache ( $settings['db_cachedir'] );
      else
      if ( ! empty ( $settings['cachedir'] ) && @is_dir ( $cfg['cachedir'] ) )
        dbi_init_cache ( $settings['cachedir'] );

      // Delete existing WEBCAL_PROGRAM_VERSION number.
      dbi_execute ( 'DELETE FROM webcal_config
        WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'' );
    }
    dbi_free_result ( $res );
    // Insert webcal_config values only if blank.
    db_load_config ();
    // Check if an Admin account exists.
    $_SESSION['admin_exists'] = db_check_admin ();
  }
  // Get existing server URL.
  // We could use the self-discvery value, but this may be a custom value.
  $res = dbi_execute ( 'SELECT cal_value FROM webcal_config
    WHERE cal_setting = \'SERVER_URL\'', array (), false, $show_all_errors );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty ( $row[0] ) && strlen ( $row[0] ) )
      $_SESSION['server_url'] = $row[0];

    dbi_free_result ( $res );
  }
  // Get existing application name.
  $res = dbi_execute ( 'SELECT cal_value FROM webcal_config
    WHERE cal_setting = \'APPLICATION_NAME\'',
    array (), false, $show_all_errors );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty ( $row[0] ) )
      $_SESSION['application_name'] = $row[0];

    dbi_free_result ( $res );
  }
  // Enable warnings.
  show_errors ( true );
} // end get_installed_version

function parse_sql ( $sql ) {
  $sql = trim ( $sql );
  $sql = trim ( $sql, "\r\n " );
  $ret = array ();

  $buffer_str = '';
  for( $i = 0; $i < strlen ( $sql ); $i++ ) {
    $buffer_str .= substr ( $sql, $i, 1 );
    if ( substr ( $sql, $i, 1 ) == ';' ) {
      $ret[] = $buffer_str;
      $buffer_str = '';
    }
  }
  return ( $ret );
}

function db_populate ( $install_filename, $display_sql ) {
  global $show_all_errors, $str_parsed_sql;
	
  if ( $install_filename == '' )
    return;
  $full_sql = '';
  $current_pointer = false;
  $magic = @get_magic_quotes_runtime ();
  @set_magic_quotes_runtime ( 0 );
  $fd = @fopen ( 'sql/' . $install_filename, 'r', true );

  // Discard everything up to the required point in the upgrade file.
  while ( ! feof ( $fd ) && empty ( $current_pointer ) ) {
    $data = trim ( fgets ( $fd, 4096 ), "\r\n " );
    if ( strpos ( strtoupper ( $data ),
          strtoupper ( $_SESSION['install_file'] ) ) ||
        substr ( $_SESSION['install_file'], 0, 6 ) == 'tables' )
      $current_pointer = true;
  }
  // We already have a $data item from above.
  if ( substr ( $data, 0, 2 ) == "/*" &&
      substr ( $_SESSION['install_file'], 0, 6 ) != 'tables' ) {
    // Do nothing...We skip over comments in upgrade files
  } else
    $full_sql .= $data;
  // We need to strip out the comments from upgrade files.
  while ( ! feof ( $fd ) ) {
    $data = trim ( fgets ( $fd, 4096 ), "\r\n " );
    if ( substr ( $data, 0, 2 ) == '/*' &&
        substr ( $_SESSION['install_file'], 0, 6 ) != 'tables' ) {
      // Do nothing...We skip over comments in upgrade files.
    } else
      $full_sql .= $data;
  }
//echo $full_sql;

  @set_magic_quotes_runtime ( $magic );
  fclose ( $fd );
  $parsed_sql = parse_sql ( $full_sql );
  // Disable warnings.
  // show_errors ();
  // String version of parsed_sql that is used if displaying SQL only.
  $str_parsed_sql = '';
  for ( $i = 0, $sqlCntStr = count ( $parsed_sql ); $i < $sqlCntStr; $i++ ) {
    if ( empty ( $display_sql ) ) {
      if ( $show_all_errors == true )
        echo $parsed_sql[$i] . '<br />';
      dbi_execute ( $parsed_sql[$i], array (), false, $show_all_errors );
    } else
      $str_parsed_sql .= $parsed_sql[$i] . "\n\n";
  }
  // echo "PARSED SQL " . $str_parsed_sql;
  // Enable warnings.
  show_errors ( true );
} //end db_populate

function readSettings () {

}

function writeSettings ( $settingsAr ) {

}

function writeAlert ( $str, $back=false ) {
   $go_back = ( $back ?'document.go(-1)' : '' );
echo <<<EOT
   <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
   <html>
   <head>
	 <title>{$str}</title>
   <meta http-equiv="refresh" content="0; index.php" />
   </head>
   <body onload="alert ('{$str}'); {$go_back}">
   </body></html>
EOT;
}
?>
