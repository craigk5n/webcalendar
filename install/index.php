<?php // $Id: index.php,v 1.139.2.1 2012/02/28 15:43:10 cknudsen Exp $
/**
 * Page Description:
 * Main page for install/config of db settings.
 * This page is used to create/update includes/settings.php.
 *
 * NEW RELEASE UPDATE PROCEDURES:
 *   - Update WEBCAL_PROGRAM_VERSION default value in default_config.php
 *     This should be of the format "v1.0.0"
 *   - Make sure the last entry in all the upgrade-*.sql files reference
 *     this same version. For example, for "v1.0.0", there should be a
 *     comment of the format:    /*upgrade_v1.0.0 */
       /* ( Don't remove this line as it leads to nested C-Style comments )
 *     If there are NO db changes, then you should just modify the
 *     the last comment to be the new version number. If there are
 *     db changes, you should create a new entry in the *.sql files
 *     that detail the SQL to upgrade.
 *   - Update the $PROGRAM_VERSION and $PROGRAM_DATE variables defined
 *     in includes/config.php. The $PROGRAM_VERSION needs to be the
 *     same value (e.g. "v1.0.0") that was defined above.
 *   - Update the version/date in ChangeLog and NEWS files.
 *   - Update UPGRADING.html documentation.
 *
 * ABOUT VERSION NUMBERS:
 *   From now on, we should only be using "vN.N.N" format for versions.
 *   (No more "v.1.12+CVS", for example.)  This may be confusing for CVS
 *   users since they may download a CVS snapshot that says its "1.1.4",
 *   but it's really not quite the official "1.1.4" since we will using
 *   1.1.4 in CVS until the official 1.1.4 release is made.
 *
 *   You can mark the version with "+CVS" or something similar in NEWS
 *   and/or ChangeLog since these are not used in the code.
 *
 * Input Parameters:
 * OPTIONAL tzoffset   If after logging in, adding tzoffset to the URL
 * ( http://yourserver/install/index.php?tzoffset=2 )
 * will adjust all existing events in the database +2 hours.
 * OPTIONAL cutoffdate (YYYYMMDD)  When adjusting the tzoffset the URL
 * ( http://yourserver/install/index.php?tzoffset=2&cutoffdate=20070110 )
 * will adjust all events <= 20070110 in the database +2 hours.
 * This is very handy if your server changes timezones after installation.
 *
 * Security:
 * The first time this page is accessed, there are no security precautions.
 * The user is prompted to generate a config password. From then on, users must
 * know this password to make any changes to the settings in settings.php.
 *
 * TODO:
 * Change all references from postgresql to pgsql
 */
$show_all_errors = false;
// Change this path as needed.
$firebird_path = 'c&#58;/program files/firebird/firebird_1_5/examples/employee.fdb';

include_once '../includes/translate.php';
include_once '../includes/dbi4php.php';
include_once '../includes/config.php';
include_once '../includes/formvars.php';
include_once 'default_config.php';
include_once 'install_functions.php';
include_once 'sql/upgrade_matrix.php';

define( '__WC_BASEDIR', '../' );
$fileDir = __WC_BASEDIR . 'includes';
$file    = $fileDir . '/settings.php';

clearstatcache();

// We may need time to run extensive database loads.
if ( ! get_php_setting( 'safe_mode' ) )
  set_time_limit( 240 );

// If we're using SQLLite, it seems that magic_quotes_sybase must be on.
// ini_set( 'magic_quotes_sybase', 'On' );

// Check for proper auth settings.
if( ! empty( $_SERVER['PHP_AUTH_USER'] ) )
  $PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER'];

// We'll always use browser defined languages.
reset_language( 'none' );

// Some common translations used in the install script.
$backStr        = translate( 'Back' );
$cachedirStr    = translate( 'Database Cache Directory' );
$createNewStr   = translate( 'Create New' );
$databaseNameStr= translate( 'Database Name' );
$failureStr     = translate( 'Failure Reason' );
$loginStr       = translate( 'Login' );
$logoutStr      = translate( 'Logout' );
$manualStr      = translate( 'You must manually create database' );
$nextStr        = translate( 'Next' );
$passwordStr    = translate( 'Password' );
$singleUserStr  = translate( 'Single-User' );
$testSettingsStr= translate( 'Test Settings' );
$tzSuccessStr   = translate( 'Timezone Conversion Successful' );
$wizardStr      = translate( 'WebCalendar Installation Wizard Step XXX' );

$failure = $failureStr . '<blockquote>';

$checked = ' checked="checked"';
$selected= ' selected="selected"';

// First pass at settings.php.
// We need to read it first in order to get the md5 password.
if( function_exists( 'set_magic_quotes_runtime' ) ) {
  $magic = @get_magic_quotes_runtime();
  @set_magic_quotes_runtime( 0 );
} else
  unset( $magic );

$fd = @fopen( $file, 'rb', true );
$settings = array();
$password = '';
$forcePassword = false;

if( ! empty( $fd ) ) {
  while( ! feof( $fd ) ) {
    $buffer = trim( fgets( $fd, 4096 ) );

    if( preg_match( '/^(\S+):\s*(.*)/', $buffer, $matches ) ) {
      if( $matches[1] == 'install_password' )
        $password = $settings['install_password'] = $matches[2];
    }
  }
  fclose( $fd );

  // File exists, but no password. Force them to create a password.
  if( empty( $password ) )
    $forcePassword = true;
}

if( isset( $magic ) )
  @set_magic_quotes_runtime( $magic );

session_start();
$doLogin = false;

// Set default Application Name.
if( ! isset( $_SESSION['application_name'] ) )
  $_SESSION['application_name'] = 'WebCalendar';

// Set Server URL.
if( ! isset( $_SESSION['server_url'] ) ) {
  if( ! empty( $_SERVER['HTTP_HOST'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
    $ptr = strpos( $_SERVER['REQUEST_URI'], '/install', 2 );

    if( $ptr > 0 )
      $_SESSION['server_url'] = $SERVER_URL = 'http://' . $_SERVER['HTTP_HOST']
       . ( ! empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != 80
        ? ':' . $_SERVER['SERVER_PORT'] : '' )
       . substr( $_SERVER['REQUEST_URI'], 0, $ptr + 1 );
  }
}

// Handle "Logout" button.
if( 'logout' == getGetValue( 'action' ) ) {
  session_destroy();
  Header( 'Location: index.php' );
  exit;
}

// If password already exists, check for valid session.
if( file_exists( $file ) && ! empty( $password ) &&
    ( empty( $_SESSION['validuser'] ) || $_SESSION['validuser'] != $password ) )
  // Make user login.
  $doLogin = true;

$pwd = getPostValue( 'password' );

if( file_exists( $file ) && ! empty( $pwd ) ) {
  $_SESSION['validuser'] = '';
  echo '
<html>
  <head>
    <title>';

  if( md5( $pwd ) == $password ) {
    $_SESSION['validuser'] = $password;
    echo translate( 'Password Accepted' ) . '</title>
    <meta http-equiv="refresh" content="0; index.php" />
  </head>
  <body onLoad="alert( \'' . translate( 'Successful Login', true ) . '\' );">';
  } else
    // Invalid password.
    echo translate( 'Password Incorrect' ) . '</title>
    <meta http-equiv="refresh" content="0; index.php" />
  </head>
  <body onLoad="alert( \'' . translate( 'Invalid Login', true )
     . '\' ); document.go(-1)">';

  echo '
  </body>
</html>';
  exit;
}

// [0]Display Text [1]ini_get name [2]required value [3]ini_get string search value
//DO NOT TRANSLATE OFF/ON in this section
$php_settings = array(
  array( translate( 'Display Errors' ), 'display_errors', 'ON', false ),
  array( translate( 'File Uploads' ), 'file_uploads', 'ON', false ),
  array( translate( 'Allow URL fopen' ), 'allow_url_fopen', 'ON', false ),
  array( translate( 'Safe Mode' ), 'safe_mode', 'OFF', false )
  );

//Add 'Safe Mode Allowed Vars' if 'Safe Mode' is enabled
if( get_php_setting( 'safe_mode' ) == 'ON' )
  $php_settings[] = array(
    translate('Safe Mode Allowed Vars'),
      'safe_mode_allowed_env_vars', 'TZ', 'TZ');

// Set up array to test for some constants
// (display name, constant name, preferred value )
$php_constants = array(
  // array(' CRYPT_STD_DES', CRYPT_STD_DES, 1)
  // future expansion
  // array('CRYPT_STD_DES',CRYPT_STD_DES, 1)
  // array('CRYPT_MD5',CRYPT_MD5, 1)
  // array('CRYPT_BLOWFISH',CRYPT_BLOWFISH, 1)
  );
$php_modules = array(
  array( translate( 'GD' ), 'imagepng', 'ON' ),
  );

$pwd1 = getPostValue( 'password1' );
$pwd2 = getPostValue( 'password2' );
if( file_exists( $file ) && $forcePassword && ! empty( $pwd1 ) ) {
  if( $pwd1 != $pwd2 ) {
    echo translate( 'Passwords do not match!' ) . '<br />' . "\n";
    exit;
  }
  $fd = @fopen( $file, 'a+b', false );
  if( empty( $fd ) ) {
    echo '<html><body>'
     . translate( 'Unable to write password to settings.php file' )
     . '</body></html>';
    exit;
  }
  fwrite( $fd, '<?php' . "\r\n" . 'install_password: ' . md5( $pwd1 )
     . "\r\n?>\r\n" );
  fclose( $fd );

  echo '
<html>
  <head>
    <title>' . translate( 'Password Updated' ) . '</title>
    <meta http-equiv="refresh" content="0; index.php" />
  </head>
  <body onLoad="alert( \''
   . translate( 'Password has been set', true ) . '\' );">
  </body>
</html>';
  exit;
}

if( function_exists( 'set_magic_quotes_runtime' ) ) {
  $magic = @get_magic_quotes_runtime();
  @set_magic_quotes_runtime( 0 );
} else
  unset( $magic );

$fd = @fopen( $file, 'rb', false );
if( ! empty( $fd ) ) {
  while( ! feof( $fd ) ) {
    $buffer = trim( fgets( $fd, 4096 ) );

    if( preg_match( '/^#|\/\*/', $buffer ) // comments
        || preg_match( '/^<\?/', $buffer ) // start php code
        || preg_match( '/^\?>/', $buffer ) // end php code
      ) {
        continue;
    }
    if( preg_match( '/(\S+):\s*(.*)/', $buffer, $matches ) )
      $settings[$matches[1]] = $matches[2];
  }
  fclose( $fd );
}

if( isset( $magic ) )
  @set_magic_quotes_runtime( $magic );

$action = getGetValue( 'action' );
// We were sent here because of a mismatch of $PROGRAM_VERSION.
// A simple way to ensure that UPGRADING.html gets read and processed.
if( ! empty( $action ) && $action == 'mismatch' )
  $_SESSION['old_program_version'] = $version = getGetValue( 'version' );

// Go to the proper page.
if( ! empty( $action ) && $action == 'switch' ) {
  $page = getGetValue( 'page' );
  switch( $page ) {
    case 2:
      if( ! empty( $_SESSION['validuser'] ) ) {
        $_SESSION['step'] = $page;
        $onload = 'db_type_handler();';
      }
      break;
    case 3:
      if( ! empty( $_SESSION['validuser'] )
          && ! empty( $_SESSION['db_success'] ) )
        $_SESSION['step'] = $page;
      break;
    case 4:
      if( ! empty( $_SESSION['validuser'] )
          && ! empty( $_SESSION['db_success'] )
          && empty( $_SESSION['db_create'] ) ) {
        $_SESSION['step'] = $page;
        $onload = 'auth_handler();';
      }
      break;
    default:
      $_SESSION['step'] = 1;
  }
}

// We're doing a database installation yea ha!
if( ! empty( $action ) && $action == 'install' ) {
  // We'll grab database settings from settings.php.
  $db_database = $settings['db_database'];
  $db_host     = $settings['db_host'];
  $db_login    = $settings['db_login'];
  $db_password = ( empty( $settings['db_password'] )
    ? '' : $settings['db_password'] );
  $db_persistent = false;
  $db_type       = $settings['db_type'];
  $real_db       = ( $db_type== 'sqlite' || $db_type == 'sqlite3'
    ? get_full_include_path( $db_database ) : $db_database );

  // We might be displaying SQL only.
  $display_sql = getPostValue( 'display_sql' );

  $c = dbi_connect( $db_host, $db_login, $db_password, $real_db, false );
  // It's possible that the tables were created manually
  // and we just want to do the database population routines.
  if( $c && ! empty( $_SESSION['install_file'] ) ) {
    $sess_install = $_SESSION['install_file'];
    $install_filename = ( $sess_install == 'tables' ? 'tables-' : 'upgrade-' );
    switch( $db_type ) {
      case 'ibase':
      case 'mssql':
      case 'oracle':
        $install_filename .= $db_type . '.sql';
        break;
      case 'ibm_db2':
        $install_filename .= 'db2.sql';
        break;
      case 'odbc':
        $install_filename .= $_SESSION['odbc_db'] . '.sql';
        break;
      case 'postgresql':
        $install_filename .= 'postgres.sql';
        break;
      case 'sqlite':
        include_once 'sql/tables-sqlite.php';
        populate_sqlite_db( $real_db, $c );
        $install_filename = '';
        break;
      case 'sqlite3':
        include_once 'sql/tables-sqlite3.php';
        populate_sqlite_db( $real_db, $c );
        $install_filename = '';
        break;
      default:
        $install_filename .= 'mysql.sql';
    }
    db_populate( $install_filename, $display_sql );
  }
  if( empty( $display_sql ) ) {
    // Convert passwords to md5 hashes if needed.
    $res = dbi_execute( 'SELECT cal_login, cal_passwd FROM webcal_user',
      array(), false, $show_all_errors );
    if( $res ) {
      while( $row = dbi_fetch_row( $res ) ) {
        if( strlen( $row[1] ) < 30 )
          dbi_execute( 'UPDATE webcal_user SET cal_passwd = ?
            WHERE cal_login = ?', array( md5( $row[1] ), $row[0] ) );
      }
      dbi_free_result( $res );
    }

    // If new install, run 0 GMT offset
    // just to set webcal_config.WEBCAL_TZ_CONVERSION.
    if( $_SESSION['old_program_version'] == 'new_install' )
      convert_server_to_GMT();

    // For upgrade to v1.1b
    // we need to convert existing categories and repeating events.
    do_v11b_updates();

    // v1.1e requires converting webcal_site_extras to webcal_reminders.
    do_v11e_updates();

    // Update the version info.
    get_installed_version( true );

    $_SESSION['blank_database'] = '';
  } //end if $display_sql

} //end database installation

// Set the value of the underlying database for ODBC connections.
if( ! empty( $action ) && $action == 'set_odbc_db' )
  $_SESSION['odbc_db'] = getPostValue( 'odbc_db' );

$post_action  = getPostValue( 'action' );
$post_action2 = getPostValue( 'action2' );
// Is this a db connection test?
// If so, just test the connection, show the result and exit.
if( ! empty( $post_action ) && $post_action == $testSettingsStr && !
    empty( $_SESSION['validuser'] ) ) {
  $_SESSION['db_success'] = false;
  $db_cachedir   = getPostValue( 'form_db_cachedir' );
  $db_database   = getPostValue( 'form_db_database' );
  $db_host       = getPostValue( 'form_db_host' );
  $db_login      = getPostValue( 'form_db_login' );
  $db_password   = getPostValue( 'form_db_password' );
  $db_persistent = getPostValue( 'db_persistent' );
  $db_type       = getPostValue( 'form_db_type' );
  $response_msg  = $response_msg2= '';

  // Allow field length to change if needed.
  $onload = 'db_type_handler();';

  // Disable warnings.
  show_errors();

  $real_db =( $db_type == 'sqlite' || $db_type == 'sqlite3'
    ? get_full_include_path( $db_database ) : $db_database );

  $c = dbi_connect( $db_host, $db_login, $db_password, $real_db, false );

  // Re-enable warnings.
  show_errors( true );

  if( $c ) {
    $_SESSION['db_success'] = true;

    // Do some queries to try to determine the previous version.
    get_installed_version();
    $response_msg = translate( 'Connection Successful...' );
  } else {
    $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
    // See if user is valid, but database doesn't exist.
    // The normal call to dbi_connect simply return false for both conditions.

    // TODO figure out how to remove this hardcoded link.
    if( $db_type == 'ibase' )
      $c =
        dbi_connect( $db_host, $db_login, $db_password, $firebird_path, false );
    elseif( $db_type == 'mssql' )
      $c = mssql_connect( $db_host, $db_login, $db_password );
    elseif( $db_type == 'mysql' )
      $c = mysql_connect( $db_host, $db_login, $db_password );
    elseif( $db_type == 'mysqli' )
      $c = dbi_connect( $db_host, $db_login, $db_password, $db_database );
    elseif( $db_type == 'postgresql' )
      $c =
        dbi_connect( $db_host, $db_login, $db_password, 'template1', false );

    // TODO: Code remaining database types.

    if( $c ) { // Credentials are valid, but database doesn't exist.
      $response_msg =
        translate( 'Correct your entries or click the Create New...' );
      $_SESSION['db_noexist'] = true;
    } else
      $response_msg = $failure .( $db_type == 'ibase'
        ? $manualStr . '</blockquote>' . "\n"
        : dbi_error() . '</blockquote>' . "\n"
         . translate( 'Correct your entries and try again.' ) );
  } //end if($c)

  // Test db_cachedir directory for write permissions.
  if( strlen( $db_cachedir ) > 0 ) {
    if( ! is_dir( $db_cachedir ) )
      $response_msg2 = $failureStr
       . str_replace( 'XXX', $cachedirStr,
         translate( 'XXX does not exist' ) );
    else
    if( ! is_writable( $db_cachedir ) )
      $response_msg2 = $failureStr
       . str_replace( 'XXX', $cachedirStr,
         translate( 'XXX is not writable' ) );
  }

  // Is this a db create?
  // If so, just test the connection, show the result and exit.
} else
if( ! empty( $post_action2 ) && $post_action2 == $createNewStr && !
    empty( $_SESSION['validuser'] ) && ! empty( $_SESSION['db_noexist'] ) ) {
  $_SESSION['db_success'] = $db_persistent = false;
  $db_cachedir= getPostValue( 'form_db_cachedir' );
  $db_database= getPostValue( 'form_db_database' );
  $db_host    = getPostValue( 'form_db_host' );
  $db_login   = getPostValue( 'form_db_login' );
  $db_password= getPostValue( 'form_db_password' );
  $db_type    = getPostValue( 'form_db_type' );

  // Allow ODBC field to be visible if needed.
  $onload = 'db_type_handler();';

  $sql = 'CREATE DATABASE ' . $db_database;

  // We don't use the normal dbi_execute because we need to know
  // the difference between no conection and no database.
  if( $db_type == 'ibase' )
    $response_msg = $failure . $manualStr . '</blockquote>' . "\n";
  elseif( $db_type == 'mssql' ) {
    $c = dbi_connect( $db_host, $db_login, $db_password, 'master', false );
    if( $c ) {
      dbi_execute( $sql . ';', array(), false, $show_all_errors );
      if( ! @mssql_select_db( $db_database ) ) {
        $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
      } else {
        $_SESSION['db_noexist'] = false;
        $_SESSION['old_program_version'] = 'new_install';
      }
    } else
      $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
  } elseif( $db_type == 'mysql' ) {
    $c = dbi_connect( $db_host, $db_login, $db_password, 'mysql', false );
    if( $c ) {
      dbi_execute( $sql . ';', array(), false, $show_all_errors );
      if( ! @mysql_select_db( $db_database ) )
        $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
      else {
        $_SESSION['db_noexist'] = false;
        $_SESSION['old_program_version'] = 'new_install';
      }
    } else
      $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
  } elseif( $db_type == 'mysqli' ) {
    $c = dbi_connect( $db_host, $db_login, $db_password, '', false );
    if( $c ) {
      dbi_execute( $sql . ';', array(), false, $show_all_errors );
      if( ! $c->select_db($db_database ) )
        $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
      else {
        $_SESSION['db_noexist'] = false;
        $_SESSION['old_program_version'] = 'new_install';
      }
    } else
      $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
  } elseif( $db_type == 'postgresql' ) {
    $c = dbi_connect( $db_host, $db_login, $db_password, 'template1', false );
    if( $c ) {
      dbi_execute( $sql, array(), false, $show_all_errors );
      $_SESSION['db_noexist'] = false;
    } else
      $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
  }

  // TODO code remaining database types.

  // Allow bypass of TZ Conversion.
  $_SESSION['tz_conversion'] = 'Y';
}

// Is this a Timezone Convert?
// Manual tzoffset input in URL.
$tzoffset = getGetValue( 'tzoffset' );

if( ! empty( $tzoffset ) )
  $action = 'tz_convert';

// If so, run it.
if( ! empty( $action ) && $action == 'tz_convert' && !
    empty( $_SESSION['validuser'] ) ) {
  $cutoffdate   = getValue( 'cutoffdate', '-?[0-9]+' );
  $db_cachedir  = getPostValue( 'form_db_cachedir' );
  $db_database  = $settings['db_database'];
  $db_host      = $settings['db_host'];
  $db_login     = $settings['db_login'];
  $db_password  = $settings['db_password'];
  $db_persistent= false;
  $db_type      = $settings['db_type'];

  // Avoid false visibilty of single user login.
  $onload = 'auth_handler();';
  $real_db=( $db_type == 'sqlite' || $db_type == 'sqlite3'
    ? get_full_include_path( $db_database ) : $db_database );

  $c = dbi_connect( $db_host, $db_login, $db_password, $real_db, false );

  if( $c ) {
    $ret = convert_server_to_GMT( $tzoffset, $cutoffdate );
    if( substr( $ret, 3, 21 ) == 'Conversion Successful' ) {
      $_SESSION['tz_conversion'] = 'Success';
      $response_msg = $tzSuccessStr;
    } else
      $response_msg = translate( 'Error Converting Timezone' );
  } else
    $response_msg = $failure . dbi_error() . '</blockquote>' . "\n";
}

// Is this a call to phpinfo()?
if( ! empty( $action ) && $action == 'phpinfo' ) {
  if( ! empty( $_SESSION['validuser'] ) )
    phpinfo();
  else
    echo translate( 'You are not authorized.' );

  exit;
}

// Session check counter.
if( isset( $_SESSION['check'] ) )
  $_SESSION['check']++;
else
  $_SESSION['check'] = 0;

$canWrite = false;
$exists = file_exists( $file );

if( $exists )
  $canWrite = is_writable( $file );
else {
  // Check to see if we can create the settings file.
  $testFd = @fopen( $file, 'w+b', false );

  if( file_exists( $file ) )
    $canWrite = $exists = $forcePassword = true;

  @fclose( $testFd );
}

// If we are handling a form POST,
// take that data and put it in settings array.
$x = getPostValue( 'form_db_type' );

if( empty( $x ) ) {
  // No form was posted. Set defaults if none set yet.
  if( ! file_exists( $file ) || count( $settings ) == 1 ) {
    $settings['db_cachedir']       = '/tmp';
    $settings['db_database']       = 'intranet';
    $settings['db_host']           = 'localhost';
    $settings['db_login']          = 'webcalendar';
    $settings['db_password']       = 'webcal01';
    $settings['db_persistent']     =
    $settings['readonly']          =
    $settings['single_user']       =
    $settings['use_http_auth']     = 'false';
    if ( function_exists ( 'mysqli_connect' ) )
      $settings['db_type']           = 'mysqli';
    else
      $settings['db_type']           = 'mysql';
    $settings['install_password']  =
    $settings['single_user_login'] = '';
    $settings['user_inc']          = 'user.php';
  }
} else {
  $settings['db_cachedir']      = getPostValue( 'form_db_cachedir' );
  $settings['db_database']      = getPostValue( 'form_db_database' );
  $settings['db_host']          = getPostValue( 'form_db_host' );
  $settings['db_login']         = getPostValue( 'form_db_login' );
  $settings['db_password']      = getPostValue( 'form_db_password' );
  $settings['db_persistent']    = getPostValue( 'form_db_persistent' );
  $settings['db_type']          = getPostValue( 'form_db_type' );
  $settings['install_password'] = ( isset( $settings['install_password'] )
    ? $settings['install_password'] : '' );
  $settings['readonly'] = ( isset( $settings['readonly'] )
    ? $settings['readonly'] : 'false' );
  $settings['single_user'] = ( isset( $settings['single_user'] )
    ? $settings['single_user'] : 'false' );
  $settings['single_user_login'] = ( isset( $settings['single_user_login'] )
    ? $settings['single_user_login'] : '' );
  $settings['use_http_auth'] = ( isset( $settings['use_http_auth'] )
    ? $settings['use_http_auth'] : 'false' );
  $settings['user_inc'] = ( isset( $settings['user_inc'] )
    ? $settings['user_inc'] : 'user.php' );
}
$y = getPostValue( 'app_settings' );
if( ! empty( $y ) ) {
  $formUserStr                   = getPostValue( 'form_user_inc' );
  $settings['mode']              = getPostValue( 'form_mode' );
  $settings['readonly']          = getPostValue( 'form_readonly' );
  $settings['single_user']       = $settings['use_http_auth']= 'false';
  $settings['single_user_login'] = getPostValue( 'form_single_user_login' );
  $settings['user_inc']          = 'user.php';

  if( $formUserStr == 'http' )
    $settings['use_http_auth'] = 'true';
  elseif( $formUserStr == 'none' )
    $settings['single_user'] = 'true';
  else
    $settings['user_inc'] = $formUserStr;

  // Save Application Name and Server URL.
  $_SESSION['application_name'] = getPostValue( 'form_application_name' );
  $_SESSION['server_url']       = getPostValue( 'form_server_url' );
  $db_persistent = false;
  $db_type = $settings['db_type'];

  $db_database = ( $db_type == 'sqlite' || $db_type == 'sqlite3'
    ? get_full_include_path( $settings['db_database'] )
    : $settings['db_database'] );

  if( empty( $settings['db_password'] ) )
    $settings['db_password'] = '';

  $c = dbi_connect( $settings['db_host'], $settings['db_login'],
    $settings['db_password'], $db_database, false );

  if( $c ) {
    if( isset( $_SESSION['application_name'] ) ) {
      dbi_execute( 'DELETE FROM webcal_config
        WHERE cal_setting = \'APPLICATION_NAME\'' );
      dbi_execute( 'INSERT INTO webcal_config ( cal_setting, cal_value )
        VALUES ( \'APPLICATION_NAME\', ? )',
        array( $_SESSION['application_name'] ) );
    }
    if( isset( $_SESSION['server_url'] ) ) {
      dbi_execute( 'DELETE FROM webcal_config
        WHERE cal_setting = \'SERVER_URL\'' );
      dbi_execute( 'INSERT INTO webcal_config ( cal_setting, cal_value )
      VALUES ( \'SERVER_URL\', ? )', array( $_SESSION['server_url'] ) );
    }
  }
  $do_load_admin = getPostValue( 'load_admin' );

  if( ! empty( $do_load_admin ) ) {
    // Add default admin user if not exists.
    db_load_admin();
    // Check if an Admin account exists.
    $_SESSION['admin_exists'] = db_check_admin();
  }
  $setup_complete = true;
}
// Save settings to file now.
if( ! empty( $x ) || ! empty( $y ) ) {
  if ( $doLogin ) {
    // Hack attempt :-)
    echo "Bugger off.<br/>"; exit;
  }
  $fd = @fopen( $file, 'w+b', false );

  if( empty( $fd ) )
    $onload = 'alert( \'' . str_replace( 'XXX', $file,
      translate( 'Error Unable to write to file XXX.', true ) ) . "\\n"
     . ( file_exists( $file )
      ? translate( 'Please change the file permissions of this file.', true )
      : translate( 'Please change includes dir permission', true ) ) . '\' );';
  else {
    if ( function_exists ( "date_default_timezone_set" ) )
      date_default_timezone_set ( "America/New_York");
    fwrite( $fd, '<?php' . "\r\n" . '/* updated via install/index.php on '
       . date( 'r' ) . "\r\n" );
    foreach( $settings as $k => $v ) {
      if( $v != '<br />' && $v != '' )
        fwrite( $fd, $k . ': ' . $v . "\r\n" );
    }
    fwrite( $fd, '# end settings.php */' . "\r\n?>\r\n" );
    fclose( $fd );

    if( $post_action != $testSettingsStr && $post_action2 != $createNewStr )
      $onload .= 'alert( \''
       . translate( 'Your settings have been saved.', true ) . "\\n\\n' );";

    // Change to read/write by us only (only applies if we created file)
    // and read-only by all others. Would be nice to make it 600,
    // but the "send_reminders.php" script is usually run under a different
    // user than the web server.
    @chmod( $file, 0644 );
  }
}
$noStr  = translate( 'No' );
$offStr = translate( 'OFF' );
$onStr  = translate( 'ON' );
$yesStr = translate( 'Yes' );

echo '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title>' . translate( 'WebCalendar Setup Wizard' ) . '</title>
    <meta http-equiv="Content-Type" content="text/html; charset='
 . translate( 'charset' ) . '" />
    <script>
<!-- <![CDATA[
      var xlate = [];
      xlate[\'invalidColor\'] = \'' . translate( 'Invalid Color', true ) . '\';
' . ( empty( $_SESSION['validuser'] ) ? '' : '
      function testPHPInfo() {
        var url = "index.php?action=phpinfo";

        window.open( url, \'wcTestPHPInfo\', '
   . '\'width=800,height=600,resizable=yes,scrollbars=yes\' );
      }' ) . '
      function validate( form ) {
        // Only check to make sure single-user login is specified
        // if in single-user mode.
        var
          err = \'\',
          form = document.form_app_settings,
          listid = 0; // Find id of single user object.

        for( i = 0; i < form.form_user_inc.length; i++ ) {
          if( form.form_user_inc.options[i].value == \'none\' )
            listid = i;
        }
        if( form.form_user_inc.options[listid].selected ) {
          if( form.form_single_user_login.value.length == 0 ) {
            // No single user login specified.
            alert( \''
 . translate( 'Error you must specify a Single-User Login', true ) . '\' );
            form.form_single_user_login.focus();
            return false;
          }
        }
        if( form.form_server_url.value == \'\' ) {
          err += "' . translate( 'Server URL is required.', true ) . '\n";
          form.form_server_url.select();
          form.form_server_url.focus();
        }
        else if( form.form_server_url.value.charAt(
          form.form_server_url.value.length - 1 ) != \'/\' ) {
          err += "' . translate( 'Server URL must end with /.', true ) . '\n";
          form.form_server_url.select();
          form.form_server_url.focus();
        }
        if( err != \'\' ) {
          alert( "' . translate( 'Error', true ) . ':\n\n" + err );
          return false;
        }
        // Submit form...
        form.submit();
      }
      function auth_handler() {
        var
          form = document.form_app_settings,
          listid = 0; // Find id of single user object.
        for( i = 0; i < form.form_user_inc.length; i++ ) {
          if( form.form_user_inc.options[i].value == \'none\' )
            listid = i;
        }
        if( form.form_user_inc.options[listid].selected ) {
          makeVisible( \'singleuser\' );
        } else {
          makeInvisible( \'singleuser\' );
        }
      }
      function db_type_handler() {
        var
          form = document.dbform,
          listid = 0,
          selectvalue = form.form_db_type.value;

        if( selectvalue == \'sqlite\' || $db_type == \'sqlite3\'
            || selectvalue == \'ibase\' ) {
          form.form_db_database.size = 65;
          document.getElementById( \'db_name\' ).innerHTML = \''
 . $databaseNameStr . ': ' . translate( 'Full Path (no backslashes)' ) . '\';
        } else {
          form.form_db_database.size = 20;
          document.getElementById( \'db_name\' ).innerHTML = \''
 . $databaseNameStr . ': \';
        }
      }
      function chkPassword() {
        var
          form = document.dbform,
          db_pass = form.form_db_password.value,
          illegalChars = /\#/;
          // Do not allow #.../\#/ would stop all non-alphanumeric.

        if( illegalChars.test( db_pass ) ) {
          alert( \''
 . translate( 'The password contains illegal characters.', true ) . '\' );
          form.form_db_password.select();
          form.form_db_password.focus();
          return false;
        }
      }
//]]> -->
    </script>
    <script src="../includes/js/visible.js"></script>
    <style>
      body {
        margin:0;
        background:#fff;
        font-family:Arial, Helvetica, sans-serif;
      }
      table {
        border:0;
      }
      th.header,
      th.pageheader,
      th.redheader {
        background:#eee;
      }
      th.pageheader {
        padding:10px;
        font-size:18px;
      }
      th.header,
      th.redheader {
        font-size:14px;
      }
      th.redheader,
      .notrecommended {
        color:red;
      }
      td {
        padding:5px;
      }
      td.prompt,
      td.subprompt {
        padding-right:20px;
        font-weight:bold;
      }
      td.subprompt {
        font-size:12px;
      }
      div.nav {
        margin:0;
        border-bottom:1px solid #000;
      }
      div.main {
        margin:10px;
      }
      li {
        margin-top:10px;
      }
      doc.li {
        margin-top:5px;
      }
      .recommended {
        color:green;
      }
    </style>
  </head>
  <body' . ( empty( $onload ) ? '' : ' onload="' . $onload . '"' ) . '>';

if( empty( $_SESSION['step'] ) || $_SESSION['step'] < 2 ) {
  $class = ( version_compare( phpversion(), '4.1.0', '>=' ) ? '' : 'not' )
   . 'recommended';
  echo '
    <table border="1" width="90%" class="aligncenter">
      <tr>
        <th class="pageheader" colspan="2">'
   . str_replace( 'XXX', translate( '1' ), $wizardStr ) . '</th>
      </tr>
      <tr>
        <td colspan="2" width="50%">'
   . translate( 'This installation wizard will guide you...' ) . '
          <ul>
            <li><a href="../docs/WebCalendar-SysAdmin.html" target="_docs">'
   . translate( 'System Administrators Guide' ) . '</a></li>
            <li><a href="../docs/WebCalendar-SysAdmin.html#faq" target="_docs"'
   . '">' . '<acronym title="' . translate( 'Frequently Asked Questions' )
   . '">' . translate( 'FAQ' ) . '</acronym></a></li>
            <li><a href="../docs/WebCalendar-SysAdmin.html#trouble" '
   . 'target="_docs">' . translate( 'Troubleshooting' ) . '</a></li>
            <li><a href="../docs/WebCalendar-SysAdmin.html#help" target="_docs">'
   . translate( 'Getting Help' ) . '</a></li>
            <li><a href="../UPGRADING.html" target="_docs">'
   . translate( 'Upgrading Guide' ) . '</a></li>
            <li><a href="http://www.k5n.us/wiki/" target="_docs">'
   . translate( 'User Supported Wiki' ) . '</a></li>
          </ul>
        </td>
      </tr>
      <tr>
        <th class="header" colspan="2">'
   . translate( 'PHP Version Check' ) . '</th>
      </tr>
      <tr>
        <td>'
   . translate( 'Check to see if PHP 4.1.0 or greater is installed.' ) . '</td>
        <td class="' . $class . '"><img src="' . ( $class == 'recommended'
    ? 'recommended.gif' : 'not_recommended.jpg' ) . '" alt="" />&nbsp;'
   . translate( 'PHP version' ) . ' ' . phpversion() . '</td>
      </tr>
      <tr>
        <th class="header" colspan="2">' . translate( 'PHP Settings' )
   . ( empty( $_SESSION['validuser'] )
    ? '' : '&nbsp;<input name="action" type="button" value="'
     . translate( 'Detailed PHP Info' )
     . '" onClick="testPHPInfo()" />' ) . '</th>
      </tr>';
  foreach( $php_settings as $setting ) {
    $ini_get_result = get_php_setting( $setting[1], $setting[3] );
    $class = ( $ini_get_result == $setting[2] ? '' : 'not' ) . 'recommended';
    echo '
      <tr>
        <td class="prompt">' . $setting[0] . '</td>
        <td class="' . $class . '"><img src="'
     . ( $class == 'recommended' ? 'recommended.gif' : 'not_recommended.jpg' )
     . '" alt="" />&nbsp;' . $ini_get_result . '</td>
      </tr>';
  }
  foreach( $php_constants as $constant ) {
    $class = ( $constant[1] == $constant[2] ? '' : 'not' ) . 'recommended';
    echo '
      <tr>
        <td class="prompt">' . $constant[0] . '</td>
        <td class="' . $class . '"><img alt="" src="'
     . ( $class == 'recommended'
      ? 'recommended.gif" />&nbsp;' . $onStr
      : 'not_recommended.jpg" />&nbsp;' . $offStr ) . '</td>
      </tr>';
  }
  foreach( $php_modules as $module ) {
    $class = ( get_php_modules( $module[1] ) == $module[2]
      ? '' : 'not' ) . 'recommended';
    echo '
      <tr>
        <td class="prompt">' . $module[0] . '</td>
        <td class="' . $class . '"><img src="'
     . ( $class == 'recommended' ? 'recommended.gif"' : 'not_recommended.jpg"' )
     . ' alt="" />&nbsp;' . get_php_modules( $module[1] ) . '</td>
      </tr>';
  }
  $settingsStatStr = translate( 'settings.php Status' );
  echo '
      <tr>
        <th class="header" colspan="2">'
   . translate( 'Session Check' ) . '</th>
      </tr>
      <tr>
        <td>'
  . translate( 'To test the proper operation of sessions...' ) . '</td>
        <td class="' . ( $_SESSION['check'] > 0 ? '' : 'not' ) . 'recommended'
   . '"><img src="'
   . ( $_SESSION['check'] > 0 ? 'recommended.gif"' : 'not_recommended.jpg"' )
   . ' alt="" />&nbsp;' . translate( 'SESSION COUNTER' ) . ': '
   . $_SESSION['check'] . '</td>
      </tr>
      <tr>
        <th colspan="2" class="'
  // If the settings file doesn't exist or we can't write to it,
  // echo an error header...
  . ( ! $exists || ! $canWrite
    ? 'redheader">' . $settingsStatStr . ': ' . translate( 'Error' )
    // otherwise, echo a regular header.
    : 'header">' . $settingsStatStr )
   . '</th>
      </tr>
      <tr>
        <td';
  // If the settings file exists, but we can't write to it...
  if( $exists && ! $canWrite )
    echo '><img src="not_recommended.jpg" alt="" />&nbsp;'
     . translate( 'The file permissions of settings.php are set...' ) . ':</td>
        <td><blockquote><b>' . realpath( $file ) . '</b></blockquote></td>
      </tr>';
  // or, if the settings file doesn't exist
  // and we can't write to the includes directory...
  else
  if( ! $exists && ! $canWrite )
    echo ' colspan="2">
          <img src="not_recommended.jpg" alt="" />&nbsp;'
     . translate( 'The file permissions of the includes directory are set...' )
     . ': <blockquote><b>' . realpath( $fileDir ) . '</b></blockquote></td>
      </tr>';
  // If settings.php DOES exist & we CAN write to it...
  else {
    echo '>'
     . translate( 'Your settings.php file appears to be valid.' ) . '</td>
        <td class="recommended"><img src="recommended.gif" alt="" />&nbsp;'
     . translate( 'OK' ) . '</td>
      </tr>';

    if( empty( $_SESSION['validuser'] ) ) {
      echo '
      <tr>
        <th colspan="2" class="header">'
       . translate( 'Configuration Wizard Password' ) . '</th>
      </tr>
      <tr>
        <td colspan="2" class="aligncenter" style="border:none">';

      if( $doLogin )
        echo '
          <form action="index.php" method="post" name="dblogin">
            <table>
              <tr>
                <th>' . $passwordStr . ':</th>
                <td>
                  <input name="password" type="password" />
                  <input type="submit" value="' . $loginStr . '" />
                </td>
              </tr>
            </table>
          </form>';
      else
      if( $forcePassword )
        echo '
          <form action="index.php" method="post" name="dbpassword">
            <table>
              <tr>
                <th colspan="2" class="header">'
         . translate( 'Create Settings File Password' ) . '</th>
              </tr>
              <tr>
                <th>' . $passwordStr . ':</th>
                <td><input name="password1" type="password" /></td>
              </tr>
              <tr>
                <th>' . translate( 'Password (again)' ) . '</th>
                <td><input name="password2" type="password" /></td>
              </tr>
              <tr>
                <td colspan="2" class="aligncenter"><input type="submit" value="'
         . translate( 'Set Password' ) . '" /></td>
              </tr>
            </table>
          </form>';
    }
  }
  echo '
        </td>
      </tr>
    </table>' . ( empty( $_SESSION['validuser'] ) ? '' : '
    <table width="90%" class="aligncenter">
      <tr>
        <td class="aligncenter">
          <form action="index.php?action=switch&amp;page=2" method="post">
            <input type="submit" value="' . $nextStr . ' ->" />
          </form>
        </td>
      </tr>
    </table>' );

  // BEGIN STEP 2
} elseif( $_SESSION['step'] == 2 ) {
  echo '
    <table border="1" width="90%" class="aligncenter">
      <tr>
        <th class="pageheader" colspan="2">'
   . str_replace( 'XXX', translate( '2' ), $wizardStr ) . '</th>
      </tr>
      <tr>
        <td colspan="2" width="50%">'
   . translate( 'db setup directions...' ) . '</td>
      </tr>
      <tr>
        <th colspan="2" class="header">'
   . translate( 'Database Status' ) . '</th>
      </tr>
      <tr>
        <td>
          <ul>
<!--
            <li>'
   . translate( 'Supported databases for your PHP installation' ) . ':</li>
-->';

  if( ! empty( $_SESSION['db_success'] ) && $_SESSION['db_success'] ) {
    echo '
            <li class="recommended"><img src="recommended.gif" alt="" />&nbsp;'
     . translate( 'Your current database settings are able to access the database.' )
     . '</li>';
    if( ! empty( $response_msg ) && empty( $response_msg2 ) )
      echo '
            <li class="recommended"><img src="recommended.gif" alt="" />&nbsp;'
       . $response_msg . '</li>';
    elseif( empty( $response_msg2 ) && empty( $_SESSION['db_success'] ) )
      echo '
            <li class="notrecommended"><img src="not_recommended.jpg" '
       . 'alt="" />&nbsp;' . translate( 'Please Test Settings' ) . '</li>';
  } else
    echo '
            <li class="notrecommended"><img src="not_recommended.jpg" '
     . 'alt="" />&nbsp;'
     . translate( 'Your current database settings are not able...' ) . '</li>'
     . ( empty( $response_msg ) ? '' : '
            <li class="notrecommended"><img src="not_recommended.jpg" '
       . 'alt="" />&nbsp;' . $response_msg . '</li>' );

  echo ( empty( $response_msg2 ) ? '' : '
            <li class="notrecommended"><img src="not_recommended.jpg" '
     . 'alt="" />&nbsp;<b>' . $response_msg2 . '</b></li>' ) . '
          </ul>
        </td>
      </tr>
      <tr>
        <th class="header" colspan="2">'
   . translate( 'Database Settings' ) . '</th>
      </tr>
      <tr>
        <td>
          <form action="index.php" method="post" name="dbform" '
   . 'onSubmit="return chkPassword()">
            <table class="alignright">
              <tr>
                <td rowspan="7" width="20%">&nbsp;</td>
                <td class="prompt" width="25%" class="alignbottom">'
   . '<label for="db_type">' . translate( 'Database Type' ) . ':</label></td>
                <td class="alignbottom">
                  <select name="form_db_type" id="db_type" '
   . 'onChange="db_type_handler();">';

  $supported = array();
  if( function_exists( 'db2_pconnect' ) )
    $supported['ibm_db2'] = 'IBM DB2 Universal Database';

  if( function_exists( 'ibase_connect' ) )
    $supported['ibase'] = 'Interbase';

  if( function_exists( 'mssql_connect' ) )
    $supported['mssql'] = 'MS SQL Server';

  if( function_exists( 'mysql_connect' ) )
    $supported['mysql'] = 'MySQL';

  if( function_exists( 'mysqli_connect' ) )
    $supported['mysqli'] = 'MySQL (Improved)';

  if( function_exists( 'odbc_pconnect' ) )
    $supported['odbc'] = 'ODBC';

  if( function_exists( 'OCIPLogon' ) )
    $supported['oracle'] = 'Oracle (OCI)';

  if( function_exists( 'pg_pconnect' ) )
    $supported['postgresql'] = 'PostgreSQL';

  if( function_exists( 'sqlite_open' ) )
    $supported['sqlite'] = 'SQLite';

  if( class_exists( 'SQLite3' ) )
    $supported['sqlite3'] = 'SQLite3';

  foreach( $supported as $key => $value ) {
    echo '
                    <option value="' . $key . '" '
     . ( $settings['db_type'] == $key ? $selected : '' )
     . '>' . $value . '</option>';
  }
  $supported = array();

  echo '
                  </select>
                </td>
              </tr>
              <tr>
                <td class="prompt"><label for="server">'
   . translate( 'Server' ) . ':</label></td>
                <td colspan="2"><input name="form_db_host" id="server" '
   . 'size="20" value="' . ( empty($settings['db_host']) ? '' : $settings['db_host']) . '" /></td>
              </tr>
              <tr>
                <td class="prompt"><label for="login">'
   . $loginStr . ':</label></td>
                <td colspan="2"><input name="form_db_login" id="login" '
   . 'size="20" value="' . ( empty($settings['db_login']) ? '' : $settings['db_login']) . '" /></td>
              </tr>
              <tr>
                <td class="prompt"><label for="pass">'
   . $passwordStr . ':</label></td>
                <td colspan="2"><input name="form_db_password" id="pass" '
   . 'size="20" value="' . (empty($settings['db_password']) ? '' : $settings['db_password']) . '" /></td>
              </tr>
              <tr>
                <td class="prompt" id="db_name"><label for="database">'
   . $databaseNameStr . ':</label></td>
                <td colspan="2"><input name="form_db_database" id="database" '
   . 'size="20" value="' . $settings['db_database'] . '" /></td>
              </tr>'
  /* This a workaround for postgresql. The db_type should be 'pgsql'
     but 'postgresql' is used in a lot of places...
     so this is easier for now :( */
   . ( substr( php_sapi_name(), 0, 3 ) <> 'cgi' &&
    ini_get( ( $settings['db_type'] == 'postgresql'
        ? 'pgsql' : $settings['db_type'] ) . '.allow_persistent' ) ? '
              <tr>
                <td class="prompt"><label for="conn_pers">'
     . translate( 'Connection Persistence' ) . ':</label></td>
                <td colspan="2">
                  <label><input name="form_db_persistent" value="true" '
     . 'type="radio"' . ( $settings['db_persistent'] == 'true'
      ? $checked : '' ) . ' />'
     . translate( 'Enabled' ) . '</label>&nbsp;&nbsp;&nbsp;&nbsp;
                  <label><input name="form_db_persistent" value="false" '
     . 'type="radio"' . ( $settings['db_persistent'] != 'true'
      ? $checked : '' ) . ' />' . translate( 'Disabled' ) . '</label>
                </td>
              </tr>' :/* Need to set a default value. */ '
              <input name="form_db_persistent" value="false" type="hidden" />' );

  if( function_exists( 'file_get_contents' ) ) {
    if( empty( $settings['db_cachedir'] ) )
      $settings['db_cachedir'] = '';

    echo '
              <tr>
                <td class="prompt">' . $cachedirStr . ':</td>
                <td><input type="text" size="70" name="form_db_cachedir" '
     . 'id="form_db_cachedir" value="' . $settings['db_cachedir'] . '" /></td>
              </tr>';
  } //end test for file_get_contents

  echo ( empty( $_SESSION['validuser'] ) ? '' : '
              <tr>
                <td class="aligncenter" colspan="3">
                  <input name="action" type="submit" value="' . $testSettingsStr
     . '" class="' . ( empty( $_SESSION['db_success'] ) ? 'not' : '' )
     . 'recommended' . '" />' . ( ! empty( $_SESSION['db_noexist'] ) &&
      empty( $_SESSION['db_success'] ) ? '
                  <input name="action2" type="submit" value="' . $createNewStr
       . '" class="recommended" />' : '' ) . '
                </td>
              </tr>
            </table>
          </form>
        </td>
      </tr>
    </table>' ) . '
    <table width="90%" class="aligncenter">
      <tr>
        <td class="alignright" width="40%">
          <form action="index.php?action=switch&amp;page=1" method="post">
            <input type="submit" value="<- ' . $backStr . '" />
          </form>
        </td>
        <td class="aligncenter" width="20%">
          <form action="index.php?action=switch&amp;page=3" method="post">
            <input type="submit" value="' . $nextStr . ' ->" '
   . ( empty( $_SESSION['db_success'] ) ? 'disabled' : '' ) . ' />
          </form>
        </td>
        <td class="alignleft" width="40%">
          <form action="" method="post">
            <input type="button" value="' . $logoutStr . '" '
   . ( empty( $_SESSION['validuser'] ) ? 'disabled' : '' )
   . ' onclick="document.location.href=\'index.php?action=logout\'" />
          </form>
        </td>
      </tr>
    </table>';
} elseif( $_SESSION['step'] == 3 ) {
  $_SESSION['db_updated'] = false;
  if( $_SESSION['old_program_version'] == $PROGRAM_VERSION &&
    empty( $_SESSION['blank_database'] ) ) {
    $response_msg = translate( 'All your database tables appear to be up...' );
    $_SESSION['db_updated'] = true;
    // $response_msg .= '<br />Previous Version: ' .
    // $_SESSION['old_program_version'] . '<br />
    // New Version: ' . $PROGRAM_VERSION;
  } else
    $response_msg = ( $_SESSION['old_program_version'] == 'new_install'
      ? translate( 'This appears to be a new installation...' )
      : ( empty( $_SESSION['blank_database'] )
        ? str_replace( array('XXX', 'YYY'),
          array( $_SESSION['old_program_version'], $PROGRAM_VERSION ),
          translate( 'This appears to be an upgrade...' ) )
        : translate( 'The database requires some data input...' ) ) );

  echo '
    <table border="1" width="90%" class="aligncenter">
      <tr>
        <th class="pageheader" colspan="2">'
   . str_replace( 'XXX', translate( '3' ), $wizardStr ) . '</th>
      </tr>
      <tr>
        <td colspan="2" width="50%">'
   . translate( 'In this section we will perform...' ) . '</td>
      </tr>
      <tr>
        <th colspan="2" class="header">'
   . translate( 'Database Status' ) . '</th>
      </tr>
      <tr>
        <td>' . $response_msg . '</td>
      </tr>
      <tr>
        <th colspan="2" class="';

  if( ! empty( $_SESSION['db_updated'] ) )
    echo 'header">' . translate( 'No database actions are required.' ) . '</th>
      </tr>';
  else {
    echo 'redheader">'
     . translate( 'The following database actions are required' ) . ':</th>
      </tr>';

    if( $settings['db_type'] == 'odbc' && empty( $_SESSION['db_updated'] ) ) {
      if( empty( $_SESSION['odbc_db'] ) )
        $_SESSION['odbc_db'] = 'mysql';

      echo '
      <tr>
        <td id="odbc_db" class="aligncenter" nowrap>
          <form action="index.php?action=set_odbc_db" method="post" '
       . 'name="set_odbc_db">' . translate( 'ODBC Underlying Database' ) . '
            <select name="odbc_db" onchange="document.set_odbc_db.submit();">
              <option value="ibase"'
       . ( $_SESSION['odbc_db'] == 'ibase' ? $selected : '' )
       . '>Interbase</option>
              <option value="mssql"'
       . ( $_SESSION['odbc_db'] == 'mssql' ? $selected : '' )
       . '>MS SQL</option>
              <option value="mysql"'
       . ( $_SESSION['odbc_db'] == 'mysql' ? $selected : '' ) . '>MySQL</option>
              <option value="oracle"'
       . ( $_SESSION['odbc_db'] == 'oracle' ? $selected : '' )
       . '>Oracle</option>
              <option value="postgresql"'
       . ( $_SESSION['odbc_db'] == 'postgresql' ? $selected : '' )
       . '>PostgreSQL</option>
            </select>
          </form>
        </td>
      </tr>';
    }

    echo '
      <tr>
        <td class="recommended aligncenter">'
     . ( ! empty( $settings['db_type'] ) &&
      empty( $_SESSION['blank_database'] ) &&
      ( $settings['db_type'] == 'ibase' || $settings['db_type'] == 'oracle' )
      ? translate( 'Automatic installation not supported' )
      : translate( 'This may take several minutes to complete' ) . '
          <form action="index.php?action=install" method="post">
            <input type="'
       . ( $_SESSION['old_program_version'] == 'new_install' &&
        empty( $_SESSION['blank_database'] )
        ? 'submit" value="' . translate( 'Install Database' )
        :/* We're doing an upgrade. */ 'hidden" name="install_file" value="'
         . $_SESSION['install_file'] . '" />
            <input type="submit" value="'
         . translate( 'Update Database' ) ) . '" />
          </form>' ) . '
        </td>
      </tr>'
     . ( ! empty( $settings['db_type'] ) && $settings['db_type'] != 'sqlite'
       && $settings['db_type'] != 'sqlite3'
       && empty( $_SESSION['blank_database'] ) ? '
      <tr>
        <td class="aligncenter">
          <form action="index.php?action=install" method="post" name="display">
            <input type="hidden" name="install_file" value="'
       . $_SESSION['install_file'] . '" />
            <input type="hidden" name="display_sql" value="1" />
            <input type="submit" value="' . translate ( 'Display Required SQL' )
       . '" /><br />' . ( empty( $str_parsed_sql ) ? '' : '
            <textarea name="displayed_sql" cols="100" rows="12">'
         . $str_parsed_sql . '</textarea><br />
            <p class="recommended">'
         . translate( 'Return to previous page after processing SQL.' )
         . '</p>' ) . '
          </form>
        </td>
      </tr>' : '' );
  }

  echo '
    </table>
    <table width="90%" class="aligncenter">
      <tr>
        <td class="alignright" width="40%">
          <form action="index.php?action=switch&amp;page=2" method="post">
            <input type="submit" value="<- ' . $backStr . '" />
          </form>
        </td>
        <td class="aligncenter" width="20%">
          <form action="index.php?action=switch&amp;page=4" method="post">
            <input type="submit" value="' . $nextStr . ' ->" '
   . ( empty( $_SESSION['db_updated'] ) ? 'disabled' : '' ) . ' />
          </form>
        </td>
        <td class="alignleft" width="40%">
          <form action="" method="post">
            <input type="button" value="' . $logoutStr . '" '
   . ( empty( $_SESSION['validuser'] ) ? 'disabled' : '' )
   . ' onclick="document.location.href=\'index.php?action=logout\'" />
          </form>
        </td>
      </tr>
    </table>';
} elseif( $_SESSION['step'] == 4 ) {
  if( empty( $settings['mode'] ) )
    $settings['mode'] = 'prod';

  $mode = ( preg_match( '/dev/', $settings['mode'] )
    ? 'dev' // development
    : 'prod' ); // production
  echo '
    <table border="1" width="90%" class="aligncenter">
      <th class="pageheader" colspan="2">'
   . str_replace( 'XXX', translate( '4' ), $wizardStr ) . '</th>
      <tr>
        <td colspan="2" width="50%">'
   . translate( 'This is the final step in setting up your WebCalendar Installation.' )
   . '</td>
      </tr>'
   . ( ! empty( $_SESSION['tz_conversion'] ) && $_SESSION['tz_conversion'] != 'Y' ? '
      <th class="header" colspan="2">'
     . translate( 'Timezone Conversion' ) . '</th>
      <tr>
        <td colspan="2">' . ( $_SESSION['tz_conversion'] != 'Success' ? '
          <form action="index.php?action=tz_convert" method="post">
            <ul><li>'
       . translate( 'It appears that you have NOT converted...' ) . '</li></ul>
            <div class="aligncenter"><input type="submit" value="'
       . translate( 'Convert Data to GMT' ) . ':" /></div>
          </form>' : '
          <ul><li>' . $tzSuccessStr . '</li></ul>' ) . '
        </td>
      </tr>' : '' )/* end Timezone Conversion */ . '
      <th class="header" colspan="2">'
   . translate( 'Application Settings' ) . '</th>
      <tr>
        <td colspan="2">
          <ul><li>' . ( empty( $PHP_AUTH_USER )
    ? translate( 'HTTP-based authentication was not detected...' )
    : translate( 'HTTP-based authentication was detected...' ) )
   . '</li></ul>
        </td>
      </tr>
      <tr>
        <td>
          <table width="75%" class="aligncenter">
            <tr>
            <form action="index.php?action=switch&amp;page=4" method="post" '
   . 'enctype=\'multipart/form-data\' name="form_app_settings">
              <input type="hidden" name="app_settings" value="1" />
              <td class="prompt">' . translate( 'Create Default Admin Account' )
   . ':</td>
              <td>
                <input type="checkbox" name="load_admin" value="Yes"'
   . ( ( $_SESSION['old_program_version'] == 'new_install' )
    ? $checked : '' ) . ' />' . ( $_SESSION['admin_exists'] == 0 ? '
                <span class="notrecommended"> '
    . translate( '(Admin Account Not Found)' ) . '</span>' : '' ) . '
              </td>
            </tr>
            <tr>
              <td class="prompt">' . translate( 'Application Name' ) . ':</td>
              <td><input type="text" size="40" name="form_application_name" '
   . 'id="form_application_name" value="' . $_SESSION['application_name']
   . '" /></td>
            </tr>
            <tr>
              <td class="prompt">' . translate( 'Server URL' ) . ':</td>
              <td><input type="text" size="40" name="form_server_url" '
   . 'id="form_server_url" value="' . $_SESSION['server_url'] . '" /></td>
            </tr>
            <tr>
              <td class="prompt">'
   . translate( 'User Authentication' ) . ':</td>
              <td>
                <select name="form_user_inc" onChange="auth_handler()">
                  <option value="user.php"'
   . ( $settings['user_inc'] == 'user.php' && $settings['use_http_auth'] != 'true'
    ? $selected : '' ) . '>'
   . translate( 'Web-based via WebCalendar (default)' )
   . '</option>
                  <option value="http"'
   . ( $settings['user_inc'] == 'user.php' && $settings['use_http_auth'] == 'true'
    ? $selected : '' ) . '>'
  . ( empty( $PHP_AUTH_USER ) ? translate( 'Web Server (not detected)' )
    : translate( 'Web Server (detected)' ) ) . '</option>'
   . ( function_exists( 'ldap_connect' ) ? '
                  <option value="user-ldap.php"'
     . ( $settings['user_inc'] == 'user-ldap.php' ? $selected : '' )
     . '>LDAP</option>' : '' ) . ( function_exists( 'yp_match' ) ? '
                  <option value="user-nis.php"'
     . ( $settings['user_inc'] == 'user-nis.php' ? $selected : '' )
     . '>NIS</option>' : '' ) . '
                  <option value="user-imap.php"'
   . ( $settings['user_inc'] == 'user-imap.php' ? $selected : '' )
   . '>IMAP</option>
                  <option value="none" '
   . ( $settings['user_inc'] == 'user.php' && $settings['single_user'] == 'true'
    ? $selected : '' ) . '>' . translate( 'None (Single-User)' ) . '</option>
                </select>
              </td>
            </tr>
            <tr id="singleuser">
              <td class="prompt">&nbsp;&nbsp;&nbsp;' . $singleUserStr . ' '
   . $loginStr . ':</td>
              <td><input name="form_single_user_login" size="20" value="'
   . ( empty( $settings['single_user_login'] )
     ? '' : $settings['single_user_login'] ) . '" /></td>
            </tr>
            <tr>
              <td class="prompt">' . translate( 'Read-Only' ) . ':</td>
              <td>
                <input name="form_readonly" value="true" type="radio"'
   . ( $settings['readonly'] == 'true' ? $checked : '' ) . ' />'
   . $yesStr . '&nbsp;&nbsp;&nbsp;&nbsp;
                <input name="form_readonly" value="false" type="radio"'
   . ( $settings['readonly'] != 'true' ? $checked : '' ) . ' />' . $noStr . '
              </td>
            </tr>
            <tr>
              <td class="prompt">' . translate( 'Environment' ) . ':</td>
              <td>
                <select name="form_mode">
                  <option value="prod"' . ( $mode == 'prod' ? $selected : '' )
   . '>' . translate( 'Production' ) . '</option>
                  <option value="dev"' . ( $mode == 'dev' ? $selected : '' )
   . '>' . translate( 'Development' ) . '</option>
                </select>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <table width="80%" class="aligncenter">
      <tr>
        <td class="aligncenter">'
   . ( ! empty( $_SESSION['db_success'] ) && $_SESSION['db_success'] &&
    empty( $dologin ) ? '
              <input name="action" type="button" value="'
     . translate( 'Save Settings' ) . '" onClick="return validate();" />'
     . ( ! empty( $_SESSION['old_program_version'] ) &&
      ( $_SESSION['old_program_version'] == $PROGRAM_VERSION ) && !
      empty( $setup_complete ) ? '
              <input type="button" name="action2" value="'
       . translate( 'Launch WebCalendar' )
       . '" onClick="window.open( \'../index.php\', \'webcalendar\' );" />'
      : '' ) : '' ) . ( ! empty( $_SESSION['validuser'] ) ? '
              <input type="button" value="' . $logoutStr
     . '" onclick="document.location.href=\'index.php?action=logout\'" />'
    : '' ) . '
            </form>
        </td>
      </tr>
    </table>';
}

?>
  </body>
</html>
