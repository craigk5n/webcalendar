<?php
/*
 * $Id$
 *
 * Page Description:
 * Main page for install/config of db settings.
 * This page is used to create/update includes/settings.php.
 *
 * NEW RELEASE UPDATE PROCEDURES:
 *   - Update _WEBCAL_PROGRAM_VERSION default value in default_config.php
 *     This should be of the format "v1.0.0"
 *   - Make sure the last entry in all the upgrade-*.sql files reference
 *     this same version.  For example, for "v1.0.0", there should be a
 *     comment of the format:
         /*upgrade_v1.0.0*/
/*     ( Don't remove leading / as it leads to nested C-Style comments )
 *     If there are NO db changes, then you should just modify the
 *     the last comment to be the new version number.  If there are
 *     db changes, you should create a new entry in the *.sql files
 *     that detail the SQL to upgrade.
 *   - Update the _WEBCAL_PROGRAM_VERSION and $PROGRAM_DATA variables defined
 *     in includes/config.php.  The $PROGRAM_VERSION needs to be the
 *     same value (e.g. "v1.0.0") that was defined above.
 *   - Update the version/date in ChangeLog and NEWS files.
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
 * Security:
 * The first time this page is accessed, there are no security
 * precautions.   The user is prompted to generate a config password.
 * From then on, users must know this password to make any changes
 * to the settings in settings.php./
 *
 * TODO:
 *
 */
$show_all_errors =  false;
define ( '_ISVALID', 1 );
define ( '_WC_BASE_DIR', '..' );
define ( '_WC_INCLUDE_DIR', _WC_BASE_DIR . '/includes/' );
define ( 'CHECKED', ' checked="checked" ' );
define ( 'SELECTED', ' selected="selected"' );
define ( '_WC_RUN_MODE', 'prod' );
define ( '_WC_phpdbiVerbose', $show_all_errors );

include_once 'install_functions.php';
include_once _WC_INCLUDE_DIR . 'translate.php';
include_once _WC_INCLUDE_DIR . 'dbi4php.php';
include_once _WC_INCLUDE_DIR . 'config.php';
include_once _WC_INCLUDE_DIR . 'formvars.php';
include_once './default_config.php';
include_once './install_functions.php';

$file = _WC_INCLUDE_DIR . 'settings.php';

require ( _WC_INCLUDE_DIR . 'classes/smarty/libs/Smarty.class.php' );
$smarty = new Smarty();
$smarty->template_dir = './';
$smarty->compile_dir  = './';
$smarty->config_dir   = _WC_INCLUDE_DIR . 'smarty';
$smarty->plugins_dir  =  array( _WC_INCLUDE_DIR . 'smarty', 'plugins');
$smarty->register_prefilter('template_translate');

//change this path if needed
$firebird_path = 'c&#58;/program files/firebird/firebird_1_5/examples/employee.fdb';

clearstatcache();

// We may need time to run extensive database loads
if  ( ! get_php_setting ( 'safe_mode' ) )
  set_time_limit ( 240 );

// If we're using SQLLite, it seems that magic_quotes_sybase must be on
//ini_set('magic_quotes_sybase', 'On');


// Check for proper auth settings
if ( ! empty (  $_SERVER['PHP_AUTH_USER'] ) )
  $PHP_AUTH_USER= $_SERVER['PHP_AUTH_USER'];

//We'll always use browser defined languages
reset_language ( 'none' );
session_start ();

//Create settings.php file is it doesn't exist and set default values
if ( ! @file_exists ( $file ) )
  writeSettings ( $file );

//Load values from settings.php
readSettings ( $file );

// File exists, but no password.  Force them to create a password.
$forcePassword = ( ! doSettings ( 'install_password' ) ? true : false );

// If password already exists, check for valid session.
$doLogin = ( ! $forcePassword && ( empty ( $_SESSION['validuser'] ) ||
  $_SESSION['validuser'] != doSettings ( 'install_password' ) ) );


//Set install_password
$pwd1 = getPostValue ( 'password1' );
$pwd2 = getPostValue ( 'password2' );

if ( @file_exists ( $file ) && $forcePassword && ! empty ( $pwd1 ) ) {
  if ( $pwd1 != $pwd2 ) {
    writeAlert ( translate ( 'Passwords do not match', true ), true );
    exit;
  }	
	$md5pwd1 = md5 ( $pwd1 );
  doSettings ( 'install_password', $md5pwd1 , true );
	doSettings ( 'SaveAll', $file );
  $_SESSION['validuser'] = $md5pwd1;
	writeAlert ( translate ( 'Password has been set', true ) );
	exit;

}

//Normal log in
$pwd = getPostValue ( 'password3' );
if ( @file_exists ( $file ) && ! empty ( $pwd ) ) {
  if ( md5($pwd) == doSettings ( 'install_password' ) ) {
    $_SESSION['validuser'] = doSettings ( 'install_password' );
    writeAlert ( translate ( 'Successful Login', true ) );
		exit;
  } else {
    // Invalid password
    $_SESSION['validuser'] = '';
    writeAlert ( translate ( 'Invalid Login', true ), true );
		exit;
  }
}

//reset all passwords
$pwd = $pwd1 = $pwd2 = $md5pwd1 = '';

//reload settings
readSettings ( $file );
	
// Set default Application Name
if ( ! isset ( $_SESSION['APPLICATION_NAME'] ) ) {
  $_SESSION['APPLICATION_NAME'] = 'WebCalendar';
}

// Set Server URL
if ( ! isset ( $_SESSION['SERVER_URL'] ) ) {
    if ( ! empty ( $_SERVER['HTTP_HOST'] ) && ! empty ( $_SERVER['REQUEST_URI'] ) ) {
      $ptr = strpos ( $_SERVER['REQUEST_URI'], '/install', 2 );
      if ( $ptr > 0 ) {
        $uri = substr ( $_SERVER['REQUEST_URI'], 0, $ptr + 1 );
        $SERVER_URL = "http://" . $_SERVER['HTTP_HOST'];
        if ( ! empty ( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != 80 )
          $SERVER_URL .= ': ' . $_SERVER['SERVER_PORT'];
        $SERVER_URL .= $uri;
        $_SESSION['SERVER_URL'] = $SERVER_URL;
      }
    }
}
// Set PUBLIC_CACHE
if ( ! isset ( $_SESSION['PUBLIC_CACHE'] ) ) {
  $_SESSION['PUBLIC_CACHE'] = '/cache';
}

// Set PUBLIC_CACHE
if ( ! isset ( $_SESSION['DB_CACHE'] ) ) {
  $_SESSION['DB_CACHE'] = '/tmp';
}

$get_action = getGetValue ( 'action' );
$post_action = getPostValue ( 'action' );
$post_action2 = getPostValue ( 'action2' );

// Handle "Logout" button
if ( $get_action == 'logout' ) {
  session_destroy ();
  Header ( 'Location: index.php' );
}


// We were set here because of a mismatch of _WEBCAL_PROGRAM_VERSION
// A simple way to ensure that UPGRADING.html gets read and processed
if ( $get_action == 'mismatch' ) {
  $version = getGetValue ( 'version' );
 $_SESSION['old_program_version'] = $version;
}

// Go to the proper page
$page = getIntValue ( 'page' );

if ( empty ( $page ) )
  $page = 1;
	
switch ( $page ){
   case 2:
     if ( ! empty ( $_SESSION['validuser'] ) ){
    $onload = 'db_type_handler();';
    }
   break;
  case 3:
     if ( ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_success'] ) ){
    }
   break;
  case 4:
     if ( ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_success'] )  &&
      empty ( $_SESSION['db_create'] ) ){
    $onload = 'auth_handler();';
    }
   break;
  default:
    //

}



// We're doing a database installation yea ha!
if ( $get_action == 'install' ){
    // We'll grab database settings from settings.php
    $db_persistent = false;
    $db_prefix = doSettings ( 'db_prefix' );
    $db_type = doSettings ( 'db_type' );
    $db_host = doSettings ( 'db_host' );
    $db_database = doSettings ( 'db_database' );
    $db_login = doSettings ( 'db_login' );
    $db_password = doSettings ( 'db_password' );

  if ( ! defined ( '_WC_DB_TYPE' ) )
    define ( '_WC_DB_TYPE', $db_type );
  if ( ! defined ( '_WC_DB_PERSISTENT' ) )
    define ( '_WC_DB_PERSISTENT', $db_persistent );
  if ( ! defined ( '_WC_DB_PREFIX' ) )
    define ( '_WC_DB_PREFIX', $db_prefix );
    // We might be displaying sql only
  $display_sql = getPostValue('display_sql');

    $c = dbi_connect ( $db_host, $db_login,
      $db_password, $db_database, false );
  // It's possible that the tables were created manually
  // and we just want to do the database population routines
  if ( $c ) {
    $sess_install = $_SESSION['install_file'];
    $install_filename = ( $sess_install == 'tables' ? 'tables':'upgrade');

    $dbType = ( $db_type == 'odbc' ? $_SESSION['odbc_db'] : $db_type );
    $dbPrefix  = $db_prefix;
    include_once 'sql/sql_array.php';
    foreach ( $sql_array as $sql ) {
      dbi_execute ( $sql, array (), false, $show_all_errors );
    }
  }
  if ( empty ( $display_sql ) ){
   // Update the version info
   get_installed_version( true );

   $_SESSION['blank_database'] = '';
  } //end if $display_sql

} //end database installation

//Set the value of the underlying database for ODBC connections
if ( $get_action == 'set_odbc_db' ){
 $_SESSION['odbc_db'] = getPostValue( 'odbc_db' );
}


// Is this a db connection test?
// If so, just test the connection, show the result and exit.
if (  ! empty ( $post_action ) && $post_action == $testSettingsStr  &&
  ! empty ( $_SESSION['validuser'] )  ) {
    $response_msg = '';
    $response_msg2 = '';
    $_SESSION['db_success'] = false;
    $db_persistent = getPostValue ( 'db_persistent' );
    $db_prefix = getPostValue ( 'form_db_prefix' );
    $db_type = getPostValue ( 'form_db_type' );
    $db_host = getPostValue ( 'form_db_host' );
    $db_database = getPostValue ( 'form_db_database' );
    $db_login = getPostValue ( 'form_db_login' );
    $db_password = getPostValue ( 'form_db_password' );
    $db_cachedir = getPostValue ( 'form_db_cachedir' );

  if ( $db_password == 'none' )
     $db_password ='';
  if ( ! defined ( '_WC_DB_TYPE' ) )
    define ( '_WC_DB_TYPE', $db_type );
  if ( ! defined ( '_WC_DB_PERSISTENT' ) )
    define ( '_WC_DB_PERSISTENT', $db_persistent );
  if ( ! defined ( '_WC_DB_PREFIX' ) )
    define ( '_WC_DB_PREFIX', $db_prefix );
    //Allow  field length to change if needed
   $onload = 'db_type_handler();';

   //disable warnings
   show_errors ();
   $c = dbi_connect ( $db_host, $db_login,
     $db_password, $db_database, false );

    //enable warnings
   show_errors ( true);

   if ( $c ) {
      $_SESSION['db_success'] = true;

      // Do some queries to try to determine the previous version
      get_installed_version();

      $response_msg = '<b>' .translate ( 'Connection Successful' ) . '</b> ' .
      translate ( 'Please go to next page to continue installation' ) . '.';

    } else {
      $response_msg =  $failure . dbi_error () . "</blockquote>\n";
     // See if user is valid, but database doesn't exist
     // The normal call to dbi_connect simply return false for both conditions
     if ( $db_type == 'mysql'  ) {
       $c = mysql_connect ( $db_host, $db_login, $db_password );
     } else if ( $db_type == 'mysqli' ) {
      $c = dbi_connect ( $db_host, $db_login, $db_password, '' );
     } else if ( $db_type == 'mssql'  ) {
       $c = mssql_connect ( $db_host, $db_login, $db_password );
     } else if ( $db_type == 'pgsql'  ) {
       $c = dbi_connect ( $db_host, $db_login, $db_password , 'template1', false);
     } else if ( $db_type == 'ibase'  ) {
      //TODO figure out how to remove this hardcoded link
       $c = dbi_connect ( $db_host, $db_login, $db_password , $firebird_path, false);
     } //TODO Code remaining database types
     if ( $c ) { // credentials are valid, but database doesn't exist
        $response_msg = translate ( 'Correct your entries or click Create...' );
       $_SESSION['db_noexist'] = true;
     } else {
       if ( $db_type == 'ibase'  ) {
         $response_msg = $failure . $manualStr . "</blockquote>\n";
       } else {
         $response_msg = $failure . dbi_error () . "</blockquote>\n" .
           translate ( 'Correct your entries and try again' );
       }
     }
  } //end if ($c)

  //test db_cachedir directory for write permissions
  if ( strlen ( $db_cachedir ) > 0 ) {
    if ( ! @file_exists ( $db_cachedir ) ) {
      $response_msg2 = $failureStr . $cachedirStr . ' ' .
        translate ( 'does not exist' );
    } else if ( ! @is_writable ( $db_cachedir ) ) {
      $response_msg2 = $failureStr . $cachedirStr . ' ' .
        translate ( 'is not writable' );
      } else {
    }
  }

// Is this a db create?
// If so, just test the connection, show the result and exit.
} else if ( ! empty ( $post_action2 ) && $post_action2== $createNewStr  &&
  ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_noexist'] )) {
    $_SESSION['db_success'] = false;

    $db_persistent = false;
  $db_prefix = getPostValue ( 'form_db_prefix' );
    $db_type = getPostValue ( 'form_db_type' );
    $db_host = getPostValue ( 'form_db_host' );
    $db_database = getPostValue ( 'form_db_database' );
    $db_login = getPostValue ( 'form_db_login' );
    $db_password = getPostValue ( 'form_db_password' );
    $db_cachedir = getPostValue ( 'form_db_cachedir' );

  if ( ! defined ( '_WC_DB_TYPE' ) )
    define ( '_WC_DB_TYPE', $db_type );
  if ( ! defined ( '_WC_DB_PERSISTENT' ) )
    define ( '_WC_DB_PERSISTENT', $db_persistent );
  if ( ! defined ( '_WC_DB_PREFIX' ) )
    define ( '_WC_DB_PREFIX', $db_prefix );
    //Allow ODBC field to be visible if needed
   $onload = 'db_type_handler();';

  $sql = 'CREATE DATABASE ' . $db_database;

    // We don't use the normal dbi_execute because we need to know
  // the difference between no conection and no database
  if ( $db_type == 'mysql' ) {
      $c = dbi_connect ( $db_host, $db_login, $db_password, 'mysql', false );
      if ( $c ) {
     dbi_execute ( $sql . ';' , array(), false, $show_all_errors);
    if ( ! @mysql_select_db ( $db_database ) ) {
      $response_msg = $failure . dbi_error () . "</blockquote>\n";
    } else {
      $_SESSION['db_noexist'] = false;
      $_SESSION['old_program_version'] = 'new_install';
    }
    } else {
      $response_msg = $failure . dbi_error () . "</blockquote>\n";

   }
  } else if ( $db_type == 'mysqli' ) {
    $c = dbi_connect ( $db_host, $db_login, $db_password, '', false );
    if ( $c ) {
      dbi_execute ( $sql . ';', array (), false, $show_all_errors );
      if ( ! $c->select_db($db_database ) )
        $response_msg = $failure . dbi_error () . '</blockquote>' . "\n";
      else {
        $_SESSION['db_noexist'] = false;
        $_SESSION['old_program_version'] = 'new_install';
      }
    } else
      $response_msg = $failure . dbi_error () . '</blockquote>' . "\n";

  } else if ( $db_type == 'mssql' ) {
      $c = dbi_connect ( $db_host, $db_login, $db_password , 'master', false);
      if ( $c ) {
     dbi_execute (  $sql , array(), false, $show_all_errors);
    if ( ! @mssql_select_db ( $db_database ) ) {
      $response_msg = $failure . dbi_error () . "</blockquote>\n";

    } else {
      $_SESSION['db_noexist'] = false;
      $_SESSION['old_program_version'] = 'new_install';
    }
     } else {
      $response_msg = $failure . dbi_error () . "</blockquote>\n";

   }
  } else if ( $db_type == 'pgsql' ) {
   $c = dbi_connect ( $db_host, $db_login, $db_password , 'template1', false);
      if ( $c ) {
     dbi_execute (  $sql , array(), false, $show_all_errors);
     $_SESSION['db_noexist'] = false;
    } else {
      $response_msg = $failure . dbi_error () . "</blockquote>\n";

   }
  } else if ( $db_type == 'ibase' ) {

      $response_msg = $failure . $manualStr . "</blockquote>\n";

  } // TODO code remainig database types
  //allow bypass of TZ Conversion
  $_SESSION['tz_conversion'] = 'Y';
}

// Is this a call to phpinfo()?
if ( $get_action == 'phpinfo' ) {
  if ( ! empty ( $_SESSION['validuser'] ) ) {
    phpinfo();
  } else {
    etranslate ( 'You are not authorized' ) . '.';
  }
}



// If we are handling a form POST, then take that data and put it in settings
// array.
$x = getPostValue ( 'form_db_type' );
if ( ! empty ( $x ) ) {
  doSettings ( 'db_prefix', getPostValue ( 'form_db_prefix' ), true );
  doSettings ( 'db_type', getPostValue ( 'form_db_type' ), true );
  doSettings ( 'db_host', getPostValue ( 'form_db_host' ), true );
  doSettings ( 'db_database', getPostValue ( 'form_db_database' ), true );
  doSettings ( 'db_login', getPostValue ( 'form_db_login' ), true );
  doSettings ( 'db_password', getPostValue ( 'form_db_password' ), true );
  doSettings ( 'db_persistent', getPostValue ( 'form_db_persistent' ), true );
  doSettings ( 'db_cachedir', getPostValue ( 'form_db_cachedir' ), true );
}
$y = getPostValue ( 'app_settings' );
if ( ! empty ( $y ) ) {
  $incval =  getPostValue ( 'form_user_inc' );
  doSettings ( 'single_user_login', getPostValue ( 'form_single_user_login' ), true );
  doSettings ( 'readonly', getPostValue ( 'form_readonly' ), true );
  doSettings ( 'mode', getPostValue ( 'form_mode' ), true );
  doSettings ( 'use_http_auth', ( $incval == 'http' ? 'true' : 'false' ), true );
  doSettings ( 'single_user', ( $incval == 'none' ? 'true' : 'false' ), true );
  doSettings ( 'user_inc', ( $incval == 'none' || $incval == 'http'
    ? 'User' : $incval ), true );
  doSettings ( 'imap_server', getPostValue ( 'form_imap_server' ), true );
  doSettings ( 'user_app_path', getPostValue ( 'form_user_app_path' ), true );

 //Save Application Name and Server URL
 $db_persistent = false;
 $db_prefix = doSettings ( 'db_prefix' );
 $db_type = doSettings ( 'db_type' );
 if ( ! defined ( '_WC_DB_TYPE' ) )
   define ( '_WC_DB_TYPE', $db_type );
 if ( ! defined ( '_WC_DB_PERSISTENT' ) )
   define ( '_WC_DB_PERSISTENT', $db_persistent );
 if ( ! defined ( '_WC_DB_PREFIX' ) )
   define ( '_WC_DB_PREFIX', $db_prefix );

 $_SESSION['APPLICATION_NAME']  = getPostValue ( 'form_APPLICATION_NAME' );
 $_SESSION['SERVER_URL']  = getPostValue ( 'form_server_url' );
  $c = dbi_connect ( doSettings ( 'db_host' ), doSettings ( 'db_login' ),
    doSettings ( 'db_password' ), doSettings ( 'db_database' ), false );
 if ( $c ) {
   if ( isset ( $_SESSION['APPLICATION_NAME'] ) ) {
    dbi_execute ("DELETE FROM webcal_config WHERE cal_setting = 'APPLICATION_NAME'");
    dbi_execute ("INSERT INTO webcal_config ( cal_setting, cal_value ) " .
          "VALUES ('APPLICATION_NAME', ?)" , array ( $_SESSION['APPLICATION_NAME'] ) );
  }
   if ( isset ( $_SESSION['SERVER_URL'] ) ) {
    dbi_execute ("DELETE FROM webcal_config WHERE cal_setting = 'SERVER_URL'");
    dbi_execute ("INSERT INTO webcal_config ( cal_setting, cal_value ) " .
          "VALUES ('SERVER_URL', ?)" , array ( $_SESSION['SERVER_URL'] ) );
  }
 }
 $do_load_admin = getPostValue ( 'load_admin' );
 if ( ! empty ( $do_load_admin ) ) {
  //add default admin user if not exists
  db_load_admin ();
  //check if an Admin account exists
  $_SESSION['admin_exists'] = db_check_admin ();
 }
 $setup_complete = true;
}
  // Save settings to file now.
if ( ! empty ( $x ) || ! empty ( $y ) ){
 doSettings ( 'AllSave', $file );
}

//Can't include installConfig until all variables are set up
include_once './install_config.php';

//parse installConfig
$cnt = $fld = 1;

$progress =  100/(count ( $installConfig ) - $page + 1 ); 

//Password Check if not logged in
if ( $doLogin ){
  $progress = 1;
  $installConfig =  array ( array (
    'title'=>translate ('Log In' ),
    'formname'=>'install_password',
    'text'=>translate( 'install password text...' ),
    'password3'=>array (
      'text'=>translate( 'Enter Installation Password' ),
      'type'=>'password',
      'value'=>'',
			'size'=>25)
  ) );
}
//Password Create ifneeded
if ( $forcePassword ) {
  $progress = 1;
  $installConfig =   array( array (
    'title'=>translate ('Installation Password' ),
    'formname'=>'install_password',
    'text'=>translate( 'install password text...' ),
    'password1'=>array (
      'text'=>translate( 'Enter Installation Password' ),
      'type'=>'password',
      'value'=>'',
			'size'=>25),
    'password2'=>array (
      'type'=>'password',
      'text'=>translate( 'Enter Installation Password Again' ),
      'value'=>'',
      'size'=>25)
  ) );
}

//print_r ( $installConfig );		
while ( list ( $key, $value ) = each ( $installConfig ) ) {
  foreach($value as $k => $v ){
    //echo $cnt . ' ' . $page . ' ' .$k . ' ' . $v . '<br>';
    if ( $k == 'title') {
      $menu[$cnt-1]['title'] = $v;
    }
    if ( $cnt == $page ) {
      if ( is_array ( $v ) ) {
        $fields[$k] = $v;
      } else {
        $main[$k] = $v;
      }
      $menu[$cnt-1]['active'] = true;
    }
  }
  $cnt++;
}

$smarty->assign ( 'page', $page );
$smarty->assign ( 'menu', $menu );
$smarty->assign ( 'progress', $progress );
$smarty->assign ( 'main', $main );
$smarty->assign ( 'fields', $fields );
$smarty->display ( 'install.tpl');
?>


