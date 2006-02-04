<?php
/*
 * $Id$
 *
 * Page Description:
 * Main page for install/config of db settings.
 * This page is used to create/update includes/settings.php.
 *
 * Input Parameters:
 * None
 *
 * Security:
 * The first time this page is accessed, there are no security
 * precautions.   The user is prompted to generate a config password.
 * From then on, users must know this password to make any changes
 * to the settings in settings.php./
 *
 * TODO:
 * Change all references from postgresql to pgsql
 */
$show_all_errors = true;

include_once '../includes/dbi4php.php';
include_once '../includes/config.php';

include_once '../includes/translate.php';
include_once 'tz_import.php';
include_once 'default_config.php';
include_once 'sql/upgrade_matrix.php';
$file = "../includes/settings.php";
$fileDir = "../includes";
$basedir = "..";

//change this path if needed
$firebird_path = 'c:/progra~1/firebird/firebird_1_5/examples/employee.fdb';

clearstatcache();

// We may need time to run extensive database loads
set_time_limit(240);

// If we're using SQLite, it seems that magic_quotes_sybase must be on
//ini_set('magic_quotes_sybase', 'On'); 

// Check for proper auth settings
if ( ! empty (  $_SERVER['PHP_AUTH_USER'] ) )
  $PHP_AUTH_USER= $_SERVER['PHP_AUTH_USER'];

//We'll always use browser defined languages 
$lang = get_browser_language ();
if ( $lang == "none" )
 $lang = "";
if ( strlen ( $lang ) == 0 || $lang == 'none' ) {
$lang = "English-US"; // Default
}

$lang_file = "translations/" . $lang . ".txt";

function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //3, "d:\php\logs\debug.txt" );
}

// Get value from POST form
function getPostValue ( $name ) {
  global $HTTP_POST_VARS;

  if ( isset ( $_POST ) && is_array ( $_POST ) && ! empty ( $_POST[$name] ) ) {
  $_POST[$name] = ( get_magic_quotes_gpc () != 0? $_POST[$name]: addslashes ( $_POST[$name]) );
   $HTTP_POST_VARS[$name] = $_POST[$name];
    return $_POST[$name];
  } else if ( ! isset ( $HTTP_POST_VARS ) ) {
    return null;
  } else if ( ! isset ( $HTTP_POST_VARS[$name] ) ) {
    return null;
 }
  return ( $HTTP_POST_VARS[$name] );
}


// Get value from GET form
function getGetValue ( $name ) {
  global $HTTP_GET_VARS;

  if ( isset ( $_GET ) && is_array ( $_GET ) && ! empty ( $_GET[$name] ) ) {
  $_GET[$name] = ( get_magic_quotes_gpc () != 0? $_GET[$name]: addslashes ( $_GET[$name]) );
    $HTTP_GET_VARS[$name] = $_GET[$name];
  return $_GET[$name];
  } else if ( ! isset ( $HTTP_GET_VARS ) ) {
    return null;
  } else if ( ! isset ( $HTTP_GET_VARS[$name] ) ){
    return null;
 }
  return ( $HTTP_GET_VARS[$name] );
}

function get_php_setting ( $val ) {
  $setting = ini_get ( $val );
  if ( $setting == '1' || $setting == 'ON' )
    return 'ON';
  else
    return 'OFF';
}


function get_php_modules ( $val ) {
  $setting = function_exists ( $val );
  if ( $setting  )
    return 'ON';
  else
    return 'OFF';
}

// We will generate many errors while trying to test database
// Disable them temporarily as needed
function show_errors ( $error_val=0 ) {
  global $show_all_errors;
  
 if ( empty ( $_SESSION['error_reporting'] ) )
    $_SESSION['error_reporting'] = get_php_setting ( 'error_reporting' ); 
  if ( $show_all_errors == true ) {
   ini_set ( "error_reporting", 64 );
 } else {
    ini_set ( "error_reporting", ( $error_val? $_SESSION['error_reporting'] :64) );
 }
}

function get_installed_version () {
 global $database_upgrade_matrix, $show_all_errors;
 
  //disable warnings
 show_errors ();
 // Set this as the default value
 $_SESSION['application_name']  = "Title";
 $_SESSION['old_program_version'] = "new_install";
 $_SESSION['blank_database'] = "";
 
 //We will append the db_type to come up te proper filename
  $_SESSION['install_file'] = "tables";
 //This data is read from file upgrade_matrix.php
 for ( $i=0; $i < count( $database_upgrade_matrix); $i++ ) {
   $sql = $database_upgrade_matrix[$i][0];
  $res = dbi_execute ( $sql, array(), false, false );
  if ( $res ) {
  $_SESSION['old_program_version'] = $database_upgrade_matrix[$i][1];
  $_SESSION['install_file'] = $database_upgrade_matrix[$i][2];
  dbi_free_result ( $res );
  }
 }
 if ( $_SESSION['old_program_version'] == "pre-v0.9.07" ) {
   $response_msg = translate ( "Your previous version of WebCalendar requires running a PERL script " .
    "to convert your data. Please run /tools/upgrade_to_0.9.7.pl then return to this page " .
    "to continue" ) .  ".";
 } else {
   $response_msg = translate ( "Your previous version of WebCalendar requires updating several " .
    "database tables" ) . "."; 
 }
 // v1.1 and after will have an entry in webcal_config to make this easier
 //Not sure if this will work well for CVS code
 //We'll bypass this for now
// $res = dbi_execute ( "SELECT cal_value FROM webcal_config " .
//  "WHERE cal_setting  = 'WEBCAL_PROGRAM_VERSION'", array(), false, false );
// if ( $res ) {
//   $row = dbi_fetch_row ( $res );
//  if ( ! empty ( $row[0] ) ) {  
//    $_SESSION['old_program_version'] = $row[0];
//    $_SESSION['install_file']  = "upgrade_" . $row[0];
//  }
//  dbi_free_result ( $res );
// }

 //We need to determine this is a blank database
 // This may be due to a manual table setup
 $res = dbi_execute ( "SELECT count(cal_value) FROM webcal_config" , array() , false, false );
 if ( $res ) {
   $row = dbi_fetch_row ( $res );
  if ( isset ( $row[0] ) && $row[0] == 0 ) {  
    $_SESSION['blank_database'] = true;
  } else {
   //make sure all existing values in config and pref tables are UPPERCASE
   make_uppercase ();
   
   //delete existing WEBCAL_PROGRAM_VERSION number 
   dbi_execute ("DELETE FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'");
      
   // Insert webcal_config values only if blank
   db_load_config ();   
 }
  dbi_free_result ( $res );
 }
 // Determine if old data has been converted to GMT
 // This seems lke a good place to put this
 $res = dbi_execute ( "SELECT cal_value FROM webcal_config " .
  "WHERE cal_setting  = 'WEBCAL_TZ_CONVERSION'", array(), false, false);
 if ( $res ) {
  $row = dbi_fetch_row ( $res );
  // if not 'Y', we will prompt user to do conversion
  // from server time to GMT time
  if ( ! empty ( $row[0] ) ) {
   $_SESSION['tz_conversion']  = $row[0];
  }
  dbi_free_result ( $res );
 }
 // Get existing server URL
 // We could use the self-discvery value, but this 
 // may be a custom value
 $res = dbi_execute ( "SELECT cal_value FROM webcal_config " .
  "WHERE cal_setting  = 'SERVER_URL'", array(), false, false);
 if ( $res ) {
  $row = dbi_fetch_row ( $res );
  if ( ! empty ( $row[0] ) && strlen ( $row[0] ) ) {
   $_SESSION['server_url']  = $row[0];
  }
  dbi_free_result ( $res );
 }
 // Get existing application name
 $res = dbi_execute ( "SELECT cal_value FROM webcal_config " .
  "WHERE cal_setting  = 'APPLICATION_NAME'", array(), false, false);
 if ( $res ) {
  $row = dbi_fetch_row ( $res );
  if ( ! empty ( $row[0] ) ) {
   $_SESSION['application_name']  = $row[0];
  }
  dbi_free_result ( $res );
 }
 //enable warnings
 show_errors ( true );
} // end get_installed_version 

// First pass at settings.php.
// We need to read it first in order to get the md5 password.
$magic = @get_magic_quotes_runtime();
@set_magic_quotes_runtime(0);    
$fd = @fopen ( $file, "rb", false );
$settings = array ();
$password = '';
$forcePassword = false;
if ( ! empty ( $fd ) ) {
  while ( ! feof ( $fd ) ) {
    $buffer = fgets ( $fd, 4096 );
    $buffer = trim ( $buffer, "\r\n " );
    if ( preg_match ( "/^(\S+):\s*(.*)/", $buffer,  $matches ) ) {
      if ( $matches[1] == "install_password" ) {
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
$doLogin = false;

// Set default Application Name
if ( ! isset ( $_SESSION['application_name'] ) ) {
  $_SESSION['application_name'] = "WebCalendar";
}

// Set Server URL
if ( ! isset ( $_SESSION['server_url'] ) ) {
    if ( ! empty ( $_SERVER['HTTP_HOST'] ) && ! empty ( $_SERVER['REQUEST_URI'] ) ) {
      $ptr = strpos ( $_SERVER['REQUEST_URI'], "/install", 2 );
      if ( $ptr > 0 ) {
        $uri = substr ( $_SERVER['REQUEST_URI'], 0, $ptr + 1 );
        $SERVER_URL = "http://" . $_SERVER['HTTP_HOST'];
        if ( ! empty ( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != 80 )
          $SERVER_URL .= ":" . $_SERVER['SERVER_PORT'];
        $SERVER_URL .= $uri;
        $_SESSION['server_url'] = $SERVER_URL;
      }
    }
}


// Handle "Logout" button
if ( 'logout' == getGetValue ( 'action' ) ) {
  session_destroy ();
  Header ( "Location: index.php" );
  exit;
}

// If password already exists, check for valid session.
if ( file_exists ( $file ) && ! empty ( $password ) &&
  ( empty ( $_SESSION['validuser'] ) ||
  $_SESSION['validuser'] != $password ) ) {
  // Make user login
  $doLogin = true;
}

$pwd = getPostValue ( "password" );
if ( file_exists ( $file ) && ! empty ( $pwd ) ) {
  if ( md5($pwd) == $password ) {
    $_SESSION['validuser'] = $password;
?>
      <html><head><title>Password Accepted</title>
      <meta http-equiv="refresh" content="0; index.php" />
      </head>
      <body onLoad="alert('<?php etranslate ( "Successful Login", true ) ?>');">
      </body></html>
<?php
    exit;
  } else {
    // Invalid password
    $_SESSION['validuser'] = '';
?>
      <html><head><title>Password Incorrect</title>
      <meta http-equiv="refresh" content="0; index.php" />
      </head>
      <body onLoad="alert ('<?php etranslate ( "Invalid Login", true ) ?>'); document.go(-1)">
      </body></html>
<?php
    exit;
  }
}

$php_settings = array (
  array ('Safe Mode','safe_mode','OFF'),
//  array ('Magic Quotes GPC','magic_quotes_gpc','ON'),
  array ('Display Errors','display_errors','ON'),
  array ('File Uploads','file_uploads','ON') 
);

// set up array to test for some constants (display name, constant name, preferred value )
$php_constants = array (
  array (' CRYPT_STD_DES', CRYPT_STD_DES, 1) );
 //future expansion
  // array ('CRYPT_STD_DES',CRYPT_STD_DES, 1)
  // array ('CRYPT_MD5',CRYPT_MD5, 1)
  // array ('CRYPT_BLOWFISH',CRYPT_BLOWFISH, 1)

$gdstring = "GD  (" . translate ( "needed for Gradient Image Backgrounds" ) . ")";
$php_modules = array (
  array ($gdstring,'imagepng','ON'),
);

$pwd1 = getPostValue ( "password1" );
$pwd2 = getPostValue ( "password2" );
if ( file_exists ( $file ) && $forcePassword && ! empty ( $pwd1 ) ) {
  if ( $pwd1 != $pwd2 ) {
    etranslate ( "Passwords do not match" ) . "!<br />\n";
    exit;
  }
  $fd = @fopen ( $file, "a+b", false );
  if ( empty ( $fd ) ) {
    echo "<html><body>" . translate ( "Unable to write password to settings.php file" ) . 
    ".</body></html>";
    exit;
  }
  fwrite ( $fd, "<?php\r\n" );
  fwrite ( $fd, "install_password: " . md5($pwd1) . "\r\n" );
  fwrite ( $fd, "?>\r\n" );
  fclose ( $fd );
  ?>
    <html><head><title>Password Updated</title>
    <meta http-equiv="refresh" content="0; index.php" />
    </head>
    <body onLoad="alert('<?php etranslate ( "Password has been set", true ) ?>');">
    </body></html>
  <?php
  exit;
}

$magic = @get_magic_quotes_runtime();
@set_magic_quotes_runtime(0);
$fd = @fopen ( $file, "rb", false );
if ( ! empty ( $fd ) ) {
  while ( ! feof ( $fd ) ) {
    $buffer = fgets ( $fd, 4096 );
    $buffer = trim ( $buffer, "\r\n " );
    if ( preg_match ( "/^#/", $buffer ) )
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

$action = getGetValue ( "action" );
// We were set here because of a mismatch of $PROGRAM_VERSION
// A simple way to ensure that UPGRADING.html gets read and processed
if ( ! empty ( $action ) && $action == "mismatch" ) {
  $version = getGetValue ( "version" );
 $_SESSION['old_program_version'] = $version;
}

// Go to the proper page
if ( ! empty ( $action ) && $action == "switch" ) {
  $page = getGetValue ( "page" );
 switch ( $page ){
 
   case 2:
     if ( ! empty ( $_SESSION['validuser'] ) ){  
       $_SESSION['step'] = $page;
    $onload = "db_type_handler();";
    }
   break;
  case 3;
     if ( ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_success'] ) ){  
       $_SESSION['step'] = $page;
    }
   break;
  case 4;
     if ( ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_success'] )  &&
      empty ( $_SESSION['db_create'] ) ){  
       $_SESSION['step'] = $page;
    $onload = "auth_handler();";
    }
   break;
  default:
     $_SESSION['step'] = 1;
 }
}

function parse_sql($sql) {
 $sql = trim($sql);
  $sql = trim ( $sql, "\r\n " );
 $ret = array();

 $buffer_str = '';
 for($i=0; $i < strlen($sql); $i++) {
    $buffer_str .= substr($sql, $i, 1);
  if(substr( $sql,$i, 1) == ";") {
   $ret[] = $buffer_str;
   $buffer_str = '';
  }
 }
 return($ret);
}

function db_populate ( $install_filename, $display_sql ) {
  global $str_parsed_sql;
  if ( $install_filename == "" ) return;
 $full_sql = "";
 $magic = @get_magic_quotes_runtime();
 @set_magic_quotes_runtime(0);
 $fd = @fopen ( "sql/" . $install_filename, "r", false);
 //discard everything up to the required point in the upgrade file 
 while (!feof($fd) && empty ( $current_pointer ) ) {
    $data = fgets($fd, 4096);
  $data = trim ( $data, "\r\n " );
  if ( strpos(  strtoupper ( $data ) , strtoupper ( $_SESSION['install_file'] ) )  || 
    substr( $_SESSION['install_file'], 0, 6 ) == "tables" ) {
    $current_pointer = true;
  }
  }
 // We need to strip out the comments from upgrade files
 while (!feof($fd)  ) {
   //We already have a $data item from above
  if ( substr ( $data , 0 , 2 ) == "/*" && 
   substr( $_SESSION['install_file'], 0, 6 ) != "tables" ) {
    //Do nothing...We skip over comments in upgrade files
  } else {
    $full_sql .= $data;
  }
    $data = fgets($fd, 4096);
  $data = trim ( $data, "\r\n " );
  } 
 @set_magic_quotes_runtime($magic);
 fclose ( $fd );
 $parsed_sql  = parse_sql($full_sql);
 //disable warnings
 show_errors ();
 //string version of parsed_sql that is used if displaying sql only
 $str_parsed_sql = "";
  for ( $i = 0; $i < count($parsed_sql); $i++ ) {
    if ( empty ( $display_sql ) ){ 
  if ( $show_all_errors == true ) echo $parsed_sql[$i] . "<br />";
      dbi_execute ( $parsed_sql[$i] );   
  } else {
    $str_parsed_sql .= $parsed_sql[$i] . "\n\n";
  } 
  }
 //echo "PARSED SQL " .  $str_parsed_sql;
 //enable warnings
 show_errors ( true );
} //end db_populate

// We're doing a database installation yea ha!
if ( ! empty ( $action ) &&  $action == "install" ){
    // We'll grab database settings from settings.php
    $db_persistent = false;
    $db_type = $settings['db_type'];
    $db_host = $settings['db_host'];
    $db_database = $settings['db_database'];
    $db_login = $settings['db_login'];
    $db_password = $settings['db_password'];

    // We might be displaying sql only
  $display_sql = getPostValue('display_sql');
  
    $c = dbi_connect ( $db_host, $db_login,
      $db_password, $db_database );
  // It's possible that the tables were created manually
  // and we just want to do the database population routines
  if ( $c && isset ( $_SESSION['install_file'] )  ) {
   $sess_install = $_SESSION['install_file'];
    $install_filename = ( $sess_install == "tables" ? "tables":"upgrade");
    switch ( $db_type ) {
       case "mysql";
      $install_filename .= "-mysql.sql";    
        break;
       case "mysqli";
      $install_filename .= "-mysql.sql";    
        break;      
       case "mssql";
      $install_filename .="-mssql.sql";    
        break;
       case "ibm_db2";
      $install_filename .="-db2.sql";    
        break;
     case "oracle";
      $install_filename .= "-oracle.sql";    
      break;
       case "ibase";
      $install_filename .= "-ibase.sql";    
        break;
       case "postgresql";
      $install_filename .= "-postgres.sql";    
        break;
     case "odbc";
       $underlying_db = "-" . $_SESSION['odbc_db'] . ".sql";
      $install_filename .= $underlying_db;        
      break;
     case "sqlite";
       include_once "sql/tables-sqlite.php";
      populate_sqlite_db ( $db_database, $c );
      $install_filename =  "";      
      break;      
     default; 
    }
     db_populate ( $install_filename , $display_sql );
  }
  if ( empty ( $display_sql ) ){
   //Convert passwords to md5 hashes if needed
   $sql = "SELECT cal_login, cal_passwd FROM webcal_user";
   $res = dbi_execute ( $sql, array(), false, $show_all_errors );
   if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
     if ( strlen ( $row[1] ) < 30 ) {
      dbi_execute ("UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?", array ( md5( $row[1] ) , $row[0] ) );
     }
    }
    dbi_free_result ( $res );
   }
   
   // Run Timezone Data Import
   $ret = do_tz_import ();
   if ( substr ( $ret, 3, 17 ) == "Import Successful" ) {
    $_SESSION['tz_install_success']  = true;
   }
   
  
   // If new install, run 0 GMT offset
   //if ( $_SESSION['old_program_version'] == "new_install" ) {
    //convert_server_to_GMT ( 0 );
   //}
   
  //for upgrade to v1.1b we need to convert existing categories 
  //and repeating events
  do_v11b_updates();
  
 
   // Update the version info
   get_installed_version();
   
   $_SESSION['blank_database'] = "";
  } //end if $display_sql
  
} //end database installation

//Set the value of the underlying database for ODBC connections
if ( ! empty ( $action ) &&  $action == "set_odbc_db" ){
 $_SESSION['odbc_db'] = getPostValue("odbc_db");
}

$post_action = getPostValue ( "action" );
$post_action2 = getPostValue ( "action2" );
// Is this a db connection test?
// If so, just test the connection, show the result and exit.
if (  ! empty ( $post_action ) && $post_action == "Test Settings"  && 
  ! empty ( $_SESSION['validuser'] )  ) {
    $_SESSION['db_success'] = false;
    $db_persistent = getPostValue ( 'db_persistent' );
    $db_type = getPostValue ( 'form_db_type' );
    $db_host = getPostValue ( 'form_db_host' );
    $db_database = getPostValue ( 'form_db_database' );
    $db_login = getPostValue ( 'form_db_login' );
    $db_password = getPostValue ( 'form_db_password' );
    //Allow  field length to change id needed
   $onload = "db_type_handler();";
    
   //disable warnings
   show_errors ();
   $c = dbi_connect ( $db_host, $db_login,
     $db_password, $db_database );

    //enable warnings
   show_errors ( true);
  
    if ( $c ) {
    $_SESSION['db_success'] = true;
   
   // Do some queries to try to determine the previous version
   get_installed_version();
   
     $response_msg = "<b>" .translate ( "Connection Successful" ) . "</b> " .
     translate ( "Please go to next page to continue installation" ) . ".";
   
    } else {
      $response_msg =  "<b>" . translate ( "Failure Reason" ) . 
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n";
   // See if user is valid, but database doesn't exist
   // The normal call to dbi_connect simply return false for both conditions
   if ( $db_type == "mysql"  ) {
     $c = mysql_connect ( $db_host, $db_login, $db_password );
   } else if ( $db_type == "mssql"  ) {
     $c = mssql_connect ( $db_host, $db_login, $db_password );
   } else if ( $db_type == "postgresql"  ) {
     $c = dbi_connect ( $db_host, $db_login, $db_password , 'template1');
   } else if ( $db_type == "ibase"  ) {
    //TODO figure out how to remove this hardcoded link
     $c = dbi_connect ( $db_host, $db_login, $db_password , $firebird_path);
      } //TODO Code remaining database types
   if ( $c ) { // credentials are valid, but database doesn't exist
      $response_msg = translate ( "Correct your entries or click the <b>Create New</b> button to continue installation" );
     $_SESSION['db_noexist'] = true;
   } else {
      $response_msg = "<b>" . translate ( "Failure Reason" ) .
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n" .
       translate ( "Correct your entries and try again" );
   } 
  }

// Is this a db create?
// If so, just test the connection, show the result and exit.
} else if ( ! empty ( $post_action2 ) && $post_action2== "Create New"  && 
  ! empty ( $_SESSION['validuser'] ) && ! empty ( $_SESSION['db_noexist'] )) {
    $_SESSION['db_success'] = false;

    $db_persistent = false;
    $db_type = getPostValue ( 'form_db_type' );
    $db_host = getPostValue ( 'form_db_host' );
    $db_database = getPostValue ( 'form_db_database' );
    $db_login = getPostValue ( 'form_db_login' );
    $db_password = getPostValue ( 'form_db_password' );

    //Allow ODBC field to be visible if needed
   $onload = "db_type_handler();";
  
    // We don't use the normal dbi_execute because we need to know
  // the difference between no conection and no database 
  if ( $db_type == "mysql" ) {
      $c = dbi_connect ( $db_host, $db_login, $db_password, 'mysql' );
      if ( $c ) {
     dbi_execute ( "CREATE DATABASE $db_database;" , array(), false, $show_all_errors);
    if ( ! @mysql_select_db ( $db_database ) ) {
      $response_msg = "<b>" . translate ( "Failure Reason" ) . 
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n";
    } else {
      $_SESSION['db_noexist'] = false;
      $_SESSION['old_program_version'] = 'new_install';
    }
    } else {
      $response_msg = "<b>" . translate ( "Failure Reason" ) . 
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n";

   }
  } else if ( $db_type == "mssql" ) {
      $c = dbi_connect ( $db_host, $db_login, $db_password , 'master');
      if ( $c ) {
     dbi_execute ( "CREATE DATABASE $db_database;" , array(), false, $show_all_errors);
    if ( ! @mssql_select_db ( $db_database ) ) {
      $response_msg = "<b>" . translate ( "Failure Reason" ) . 
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n";

    } else {
      $_SESSION['db_noexist'] = false;
      $_SESSION['old_program_version'] = 'new_install';
    }
     } else {
      $response_msg = "<b>" . translate ( "Failure Reason" ) . 
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n";

   }
  } else if ( $db_type == "postgresql" ) {
   $c = dbi_connect ( $db_host, $db_login, $db_password , 'template1'); 
      if ( $c ) {
     dbi_execute ( "CREATE DATABASE $db_database" , array(), false, $show_all_errors);
     $_SESSION['db_noexist'] = false;
    } else {
      $response_msg = "<b>" . translate ( "Failure Reason" ) . 
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n";

   }
  } // TODO code remainig database types
}

// Is this a Timezone Convert?
// If so, run it
if ( ! empty ( $action ) && $action == "tz_convert" && ! empty ( $_SESSION['validuser'] ) ) {
    $db_persistent = false;
    $db_type = $settings['db_type'];
    $db_host = $settings['db_host'];
    $db_database = $settings['db_database'];
    $db_login = $settings['db_login'];
    $db_password = $settings['db_password'];
  // Avoid false visibilty of single user login
  $onload = "auth_handler();";   
    $c = dbi_connect ( $db_host, $db_login,
      $db_password, $db_database );
 
    if ( $c ) {
        $ret = convert_server_to_GMT ();
    if ( substr ( $ret, 3, 21 ) == "Conversion Successful" ) {
      $_SESSION['tz_conversion']  = 'Success';
     $response_msg = translate ( "Timezone Conversion Successful" );
    } else {
       $response_msg = translate ( "Error Converting Timezone" );
    }
    } else {
      $response_msg = "<b>" . translate ( "Failure Reason" ) . 
     ":</b><blockquote>" . dbi_error () . "</blockquote>\n";
    }
}

// Is this a call to phpinfo()?
if ( ! empty ( $action ) && $action == "phpinfo" ) {
  if ( ! empty ( $_SESSION['validuser'] ) ) {
    phpinfo();
  } else {
    etranlate ( "You are not authorized" ) . ".";
  }
  exit;
}

// Session check counter
if ( isset (  $_SESSION['check'] ) ){
  $_SESSION['check']++;
} else {
   $_SESSION['check'] = 0;
}

$exists = false;
$exists = file_exists ( $file );
$canWrite = false;
if ( $exists ) {
  $canWrite = is_writable ( $file );
} else {
  // check to see if we can create the settings file.
  $testFd = @fopen ( $file, "w+b", false );
  if ( file_exists ( $file ) ) {
    $canWrite = true;
    $exists  = true;
    $forcePassword = true;
  }
  @fclose ( $testFd ); 
}



// If we are handling a form POST, then take that data and put it in settings
// array.
$x = getPostValue ( "form_db_type" );
if ( empty ( $x ) ) {
  // No form was posted.  Set defaults if none set yet.
  if ( ! file_exists ( $file ) || count ( $settings ) == 1) {
    $settings['db_type'] = 'mysql';
    $settings['db_host'] = 'localhost';
    $settings['db_database'] = 'intranet';
    $settings['db_login'] = 'webcalendar';
    $settings['db_password'] = 'webcal01';
    $settings['db_persistent'] = 'false';
    $settings['readonly'] = 'false';
    $settings['user_inc'] = 'user.php';
    $settings['install_password'] = '';
    $settings['single_user_login'] = '';
    $settings['use_http_auth'] = 'false';
    $settings['single_user'] = 'false';
  }
} else {
  $settings['db_type'] = getPostValue ( 'form_db_type' );
  $settings['db_host'] = getPostValue ( 'form_db_host' );
  $settings['db_database'] = getPostValue ( 'form_db_database' );
  $settings['db_login'] = getPostValue ( 'form_db_login' );
  $settings['db_password'] = getPostValue ( 'form_db_password' );
  $settings['db_persistent'] = getPostValue ( 'form_db_persistent' );
  $settings['readonly'] = ( ! isset ( $settings['readonly'] )?'false': $settings['readonly']);
  $settings['user_inc'] = ( ! isset ( $settings['user_inc'] )? 'user.php': $settings['user_inc']);
  $settings['install_password'] = ( ! isset ( $settings['install_password'] )?'' :$settings['install_password']);
  $settings['single_user_login'] = ( ! isset ( $settings['single_user_login'] )? '': $settings['single_user_login']);
  $settings['use_http_auth'] = ( ! isset ( $settings['use_http_auth'] )?'false':$settings['use_http_auth']);
  $settings['single_user'] = ( ! isset ( $settings['single_user'] )?'false': $settings['single_user']);
}
$y = getPostValue ( "app_settings" );
if ( ! empty ( $y ) ) {
  $settings['single_user_login'] = getPostValue ( 'form_single_user_login' );
  $settings['readonly'] = getPostValue ( 'form_readonly' );
  $settings['mode'] = getPostValue ( 'form_mode' );
  if ( getPostValue ( "form_user_inc" ) == "http" ) {
    $settings['use_http_auth'] = 'true';
    $settings['single_user'] = 'false';
    $settings['user_inc'] = 'user.php';
  } else if ( getPostValue ( "form_user_inc" ) == "none" ) {
    $settings['use_http_auth'] = 'false';
    $settings['single_user'] = 'true';
    $settings['user_inc'] = 'user.php';
  } else {
    $settings['use_http_auth'] = 'false';
    $settings['single_user'] = 'false';
    $settings['user_inc'] = getPostValue ( 'form_user_inc' );
  }

 //Save Application Name and Server URL
 $db_persistent = false;
 $db_type = $settings['db_type'];
 $_SESSION['application_name']  = getPostValue ( "form_application_name" );
 $_SESSION['server_url']  = getPostValue ( "form_server_url" );
  $c = dbi_connect ( $settings['db_host'], $settings['db_login'],
    $settings['db_password'], $settings['db_database'] );
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
 $do_load_admin = getPostValue ( "load_admin" );
 if ( ! empty ( $do_load_admin ) ) {
  //add default admin user if not exists
  db_load_admin ();
 }
 $setup_complete = true;
}
  // Save settings to file now.
if ( ! empty ( $x ) || ! empty ( $y ) ){
  $fd = @fopen ( $file, "w+b", false );
  if ( empty ( $fd ) ) {
    if ( file_exists ( $file ) ) {
      $onload = "alert('" . translate ( "Error Unable to write to file", true ) . 
     $file . "\\n" . translate ( "Please change the file permissions of this file", true ) . ".');";
    } else {
      $onload = "alert('" . translate ( "Error Unable to write to file", true ) . 
     $file. "\\n" . translate ( "Please change the file permissions of your includes directory to allow writing by other users", true ) . ".');";
    }
  } else {
    fwrite ( $fd, "<?php\r\n" );
    fwrite ( $fd, "# updated via install/index.php on " . date("r") . "\r\n" );
    foreach ( $settings as $k => $v ) {
      fwrite ( $fd, $k . ": " . $v . "\r\n" );
    }
    fwrite ( $fd, "# end settings.php\r\n?>\r\n" );
    fclose ( $fd );
    if ( $post_action != 'Test Settings' && $post_action2 != 'Create New' ){
      $onload .= "alert('" . translate ( "Your settings have been saved", true ) . ".\\n\\n');";
    }

    // Change to read/write by us only (only applies if we created file)
    // and read-only by all others.  Would be nice to make it 600, but
    // the send_reminders.php script is usually run under a different
    // user than the web server.
    @chmod ( $file, 0644 );
  }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head><title>WebCalendar Setup Wizard</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php include "../includes/js/visible.php"; ?>
<script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
<?php   if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
function testPHPInfo () {
  var url;
  url = "index.php?action=phpinfo";
  //alert ( "URL:\n" + url );
  window.open ( url, "wcTestPHPInfo", "width=800,height=600,resizable=yes,scrollbars=yes" );
}
<?php } ?>
function validate(form)
{
  var form = document.form_app_settings;
  var err = "";
  // only check is to make sure single-user login is specified if
  // in single-user mode
  // find id of single user object
  var listid = 0;
  for ( i = 0; i < form.form_user_inc.length; i++ ) {
    if ( form.form_user_inc.options[i].value == "none" )
      listid = i;
  }
  if ( form.form_user_inc.options[listid].selected ) {
    if ( form.form_single_user_login.value.length == 0 ) {
      // No single user login specified
      alert ("<?php etranslate ("Error you must specify a\\nSingle-User Login", true ) ?> ");
      form.form_single_user_login.focus ();
      return false;
    }
  }
  if ( form.form_server_url.value == "" ) {
    err += "<?php etranslate ( "Server URL is required", true ) ?>" + "\n";
    form.form_server_url.select ();
    form.form_server_url.focus ();
  }
  else if ( form.form_server_url.value.charAt (
    form.form_server_url.value.length - 1 ) != '/' ) {
    err += "<?php etranslate ( "Server URL must end with", true )?>" + "'/'\n";
    form.form_server_url.select ();
    form.form_server_url.focus ();
  }
 if ( err != "" ) {
    alert ( "<?php etranslate ( "Error", true ) ?>" + ":\n\n" + err );
    return false;
  }
  // Submit form...
  form.submit ();
}
function auth_handler () {
  var form = document.form_app_settings;
  // find id of single user object
  var listid = 0;
  for ( i = 0; i < form.form_user_inc.length; i++ ) {
    if ( form.form_user_inc.options[i].value == "none" )
      listid = i;
  }
  if ( form.form_user_inc.options[listid].selected ) {
    makeVisible ( "singleuser" );
  } else {
    makeInvisible ( "singleuser" );
  }
}

function db_type_handler () {
  var form = document.dbform;
  // find id of db_type object
  var listid = 0;
  for ( i = 0; i < form.form_db_type.length; i++ ) {
    if ( form.form_db_type.options[i].value == "sqlite" )
      sqliteid = i;
  if ( form.form_db_type.options[i].value == "ibase" )
      ibaseid = i;
  }
   if ( form.form_db_type.options[sqliteid].selected  || 
    form.form_db_type.options[ibaseid].selected) {
      form.form_db_database.size = 65;
    document.getElementById("db_name").innerHTML = 
    "<?php etranslate ( "Database Name" ) ?>" + ":" +  
   "<?php etranslate ( "Full Path (no backslashes)") ?>";
  } else {
      form.form_db_database.size = 20;
    document.getElementById("db_name").innerHTML = "<?php etranslate ( "Database Name" ) ?>" + ":";
  }
}
function chkPassword () {
  var form = document.dbform;
  var db_pass = form.form_db_password.value;
  var illegalChars = /\#/;
  // do not allow #.../\#/ would stop all non-alphanumeric
  if (illegalChars.test(db_pass)) {
    alert( "<?php etranslate ( "The password contains illegal characters.", true ) ?>");
    form.form_db_password.select ();
    form.form_db_password.focus ();
    return false;
  } 
}
 //]]> -->
</script>
<style type="text/css">
body {
  background-color: #ffffff;
  font-family: Arial, Helvetica, sans-serif;
  margin: 0;
}
table {
  border: 0px solid #ccc;
}
th.pageheader {
  font-size: 24px;
 padding:10px;
  background-color: #eee;
}
th.header {
  font-size: 18px;
  background-color: #eee;
}
th.redheader {
  font-size: 18px;
  color: red; 
  background-color: #eee;
}
td {
  padding: 5px;
}
td.prompt {
  font-weight: bold;
  padding-right: 20px;
}
td.subprompt {
  font-weight: bold;
  padding-right: 20px;
 font-size: 14px;
}
div.nav {
  margin: 0;
  border-bottom: 1px solid #000;
}
div.main {
  margin: 10px;
}
li {
  margin-top: 10px;
}
doc.li {
  margin-top: 5px;
}
.recommended {
  color: green;
}
.notrecommended {
  color: red;
}
</style>
</head>
<body <?php if ( ! empty ($onload) ) echo "onload=\"$onload\""; ?> >
<?php   //print_r ( $_SERVER ); ?>
<?php if ( empty ( $_SESSION['step'] ) || $_SESSION['step'] < 2 ) {?>
<table border="1" width="90%" align="center">
<tr><th class="pageheader"  colspan="2"><?php echo translate ( "WebCalendar Installation Wizard" ) . ":" . translate ( "Step" ) ?> 1</th></tr>
<tr><td colspan="2" width="50%">
<?php etranslate ( "This installation wizard will guide you through setting up a basic WebCalendar installation. For help and troubleshooting see" ) ?>:<br />
<a href="../docs/WebCalendar-SysAdmin.html" target="_docs">System Administrator's Guide</a>,
<a href="../docs/WebCalendar-SysAdmin.html#faq" target="_docs">FAQ</a>,
<a href="../docs/WebCalendar-SysAdmin.html#trouble" target="_docs">Troubleshooting</a>,
<a href="../docs/WebCalendar-SysAdmin.html#help" target="_docs">Getting Help</a>,
<a href="../UPGRADING.html" target="_docs">Upgrading Guide</a>
</td></tr>
<!--<tr><th class="header"  colspan="2"><?php etranslate ( "WebCalendar Version Check" ) ?></th></tr>
<tr><td>
<?php echo translate ( "This is version" ) . " " . $PROGRAM_VERSION . " "; ?>
</td><td>
<?php etranslate ( "The most recent version available is" ) ?> <img src="version.gif" />
</td></tr> -->
<tr><th class="header"  colspan="2"><?php etranslate ( "PHP Version Check" ) ?></th></tr>
<tr><td>
<?php etranslate ( "Check to see if PHP 4.1.0 or greater is installed" ) ?>. 
</td>
  <?php
    $class = ( version_compare(phpversion(), "4.1.0", ">=") ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class='recommended') {
     echo "<img src=\"recommended.gif\" />&nbsp;";
    } else {
 echo "<img src=\"not_recommended.jpg\" />&nbsp;";
    }
    echo translate ( "PHP version") . " " . phpversion();
   ?>
</td></tr>
<tr><th class="header" colspan="2">
 PHP Settings
<?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
  &nbsp;<input name="action" type="button" value="<?php etranslate ( "Detailed PHP Info" ) ?>" onClick="testPHPInfo()" />
<?php } ?>
</th></tr>
<?php foreach ( $php_settings as $setting ) { ?>
  <tr><td class="prompt"><?php echo $setting[0];?></td>
  <?php
    $class = ( get_php_setting ( $setting[1] ) == $setting[2] ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class == 'recommended') {
     echo "<img src=\"recommended.gif\" />&nbsp;";
    } else {
 echo "<img src=\"not_recommended.jpg\" />&nbsp;";
    }
    echo get_php_setting ( $setting[1] );
   ?>
   </td></tr>
<?php } ?>
<?php foreach ( $php_constants as $constant ) { ?>
  <tr><td class="prompt"><?php echo $constant[0];?></td>
  <?php
    $class = (  $constant[1] ) == $constant[2]  ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class == 'recommended') {
      echo "<img src=\"recommended.gif\" />&nbsp;ON";
    } else {
      echo "<img src=\"not_recommended.jpg\" />&nbsp;OFF";
    }
   ?>
   </td></tr>
<?php } ?>  

<?php foreach ( $php_modules as $module ) { ?>
  <tr><td class="prompt"><?php echo $module[0];?></td>
  <?php
    $class = ( get_php_modules ( $module[1] ) == $module[2] ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class == 'recommended') {
     echo "<img src=\"recommended.gif\" />&nbsp;";
    } else {
 echo "<img src=\"not_recommended.jpg\" />&nbsp;";
    }
    echo get_php_modules ( $module[1] );
   ?>
   </td></tr>
<?php } ?>  

 <tr><th class="header" colspan="2"><?php etranslate ( "Session Check" ) ?></th></tr>
 <tr><td>
  <?php echo translate ( "To test the proper operation of sessions, reload this page" ) . "<br />" . 
  translate ( "You should see the session counter increment each time" ) ?>.</td>
<?php
    $class = ( $_SESSION['check'] > 0 ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($_SESSION['check'] > 0) {
     echo "<img src=\"recommended.gif\" />&nbsp;";
    } else {
 echo "<img src=\"not_recommended.jpg\" />&nbsp;";
    }
     echo translate ( "SESSION COUNTER" ) . ": " . $_SESSION['check'];
?>
 </td></tr>
<?php //if the settings file doesn't exist or we can't write to it, echo an error header..
if ( ! $exists || ! $canWrite ) { ?>
 <tr><th class="redheader" colspan="2"><?php echo translate ( "Settings.php Status" ) . 
   ":" . translate ( "Error" ) ?></th></tr>
<?php //..otherwise, edit a regular header
} else { ?>
 <tr><th class="header" colspan="2">Settings.php Status</th></tr>
<?php } ?>
<?php //if the settings file exists, but we can't write to it..
 if ( $exists && ! $canWrite ) { ?>
  <tr><td>
   <img src="not_recommended.jpg" />&nbsp;<?php etranslate ( "The file permissions of <b>settings.php</b> are set..." ) ?>:</td><td>
   <blockquote><b>
    <?php echo realpath ( $file ); ?>
   </b></blockquote>
  </td></tr>
<?php //or, if the settings file doesn't exist & we can't write to the includes directory..
 } else if ( ! $exists && ! $canWrite ) { ?>
  <tr><td colspan="2">
   <img src="not_recommended.jpg" />&nbsp;<?php etranslate ( "The file permissions of the <b>includes</b> directory are set..." ) ?>:
   <blockquote><b>
    <?php echo realpath ( $fileDir ); ?>
   </b></blockquote>
  </td></tr>
<?php //if settings.php DOES exist & we CAN write to it..
 } else { ?>
  <tr><td>
   <?php etranslate ( "Your <b>settings.php</b> file appears to be valid" ) ?>.</td><td class="recommended">
   <img src="recommended.gif" />&nbsp;OK
  </td></tr>

<?php if (  empty ( $_SESSION['validuser'] ) ) { ?>
 <tr><th colspan="2" class="header"><?php etranslate ( "Configuration Wizard Password" ) ?></th></tr>
 <tr><td colspan="2" align="center">
 <?php if ( $doLogin ) { ?>
  <form action="index.php" method="post" name="dblogin">
   <table>
    <tr><th>
     <?php etranslate ( "Password" ) ?>:</th><td>
     <input name="password" type="password" />
     <input type="submit" value="Login" />
    </td></tr>
   </table>
  </form>
 <?php } else if ( $forcePassword ) { ?>
  <form action="index.php" method="post" name="dbpassword">
   <table border="0">
    <tr><th colspan="2" class="header">
     <?php etranslate ( "Create Settings File Password" ) ?>
    </th></tr>
    <tr><th>
     <?php etranslate ( "Password" ) ?>:</th><td>
     <input name="password1" type="password" />
    </td></tr>
    <tr><th>
     <?php etranslate ( "Password (again)" ) ?>:</th><td>
     <input name="password2" type="password" />
    </td></tr>
    <tr><td colspan="2" align="center">
     <input type="submit" value="<?php etranslate ( "Set Password" ) ?>" />
    </td></tr>
   </table>
  </form>
 <?php } ?>
<?php } ?>
<?php } ?> 
</td></tr>
</td></tr></table>
<?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
<table border="0" width="90%" align="center">
 <tr><td align="center">
  <form action="index.php?action=switch&amp;page=2" method="post">
   <input type="submit" value="<?php etranslate ( "Next" ) ?> ->" />
  </form>
 </td></tr>
</table>
<?php } ?>

<?php //BEGIN STEP 2 
} else if ( $_SESSION['step'] == 2 ) { ?>

<table border="1" width="90%" align="center">
 <tr><th class="pageheader" colspan="2">
  <?php echo translate ( "WebCalendar Installation Wizard" ) . ": " . translate ( "Step" ) ?> 2
 </th></tr>
 <tr><td colspan="2" width="50%">
  <?php echo translate ( "In this section you will set up and test a connection to your database server" ) . ". " .
    translate ( "The account information supplied should have FULL permissions to create databases, tables and users" ) . "." . 
   translate ( "If this is not possible, or your database access is limited, you will have to manually configure your database" ) ?>.
 </td></tr>
 <tr><th colspan="2" class="header">
  <?php etranslate ( "Database Status" ) ?>
 </th></tr>
 <tr><td>
  <ul>
   <li><?php etranslate ( "Supported databases for your PHP installation" ) ?>:
<?php
  $dbs = array ();
  if ( function_exists ( "mysql_pconnect" ) )
    $dbs[] = "mysql";
  if ( function_exists ( "mysqli_connect" ) )
    $dbs[] = "mysqli";
  if ( function_exists ( "OCIPLogon" ) )
    $dbs[] = "oracle";
  if ( function_exists ( "pg_pconnect" ) )
    $dbs[] = "postgresql";
  if ( function_exists ( "odbc_pconnect" ) )
    $dbs[] = "odbc";
  if ( function_exists ( "ibase_pconnect" ) )
    $dbs[] = "ibase";
  if ( function_exists ( "mssql_pconnect" ) )
    $dbs[] = "mssql";
  if ( function_exists ( "sqlite_open" ) )
    $dbs[] = "sqlite";
  if ( function_exists ( "db2_pconnect" ) )
    $dbs[] = "ibm_db2";

  for ( $i = 0; $i < count ( $dbs ); $i++ ) {
    if ( $i ) echo ", ";
  echo  $dbs[$i] ;
    $supported[$dbs[$i]] = true;
  }
?></li>

<?php if ( ! empty ( $_SESSION['db_success'] ) && $_SESSION['db_success']  ) { ?>
  <li class="recommended"><img src="recommended.gif" />&nbsp;<?php etranslate ( "Your current database settings are able to access the database" ) ?>.</li>
  <?php if ( ! empty ( $response_msg ) ) { ?>
  <li class="recommended"><img src="recommended.gif" />&nbsp;<?php echo $response_msg; ?></li>
   <?php } else {?>
  <li class="notrecommended"><img src="not_recommended.jpg" />&nbsp;<b><?php etranslate ( "Please Test Settings" ) ?></b></li>  
  <?php } ?>
<?php } else { ?>
  <li class="notrecommended"><img src="not_recommended.jpg" />&nbsp;<?php etranslate ( "Your current database settings are <b>not</b> able to access the database or have not yet been tested" ) ?>.</li>
  <?php if ( ! empty ( $response_msg ) ) { ?>
  <li class="notrecommended"><img src="not_recommended.jpg" />&nbsp;<?php echo $response_msg; ?></li>
   <?php } ?>
<?php } ?>
</ul>
</td></tr>
<tr><th class="header" colspan="2">
 <?php etranslate ( "Database Settings" ) ?>
</th></tr>
<tr><td>
 <form action="index.php" method="post" name="dbform" onSubmit="return chkPassword()">
 <table align="right" width="100%" border="0">
  <tr><td rowspan="6" width="20%">&nbsp;
   </td><td class="prompt" width="25%" valign="bottom">
   <label for="db_type"><?php etranslate ( "Database Type" ) ?>:</label></td><td valign="bottom">
   <select name="form_db_type" id="db_type" onChange="db_type_handler();">
<?php
  if ( ! empty ( $supported['mysql'] ) )
    echo "  <option value=\"mysql\" " .
      ( $settings['db_type'] == 'mysql' ? " selected=\"selected\"" : "" ) .
      ">MySQL</option>\n";
      
  if ( ! empty ( $supported['mysqli'] ) )
    echo "  <option value=\"mysqli\" " .
      ( $settings['db_type'] == 'mysqli' ? " selected=\"selected\"" : "" ) .
      ">MySQL (Improved)</option>\n";

  if ( ! empty ( $supported['oracle'] ) )
    echo "  <option value=\"oracle\" " .
      ( $settings['db_type'] == 'oracle' ? " selected=\"selected\"" : "" ) .
      ">Oracle (OCI)</option>\n";

  if ( ! empty ( $supported['postgresql'] ) )
    echo "  <option value=\"postgresql\" " .
      ( $settings['db_type'] == 'postgresql' ? " selected=\"selected\"" : "" ) .
      ">PostgreSQL</option>\n";

  if ( ! empty ( $supported['ibm_db2'] ) )
    echo "  <option value=\"ibm_db2\" " .
      ( $settings['db_type'] == 'ibm_db2' ? " selected=\"selected\"" : "" ) .
      ">IBM DB2 Universal Database</option>\n";

  if ( ! empty ( $supported['odbc'] ) )
    echo "  <option value=\"odbc\" " .
      ( $settings['db_type'] == 'odbc' ? " selected=\"selected\"" : "" ) .
      ">ODBC</option>\n";

  if ( ! empty ( $supported['ibase'] ) )
    echo "  <option value=\"ibase\" " .
      ( $settings['db_type'] == 'ibase' ? " selected=\"selected\"" : "" ) .
      ">Interbase</option>\n";

  if ( ! empty ( $supported['mssql'] ) )
    echo "  <option value=\"mssql\" " .
      ( $settings['db_type'] == 'mssql' ? " selected=\"selected\"" : "" ) .
      ">MS SQL Server</option>\n";
      
  if ( ! empty ( $supported['sqlite'] ) )
    echo "  <option value=\"sqlite\" " .
      ( $settings['db_type'] == 'sqlite' ? " selected=\"selected\"" : "" ) .
      ">SQLite</option>\n";
?>
   </select>
  </td></tr>
  <tr><td class="prompt">
   <label for="server"><?php etranslate ( "Server" ) ?>:</label></td><td colspan="2">
   <input name="form_db_host" id="server" size="20" value="<?php echo $settings['db_host'];?>" />
  </td></tr>
  <tr><td class="prompt">
   <label for="login"><?php etranslate ( "Login" ) ?>:</label></td><td colspan="2">
   <input name="form_db_login" id="login" size="20" value="<?php echo $settings['db_login'];?>" />
  </td></tr>
  <tr><td class="prompt">
   <label for="pass"><?php etranslate ( "Password" ) ?>:</label></td><td colspan="2">
   <input name="form_db_password" id="pass"  size="20" value="<?php echo $settings['db_password'];?>" />
  </td></tr>
  <tr><td class="prompt" id="db_name">
   <label for="database"><?php etranslate ( "Database Name" ) ?>:</label></td><td colspan="2">
   <input name="form_db_database" id="database" size="20" value="<?php echo $settings['db_database'];?>" />
  </td></tr>

<?php
  // This a workaround for postgresql. The db_type should be 'pgsql' but 'postgresql' is used
 // in a lot of places...so this is easier for now :(  
  $real_db_type = ( $settings['db_type'] == "postgresql" ? "pgsql" : $settings['db_type'] );
  if ( substr( php_sapi_name(), 0, 3) <> "cgi" && 
        ini_get( $real_db_type . ".allow_persistent" ) ){ ?>
  <tr><td class="prompt">
   <label for="conn_pers"><?php etranslate ( "Connection Persistence" ) ?>:</label></td><td colspan="2">
   <label><input name="form_db_persistent" value="true" type="radio"<?php 
    echo ( $settings['db_persistent'] == 'true' ) ? " checked=\"checked\"" : ""; ?> /><?php etranslate ( "Enabled" ) ?></label>
  &nbsp;&nbsp;&nbsp;&nbsp;
   <label><input name="form_db_persistent" value="false" type="radio"<?php 
    echo ( $settings['db_persistent'] != 'true' )? " checked=\"checked\"" : ""; ?> /><?php etranslate ( "Disabled" ) ?></label>
<?php } else { // Need to set a default value ?>
   <input name="form_db_persistent" value="false" type="hidden" />
<?php } ?>
  </td></tr>

<?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
 <tr><td align="center" colspan="3">
  <?php 
    $class = ( ! empty ( $_SESSION['db_success'] ) ) ?
      'recommended' : 'notrecommended';
    echo "<input name=\"action\" type=\"submit\" value=\"Test Settings\" class=\"$class\" />\n";
  ?>
 <?php
     if ( ! empty ( $_SESSION['db_noexist'] ) &&  empty ( $_SESSION['db_success'] ) ){
       echo "<input name=\"action2\" type=\"submit\" value=\"" . 
      translate ( "Create New" ). "\" class=\"recommended\" />\n";
   } 
  ?>
</td></tr>
</table>
</form> 
</td></tr></table>

<?php } ?>

<table border="0" width="90%" align="center">
<tr><td align="right" width="40%">
  <form action="index.php?action=switch&amp;page=1" method="post">
    <input type="submit" value="<- <?php etranslate ( "Back" ) ?>" />
  </form>
</td><td align="center" width="20%">
  <form action="index.php?action=switch&amp;page=3" method="post">
    <input type="submit" value="<?php etranslate ( "Next" ) ?> ->" <?php echo ( ! empty ($_SESSION['db_success'] )? "" : "disabled" ); ?> />
  </form>
</td><td align="left" width="40%">
  <form action="" method="post">
 <input type="button" value="<?php etranslate ( "Logout" ) ?>" <?php echo ( ! empty ($_SESSION['validuser'] )? "" : "disabled" ); ?> 
  onclick="document.location.href='index.php?action=logout'" />
  </form>
</td></tr>
</table>

<?php } else if ( $_SESSION['step'] == 3 ) { ?>
<?php  
  $_SESSION['db_updated'] = false;
  if ( $_SESSION['old_program_version'] == $PROGRAM_VERSION  && 
   empty ( $_SESSION['blank_database'] ) ){
   $response_msg = translate ( "All your database tables appear to be up to date. You may proceed to the") . " " . 
       translate ( "next page and complete your WebCalendar setup" ) .".";
  $_SESSION['db_updated'] = true; 
  } else if ( $_SESSION['old_program_version'] == "new_install" ) {
   $response_msg = translate ( "This appears to be a new installation. If this is not correct, please") . " " .
      translate ("go back to the previous page and correct your settings" ) . ".";  
  } else if ( ! empty ( $_SESSION['blank_database'] ) ){
   $response_msg =translate ( "The database requires some data input" ) . ". " . 
      translate ( "Click <b>Update Database</b> to complete the upgrade" ) . ".";  
  } else {
     $response_msg = translate ( "This appears to be an upgrade from version")  . " " .  
       $_SESSION['old_program_version'] . translate ( "to" ) . " " .  $PROGRAM_VERSION. ".";
  }
?>
<table border="1" width="90%" align="center">
<tr><th class="pageheader" colspan="2"><?php echo translate ( "WebCalendar Installation Wizard" ) . 
 ": " . translate ( "Step" ) ?> 3</th></tr>
<tr><td colspan="2" width="50%">
<?php echo translate ( "In this section we will perform the required database changes to bring your database up to the required level" ) . " " .
  translate ( "If you are using a fully supported database, this step will be performed automatically for you" ) . " " . 
  translate ( "If not, the required SQL can be displayed and you should be able" ) . " " .
  translate ( "to cut &amp; paste it into your database server query window" ) ?>.
</td></tr>
<tr><th colspan="2" class="header"><?php etranslate ( "Database Status" ) ?></th></tr>
<tr><td>
<?php //print_r ( $_SESSION); ?>
<?php echo $response_msg; ?>
</td></tr>
<?php if ( ! empty ( $_SESSION['db_updated'] ) ){ ?>
<tr><th colspan="2" class="header"><?php etranslate ( "No database actions are required" ) ?></th></tr>
<?php } else { ?>
<tr><th colspan="2" class="redheader"><?php etranslate ( "The following database actions are required" ) ?></th></tr>
 <?php if ( $settings['db_type']  == "odbc" &&  empty ( $_SESSION['db_updated'] ) ) { ?>
 <?php  if ( empty ( $_SESSION['odbc_db'] ) ) $_SESSION['odbc_db'] = "mysql"; ?>
<tr><td id="odbc_db" align="center" nowrap>
<form action="index.php?action=set_odbc_db" method="post" name="set_odbc_db">
<b><?php etranslate ( "ODBC Underlying Database" ) ?>:</b> <select name="odbc_db"  onchange="document.set_odbc_db.submit();">
  <option value="mysql"
   <?php echo $_SESSION['odbc_db'] == "mysql"? " selected=\"selected\"" : "" ; ?> >MySQL</option>
  <option value="mssql"
   <?php echo $_SESSION['odbc_db'] == "mssql"? " selected=\"selected\"" : "" ; ?> >MS SQL</option>
  <option value="oracle"
   <?php echo $_SESSION['odbc_db'] == "oracle"? " selected=\"selected\"" : "" ; ?> >Oracle</option>
  <option value="postgresql"
  <?php echo $_SESSION['odbc_db'] == "postgresql"? " selected=\"selected\"" : "" ; ?> >PostgreSQL</option>
  <option value="ibase" 
  <?php echo $_SESSION['odbc_db'] == "ibase"? " selected=\"selected\"" : "" ; ?> >Interbase</option>
</select>
</form>
</td></tr>
  <?php } ?>
<tr>
  <td class="recommended" align="center">
 <?php if ( ! empty ( $settings['db_type'] ) && empty ( $_SESSION['blank_database'] ) &&
   ( $settings['db_type'] == "ibase" || $settings['db_type'] == "oracle" ) ) { ?>
 <?php etranslate ( "Automatic installation has not been fully implemented for your database type. You will
 have to manually create the required tables using the sql supplied. Please click
 <b>Display SQL</b> to continue. Cut &amp; Paste into your database query window" ) ?>. 
 <?php } else { ?>
  <?php etranslate ( "This may take several minutes to complete" ) ?>.
  <?php if ( $_SESSION['old_program_version'] == "new_install" &&
   empty ( $_SESSION['blank_database'] ) ){ ?>
   <form action="index.php?action=install" method="post">
      <input type="submit" value="<?php etranslate ( "Install Database" ) ?>" />
    </form>
  <?php } else {//We're doing an upgrade ?>
  <form action="index.php?action=install" method="post">
    <input type="hidden" name="install_file" value="<?php echo $_SESSION['install_file']; ?>" />
      <input type="submit" value="<?php etranslate ( "Update Database" ) ?>" />
    </form>
  <?php } ?>
 <?php } ?>
 </td></tr>
  <?php if ( ! empty ( $settings['db_type'] ) && $settings['db_type'] != "sqlite" &&
   empty ( $_SESSION['blank_database'] ) ) { ?>
 <tr><td align="center">
   <form action="index.php?action=install" method="post" name="display">
    <input type="hidden" name="install_file" value="<?php echo $_SESSION['install_file']; ?>" />
   <input type="hidden" name="display_sql" value="1" />
      <input type="submit" value="<?php etranslate ( "Display Required SQL" ) ?>" /><br />
 <?php if ( ! empty ( $str_parsed_sql ) ) { ?>
    <textarea name="displayed_sql" cols="100" rows="12" ><?php echo $str_parsed_sql; ?></textarea>
   <br />
      <p class="recommended"><?php etranslate ( "After manually processing this sql, you will need to return to the previous page and retest your database so that the script can detect the changes" ) ?>.</p>
 <?php } ?>
  </form>  
  </td></tr>
 <?php } ?> 
<?php } ?>
</table>
<table border="0" width="90%" align="center">
<tr><td align="right" width="40%">
  <form action="index.php?action=switch&amp;page=2" method="post">
    <input type="submit" value="<- <?php etranslate ( "Back" ) ?>" />
  </form>
</td><td align="center" width="20%">
  <form action="index.php?action=switch&amp;page=4" method="post">
    <input type="submit" value="<?php etranslate ( "Next" ) ?> ->" <?php echo ( empty ($_SESSION['db_updated'] )? "disabled" : "" ); ?> />
  </form>
</td><td align="left" width="40%">
  <form action="" method="post">
  <input type="button" value="<?php etranslate ( "Logout" ) ?>" <?php echo ( ! empty ($_SESSION['validuser'] )? "" : "disabled" ); ?>
   onclick="document.location.href='index.php?action=logout'" />
 </form>
</td></tr>
</table>
<?php } else if ( $_SESSION['step'] == 4 ) { ?>
 <table border="1" width="90%" align="center">
   <th class="pageheader" colspan="2"><?php echo translate ( "WebCalendar Installation Wizard" ) . 
    ": " . translate ( "Step" ) ?> 4</th>
   <tr><td colspan="2" width="50%">
     <?php etranslate ( "This is the final step in setting up your WebCalendar Installation" ) ?>.
   </td></tr>
  <th class="header" colspan="2"><?php etranslate ( "Timezone Conversion" ) ?></th></tr>
  <tr><td colspan="2">
 <?php if ( empty ( $_SESSION['tz_conversion'] ) ) {?>
   <form action="index.php?action=tz_convert" method="post">
  <ul><li>
<?php echo translate ( "It appears that you have" ) . " " . translate ("NOT"); ?> 
  <?php etranslate ( "converted your existing WebCalendar event data to GMT" ) ?>.
   <?php echo translate ( "If you have, you may ignore this notice and not proceed with the conversion" ) . " " .
    translate ( "If this is a new installation, you may also ignore this notice." ) ?> .
    </li></ul>
   <div align="center">
     <input  type="submit" value="<?php etranslate ( "Convert Data to GMT") ?>:"  /></div>
   </form>
 <?php } else if ( $_SESSION['tz_conversion'] == "Success" ) { ?>
    <ul><li><?php etranslate ( "Conversion Successful" ) ?></li></ul>
 <?php } else if ( $_SESSION['tz_conversion'] == "Y" ) { ?>
    <ul><li><?php etranslate ( "Conversion Not Needed" ) ?></li></ul> <?php } ?>
 </td></tr>
 <th class="header" colspan="2"><?php etranslate ( "Application Settings" ) ?></th>
 <tr><td colspan="2"><ul>
  <?php if ( empty ( $PHP_AUTH_USER ) ) { ?>
   <li><?php echo translate ( "HTTP-based authentication was not detected" ) . ". " .
     translate ( "You will need to reconfigure your web server if you wish to" ) . " " .
     translate ( "select 'Web Server' from the 'User Authentication' choices below" ) ?>.
   </li>
  <?php } else { ?>
   <li><?php echo translate ( "HTTP-based authentication was detected" ) . ". " .
     translate ( "User authentication is being handled by your web server" ) . ". " .
     translate ( "You should select 'Web Server' from the list of 'User Authentication' choices below" ) ?>.
   </li>
  <?php } ?>
 </ul></td></tr>

   <tr><td>
 <?php $will_load_admin = ( ( $_SESSION['old_program_version'] == "new_install" )? "checked=\"checked\"":""); ?>
  <table width="75%" align="center" border="0"><tr>
  <form action="index.php?action=switch&amp;page=4" method="post" name="form_app_settings">
    <input type="hidden" name="app_settings"  value="1"/>
    <td class="prompt"><?php etranslate ( "Create Default Admin Account" ) ?>:</td>
    <td><input type="checkbox" name="load_admin" value="Yes" <?php echo $will_load_admin ?> /></td></tr>
    <tr><td class="prompt"><?php etranslate ( "Application Name" ) ?>:</td>
   <td>   
     <input type="text" size="40" name="form_application_name" id="form_application_name" value="<?php 
           echo $_SESSION['application_name'];?>" /></td></tr>
     <tr><td class="prompt"><?php etranslate ( "Server URL" ) ?>:</td>
   <td>   
     <input type="text" size="40" name="form_server_url" id="form_server_url" value="<?php 
           echo $_SESSION['server_url'];?>" /></td></tr>     
      
   <tr><td class="prompt"><?php etranslate ( "User Authentication" ) ?>:</td>
   <td>
    <select name="form_user_inc" onChange="auth_handler()">
  <?php
   echo "<option value=\"user.php\" " .
    ( $settings['user_inc'] == 'user.php' && $settings['use_http_auth'] != 'true' ? " selected=\"selected\"" : "" ) .
    ">". translate ( "Web-based via WebCalendar (default)" ) . "</option>\n";
  
   echo "<option value=\"http\" " .
    ( $settings['user_inc'] == 'user.php' && $settings['use_http_auth'] == 'true' ? " selected=\"selected\"" : "" ) .
    ">" . translate ( "Web Server" ) .
    ( empty ( $PHP_AUTH_USER ) ? "(not detected)" : "(detected)" ) .
    "</option>\n";
  
   if ( function_exists ( "ldap_connect" ) ) {
    echo "<option value=\"user-ldap.php\" " .
     ( $settings['user_inc'] == 'user-ldap.php' ? " selected=\"selected\"" : "" ) .
     ">LDAP</option>\n";
   }
  
   if ( function_exists ( "yp_match" ) ) {
    echo "<option value=\"user-nis.php\" " .
     ( $settings['user_inc'] == 'user-nis.php' ? " selected=\"selected\"" : "" ) .
     ">NIS</option>\n";
   }

   echo "<option value=\"user-imap.php\" " .
     ( $settings['user_inc'] == 'user-imap.php' ? " selected=\"selected\"" : "" ) .
     ">IMAP</option>\n"; 
      
   echo "<option value=\"none\" " .
    ( $settings['user_inc'] == 'user.php' && $settings['single_user'] == 'true' ? " selected=\"selected\"" : "" ) .
    ">" . translate ( "None (Single-User)" ) . "</option>\n</select>";
  ?>
    </td>
   </tr>
   <tr id="singleuser">
    <td class="prompt">&nbsp;&nbsp;&nbsp;Single-User Login:</td>
    <td>
     <input name="form_single_user_login" size="20" value="<?php echo $settings['single_user_login'];?>" /></td>
   </tr>
   <tr>
    <td class="prompt"><?php etranslate ( "Read-Only" ) ?>:</td>
    <td>
     <input name="form_readonly" value="true" type="radio"
 <?php echo ( $settings['readonly'] == 'true' )? " checked=\"checked\"" : "";?> /><?php etranslate ("Yes" ) ?>
 &nbsp;&nbsp;&nbsp;&nbsp;
 <input name="form_readonly" value="false" type="radio"
 <?php echo ( $settings['readonly'] != 'true' )? " checked=\"checked\"" : "";?> /><?php etranslate ("No" ) ?>
     </td>
    </tr>
   <tr>
    <td class="prompt"><?php etranslate ( "Environment" ) ?></td>
    <td>
     <select name="form_mode">
     <?php if ( preg_match ( "/dev/", $settings['mode'] ) )
         $mode = 'dev'; // development
        else
         $mode = 'prod'; //production
     ?>
     <option value="prod" <?php if ( $mode == 'prod' ) echo 'selected="selected"'; echo ">" . translate ( "Production" ) ?></option>
     <option value="dev" <?php if ( $mode == 'dev' ) echo 'selected="selected"'; echo ">" . translate ( "Development" ) ?></option>
     </select>
     </td>
    </tr>
  </table>
 </td></tr>
 <table width="80%"  align="center">
 <tr><td align="center">
  <?php if ( ! empty ( $_SESSION['db_success'] ) && $_SESSION['db_success']  && empty ( $dologin ) ) { ?>
  <input name="action" type="button" value="<?php etranslate ( "Save Settings" ) ?>" onClick="return validate();" />
   <?php if ( ! empty ( $_SESSION['old_program_version'] ) && 
    $_SESSION['old_program_version'] == $PROGRAM_VERSION  && ! empty ( $setup_complete )) { ?>
    <input type="button"  name="action2" value="<?php etranslate ( "Launch WebCalendar" ) ?>" onClick="window.open('../index.php', 'webcalendar');" />
   <?php } ?>
  <?php } ?>
  <?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
  <input type="button" value="<?php etranslate ( "Logout" ) ?>"
   onclick="document.location.href='index.php?action=logout'" />
  <?php } ?>
 </form>
 </td></tr></table>
<?php } ?>

</body>
</html>
