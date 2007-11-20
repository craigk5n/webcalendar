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
 *   - Update the $PROGRAM_VERSION and $PROGRAM_DATA variables defined
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
$show_all_errors =  true;
define ( '_ISVALID', 1 ); 
define ( '_WC_BASE_DIR', '..' );
define ( '_WC_INCLUDE_DIR', _WC_BASE_DIR . '/includes/' );
define ( 'CHECKED', ' checked="checked" ' );
define ( 'SELECTED', ' selected="selected"' );

define ( '_WC_phpdbiVerbose', $show_all_errors );

include_once _WC_INCLUDE_DIR . 'translate.php';
include_once _WC_INCLUDE_DIR . 'dbi4php.php';
include_once _WC_INCLUDE_DIR . 'config.php';
include_once _WC_INCLUDE_DIR . 'formvars.php';
include_once './default_config.php';
include_once './install_functions.php';
$file = _WC_INCLUDE_DIR . 'settings.php';
$fileDir = _WC_INCLUDE_DIR;

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

//Some common translations used in the install script
$wizardStr = translate ( 'WebCalendar Installation Wizard' ) .':' . translate ( 'Step' );
$passwordStr = translate ( 'Password' );
$singleUserStr = translate ( 'Single-User' );
$loginStr = translate ( 'Login' );
$failureStr = '<b>' . translate ( 'Failure Reason' ) . ':</b>';
$manualStr = translate ( 'You must manually create database' );
$cachedirStr = translate ( 'Database Cache Directory' );
$logoutStr = translate ( 'Logout' );
$testSettingsStr = translate ( 'Test Settings' );
$createNewStr = translate ( 'Create New' );
$datebasePrefixStr = translate ( 'Database Prefix' );
$datebaseNameStr = translate ( 'Database Name' );
$backStr = translate ( 'Back' );
$nextStr = translate ( 'Next' );
$tzSuccessStr = translate ( 'Timezone Conversion Successful' );

$failure = $failureStr . '<blockquote>';

// First pass at settings.php.
// We need to read it first in order to get the md5 password.
$magic = @get_magic_quotes_runtime();
@set_magic_quotes_runtime(0);    
$fd = @fopen ( $file, 'rb', true );
$settings = array ();
$password = '';
$forcePassword = false;
if ( ! empty ( $fd ) ) {
  while ( ! feof ( $fd ) ) {
    $buffer = fgets ( $fd, 4096 );
    $buffer = trim ( $buffer, "\r\n " );
    if ( preg_match ( "/^(\S+):\s*(.*)/", $buffer,  $matches ) ) {
      if ( $matches[1] == 'install_password' ) {
        $password = $matches[2];
        $settings['install_password'] = $password;
      }
    }
  }
  fclose ( $fd );
  // File exists, but no password.  Force them to create a password.
  if ( empty ( $password ) ) {
    $forcePassword = true;
  }
}
@set_magic_quotes_runtime($magic);

session_start ();

// Set default Application Name
if ( ! isset ( $_SESSION['application_name'] ) ) {
  $_SESSION['application_name'] = 'WebCalendar';
}

// Set Server URL
if ( ! isset ( $_SESSION['server_url'] ) ) {
    if ( ! empty ( $_SERVER['HTTP_HOST'] ) && ! empty ( $_SERVER['REQUEST_URI'] ) ) {
      $ptr = strpos ( $_SERVER['REQUEST_URI'], '/install', 2 );
      if ( $ptr > 0 ) {
        $uri = substr ( $_SERVER['REQUEST_URI'], 0, $ptr + 1 );
        $SERVER_URL = "http://" . $_SERVER['HTTP_HOST'];
        if ( ! empty ( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != 80 )
          $SERVER_URL .= ': ' . $_SERVER['SERVER_PORT'];
        $SERVER_URL .= $uri;
        $_SESSION['server_url'] = $SERVER_URL;
      }
    }
}

$get_action = getGetValue ( 'action' );
$post_action = getPostValue ( 'action' );
$post_action2 = getPostValue ( 'action2' );

// Handle "Logout" button
if ( $get_action == 'logout' ) {
  session_destroy ();
  Header ( 'Location: index.php' );
  exit;
}

// If password already exists, check for valid session.
$doLogin = ( @file_exists ( $file ) && ! empty ( $password ) &&
  ( empty ( $_SESSION['validuser'] ) ||
  $_SESSION['validuser'] != $password ) );

$pwd = getPostValue ( 'password3' );
if ( @file_exists ( $file ) && ! empty ( $pwd ) ) {
  if ( md5($pwd) == $password ) {
    $_SESSION['validuser'] = $password;
    writeAlert ( translate ( 'Successful Login', true ) );
    exit;
  } else {
    // Invalid password
    $_SESSION['validuser'] = '';
    writeAlert ( translate ( 'Invalid Login', true ), true );
    exit;
  }
}

$pwd1 = getPostValue ( 'password1' );
$pwd2 = getPostValue ( 'password2' );
if ( @file_exists ( $file ) && $forcePassword && ! empty ( $pwd1 ) ) {
  if ( $pwd1 != $pwd2 ) {
    writeAlert ( translate ( 'Passwords do not match', true ), true );
    exit;
  }
  $fd = @fopen ( $file, 'a+b', false );
  if ( empty ( $fd ) ) {
    writeAlert ( translate ( 'Unable to write password to settings.php file', true ) );
    exit;
  }
  
  fwrite ( $fd, "<?php\r\n" );
  fwrite ( $fd, 'install_password: ' . md5($pwd1) . "\r\n" );
  fwrite ( $fd, "?>\r\n" );
  fclose ( $fd );  
  writeAlert ( translate ( 'Password has been set', true ) );
  $_SESSION['validuser'] = $pwd1;
  exit;
}

$magic = @get_magic_quotes_runtime();
@set_magic_quotes_runtime(0);
$fd = @fopen ( $file, 'rb', false );
if ( ! empty ( $fd ) ) {
  while ( ! feof ( $fd ) ) {
    $buffer = fgets ( $fd, 4096 );
    $buffer = trim ( $buffer, "\r\n " );
    if ( preg_match ( "/^#|\/\*/", $buffer ) )
      continue;
    if ( preg_match ( "/^<\?/", $buffer ) ) // start php code
      continue;
    if ( preg_match ( "/^\?>/", $buffer ) ) // end php code
      continue;
    if ( preg_match ( "/(\S+):\s*(.*)/", $buffer, $matches ) ) {
      $settings[$matches[1]] = $matches[2];
    }
  }
  fclose ( $fd );
}
@set_magic_quotes_runtime($magic);


// We were set here because of a mismatch of PROGRAM_VERSION
// A simple way to ensure that UPGRADING.html gets read and processed
if ( $get_action == 'mismatch' ) {
  $version = getGetValue ( 'version' );
 $_SESSION['old_program_version'] = $version;
}

// Go to the proper page
if ( empty ( $_SESSION['step'] ) )
  $_SESSION['step'] = 1;
if ( $get_action == 'switch' ) {
  $page = getGetValue ( 'page' );
 switch ( $page ){
 
   case 2:
     if ( ! empty ( $_SESSION['validuser'] ) ){  
       $_SESSION['step'] = $page;
    $onload = 'db_type_handler();';
    }
   break;
  case 3:
     if ( ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_success'] ) ){  
       $_SESSION['step'] = $page;
    }
   break;
  case 4:
     if ( ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_success'] )  &&
      empty ( $_SESSION['db_create'] ) ){  
       $_SESSION['step'] = $page;
    $onload = 'auth_handler();';
    }
   break;
  default:
     $_SESSION['step'] = 1;
 }
}



// We're doing a database installation yea ha!
if ( $get_action == 'install' ){
    // We'll grab database settings from settings.php
    $db_persistent = false;
    $db_prefix = $settings['db_prefix'];
    $db_type = $settings['db_type'];
    $db_host = $settings['db_host'];
    $db_database = $settings['db_database'];
    $db_login = $settings['db_login'];
    $db_password = $settings['db_password'];

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
  exit;
}


$exists = false;
$exists = @file_exists ( $file );
$canWrite = false;
if ( $exists ) {
  $canWrite = is_writable ( $file );
} else {
  // check to see if we can create the settings file.
  $testFd = @fopen ( $file, 'w+b', false );
  if ( @file_exists ( $file ) ) {
    $canWrite = true;
    $exists  = true;
    $forcePassword = true;
  }
  @fclose ( $testFd ); 
}

// If we are handling a form POST, then take that data and put it in settings
// array.
$x = getPostValue ( 'form_db_type' );
if ( empty ( $x ) ) {
  // No form was posted.  Set defaults if none set yet.
  if ( ! @file_exists ( $file ) || count ( $settings ) == 1) {
    $settings['db_prefix'] = 'webcal_';
    $settings['db_type'] = 'mysql';
    $settings['db_host'] = 'localhost';
    $settings['db_database'] = 'intranet';
    $settings['db_login'] = 'root';
    $settings['db_password'] = 'none';
    $settings['db_persistent'] = 'false';
    $settings['db_cachedir'] =( file_exists ( '/tmp' ) && is_writable ( '/tmp' ) 
      ? '/tmp' : '' );
    $settings['readonly'] = 'false';
    $settings['user_inc'] = 'User';
    $settings['install_password'] = '';
    $settings['single_user_login'] = '';
    $settings['use_http_auth'] = 'false';
    $settings['single_user'] = 'false';
  }
} else {
  $settings['db_prefix'] = getPostValue ( 'form_db_prefix' );
  $settings['db_type'] = getPostValue ( 'form_db_type' );
  $settings['db_host'] = getPostValue ( 'form_db_host' );
  $settings['db_database'] = getPostValue ( 'form_db_database' );
  $settings['db_login'] = getPostValue ( 'form_db_login' );
  $settings['db_password'] = getPostValue ( 'form_db_password' );
  $settings['db_persistent'] = getPostValue ( 'form_db_persistent' );
  $settings['db_cachedir'] = getPostValue ( 'form_db_cachedir' );
}
$y = getPostValue ( 'app_settings' );
if ( ! empty ( $y ) ) {
  $incval =  getPostValue ( 'form_user_inc' );
  $settings['single_user_login'] = getPostValue ( 'form_single_user_login' );
  $settings['readonly'] = getPostValue ( 'form_readonly' );
  $settings['mode'] = getPostValue ( 'form_mode' );
  $settings['use_http_auth'] = ( $incval == 'http' ? 'true' : 'false' );
  $settings['single_user'] = ( $incval == 'none' ? 'true' : 'false' );
  $settings['user_inc'] = ( $incval == 'none' || $incval == 'http'
    ? 'User' : $incval );
  $settings['imap_server'] = getPostValue ( 'form_imap_server' );  
  $settings['user_app_path'] = getPostValue ( 'form_user_app_path' );  

 //Save Application Name and Server URL
 $db_persistent = false;
 $db_prefix = $settings['db_prefix'];
 $db_type = $settings['db_type'];
 if ( ! defined ( '_WC_DB_TYPE' ) )
   define ( '_WC_DB_TYPE', $db_type );
 if ( ! defined ( '_WC_DB_PERSISTENT' ) )
   define ( '_WC_DB_PERSISTENT', $db_persistent );
 if ( ! defined ( '_WC_DB_PREFIX' ) )
   define ( '_WC_DB_PREFIX', $db_prefix );
   
 $_SESSION['application_name']  = getPostValue ( 'form_application_name' );
 $_SESSION['server_url']  = getPostValue ( 'form_server_url' );
  $c = dbi_connect ( $settings['db_host'], $settings['db_login'],
    $settings['db_password'], $settings['db_database'], false );
 if ( $c ) {
   if ( isset ( $_SESSION['application_name'] ) ) {
    dbi_execute ("DELETE FROM webcal_config WHERE cal_setting = 'APPLICATION_NAME'");
    dbi_execute ("INSERT INTO webcal_config ( cal_setting, cal_value ) " .
          "VALUES ('APPLICATION_NAME', ?)" , array ( $_SESSION['application_name'] ) );
  }
   if ( isset ( $_SESSION['server_url'] ) ) {
    dbi_execute ("DELETE FROM webcal_config WHERE cal_setting = 'SERVER_URL'");
    dbi_execute ("INSERT INTO webcal_config ( cal_setting, cal_value ) " .
          "VALUES ('SERVER_URL', ?)" , array ( $_SESSION['server_url'] ) );
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
  $fd = @fopen ( $file, 'w+b', false );
  if ( empty ( $fd ) ) {
    if ( @file_exists ( $file ) ) {
      $onloadDetailStr =  
        translate ( 'Please change the file permissions of this file', true );
    } else {
      $onloadDetailStr = 
        translate ( 'Please change includes dir permission', true );
    }
    $onload = "alert('" . translate ( 'Error Unable to write to file', true ) . $file. "\\n" . 
      $onloadDetailStr . ".');";
  } else {
    fwrite ( $fd, "<?php\r\n" );
    fwrite ( $fd, '/* updated via install/index.php on ' . date('r') . "\r\n" );
    foreach ( $settings as $k => $v ) {
      if ( $v != '<br />' && $v != '' )
      fwrite ( $fd, $k . ': ' . $v . "\r\n" );
    }
    fwrite ( $fd, "# end settings.php */\r\n?>\r\n" );
    fclose ( $fd );
    if ( $post_action != $testSettingsStr && 
      $post_action2 != $createNewStr ){
      $onload .= "alert('" . translate ( 'Your settings have been saved', true ) . ".\\n\\n');";
    }

    // Change to read/write by us only (only applies if we created file)
    // and read-only by all others.  Would be nice to make it 600, but
    // the send_reminders.php script is usually run under a different
    // user than the web server.
    @chmod ( $file, 0644 );
  }
}
//print_r ( $_SESSION);
include_once './header.php';
include_once './step' .  $_SESSION['step'] . '.php';
?>

</body>
</html>
