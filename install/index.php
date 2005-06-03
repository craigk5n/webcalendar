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
 * Add translations to this page.
 */
include_once '../includes/php-dbi.php';

$file = "../includes/settings.php";
$fileDir = "../includes";

// Get value from POST form
function getPostValue ( $name ) {
  if ( ! empty ( $_POST[$name] ) ) {
    return $_POST[$name];
  }
  if ( ! isset ( $HTTP_POST_VARS ) ) {
    return null;
  }
  if ( ! isset ( $HTTP_POST_VARS[$name] ) ) {
    return null;
  }
  return ( $HTTP_POST_VARS[$name] );
}


// Get value from GET form
function getGetValue ( $name ) {
  if ( ! empty ( $_GET[$name] ) ) {
    return $_GET[$name];
  }
  if ( ! isset ( $HTTP_GET_VARS ) ) {
    return null;
  }
  if ( ! isset ( $HTTP_GET_VARS[$name] ) ) {
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





// First pass at settings.php.
// We need to read it first in order to get the md5 password.
$fd = @fopen ( $file, "rb", true );
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

session_start ();
$doLogin = false;

// Handle "Logout" button
if ( 'logout' == getGetValue ( 'action' ) ) {
  session_destroy ();
  Header ( "Location: index.php" );
  exit;
}

// If password already exists, check for session.
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
      <body onload="alert('Successful Login');">
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
      <body onload="alert ('Invalid Login'); document.go(-1)">
      </body></html>
    <?php
    exit;
  }
}

$onload = "auth_handler (); ";

$pwd1 = getPostValue ( "password1" );
$pwd2 = getPostValue ( "password2" );
if ( file_exists ( $file ) && $forcePassword && ! empty ( $pwd1 ) ) {
  if ( $pwd1 != $pwd2 ) {
    echo "Passwords do not match!<br/>\n";
    exit;
  }
  $fd = fopen ( $file, "a+b", true );
  if ( empty ( $fd ) ) {
    echo "<html><body>Unable to write password to settings.php file\n" .
      "</body></html>";
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
    <body onload="alert('Password has been set');">
    </body></html>
  <?php
  exit;
}


// Is this a db connection test?
// If so, just test the connection, show the result and exit.
$action = getGetValue ( "action" );
if ( ! empty ( $action ) && $action == "dbtest" ) {
  if ( ! empty ( $_SESSION['validuser'] ) ) {
    $db_persistent = false;
    $db_type = getGetValue ( 'db_type' );
    $db_host = getGetValue ( 'db_host' );
    $db_database = getGetValue ( 'db_database' );
    $db_login = getGetValue ( 'db_login' );
    $db_password = getGetValue ( 'db_password' );

    echo "<html><head><title>WebCalendar: Db Connection Test</title>\n" .
      "</head><body style=\"background-color: #fff;\">\n";
    echo "<p><b>Connection Result:</b></p><blockquote>";

    $c = dbi_connect ( $db_host, $db_login,
      $db_password, $db_database );

    if ( $c ) {
      echo "<span style=\"color: #0f0;\">Success</span></blockquote>";
      $_SESSION['db_success'] = true;
      // TODO: update the text in the main window to indicate success
    } else {
      echo "<span style=\"color: #0f0;\">Failure</span</blockquote>";
      echo "<br/><br/><b>Reason:</b><blockquote>" . dbi_error () .
        "</blockquote>\n";
    }
    echo "<br/><br/><br/><div align=\"center\"><form><input align=\"middle\" type=\"button\" onclick=\"window.close()\" value=\"Close\" /></form></div>\n";
    echo "</p>";
    echo "<script language=\"JavaScript\" type=\"text/javascript\">\n";
    echo "<!-- <![CDATA[\n";
    echo "window.opener.show_db_status ( " .
      ( $c ? "true" : "false" ) . " );\n";
    echo "//]]> -->\n</script>\n";
    echo "</body></html>\n";
  } else { // Not valid user
    echo "You are not authorized.";
    // etranslate ( "You are not authorized" );  
  }
  exit;
}

// Is this a call to phpinfo()?
$action = getGetValue ( "action" );
if ( ! empty ( $action ) && $action == "phpinfo" ) {
  if ( ! empty ( $_SESSION['validuser'] ) ) {
    phpinfo();
  } else {
    echo "You are not authorized.";
    // etranslate ( "You are not authorized" );
  }
  exit;
}


$exists = file_exists ( $file );
$canWrite = false;
if ( $exists ) {
  $canWrite = is_writable ( $file );
} else {
  // check to see if we can create a new file.
  $testFile = $fileDir . "/installTest.dat";
  $testFd = @fopen ( $testFile, "w+b", true );
  if ( file_exists ( $testFile ) ) {
    $canWrite = true;
  }
  @unlink ( $testFile );
}



// If we are handling a form POST, then take that data and put it in settings
// array.
$x = getPostValue ( "form_db_type" );
if ( empty ( $x ) ) {
  // No form was posted.  Set defaults if none set yet.
  if ( ! file_exists ( $file ) ) {
    $settings['db_type'] = 'mysql';
    $settings['db_host'] = 'localhost';
    $settings['db_database'] = 'intranet';
    $settings['db_login'] = 'webcalendar';
    $settings['db_password'] = 'webcal01';
    $settings['db_persistent'] = 'true';
    $settings['readonly'] = 'false';
    $settings['user_inc'] = 'user.php';
    $settings['install_password'] = '';
    $settings['single_user_login'] = '';
    $settings['use_http_auth'] = 'false';
    $settings['single_user'] = 'false';
    $settings['user_inc'] = 'user.php';
  }
} else {
  $settings['db_type'] = getPostValue ( 'form_db_type' );
  $settings['db_host'] = getPostValue ( 'form_db_host' );
  $settings['db_database'] = getPostValue ( 'form_db_database' );
  $settings['db_login'] = getPostValue ( 'form_db_login' );
  $settings['db_password'] = getPostValue ( 'form_db_password' );
  $settings['db_persistent'] = getPostValue ( 'form_db_persistent' );
  $settings['single_user_login'] = getPostValue ( 'form_single_user_login' );
  $settings['readonly'] = getPostValue ( 'form_readonly' );
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
  // Save settings to file now.
  if ( empty ( $password ) ) {
    $onload = "alert('Your settings have been saved.\\n\\n" .
      "Please be sure to set a password.\\n');";
    $forcePassword = true;
  } else {
    $onload .= "alert('Your settings have been saved.\\n\\n');";
  }
  $fd = @fopen ( $file, "w+b", true );
  if ( empty ( $fd ) ) {
    if ( file_exists ( $file ) ) {
      $onload = "alert('Error: unable to write to file $file\\nPlease change the file permissions of this file.');";
    } else {
      $onload = "alert('Error: unable to write to file $file\\nPlease change the file permissions of your includes directory\\nto allow writing by other users.');";
    }
  } else {
    fwrite ( $fd, "<?php\r\n" );
    fwrite ( $fd, "# updated via install/index.php on " . date("r") . "\r\n" );
    foreach ( $settings as $k => $v ) {
      fwrite ( $fd, $k . ": " . $v . "\r\n" );
    }
    fwrite ( $fd, "# end settings.php\r\n?>\r\n" );
    fclose ( $fd );
    // Change to read/write by us only (only applies if we created file)
    // and read-only by all others.  Would be nice to make it 600, but
    // the send_reminders.php script is usually run under a different
    // user than the web server.
    @chmod ( $file, 0644 );
  }
}


$fd = @fopen ( $file, "rb", true );
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
// echo $matches[1] . " " .  $matches[2] . "<br>";
      $settings[$matches[1]] = $matches[2];
      //echo "settings $matches[1] => $matches[2] <br>";
    }
  }
  fclose ( $fd );
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head><title>WebCalendar Database Setup</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php include "../includes/js/visible.php"; ?>
<script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
<?php   if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
function testSettings () {
  var url;
  var form = document.dbform;
  url = "index.php?action=dbtest" +
    "&db_type=" + form.form_db_type.value +
    "&db_host=" + form.form_db_host.value +
    "&db_database=" + form.form_db_database.value +
    "&db_login=" + form.form_db_login.value +
    "&db_password=" + form.form_db_password.value;
  //alert ( "URL:\n" + url );
  window.open ( url, "wcDbTest", "width=400,height=350,resizable=yes,scrollbars=yes" );
}
function testPHPInfo () {
  var url;
  var form = document.phpinfo;
  url = "index.php?action=phpinfo";
  //alert ( "URL:\n" + url );
  window.open ( url, "wcTestPHPInfo", "width=800,height=600,resizable=yes,scrollbars=yes" );
}
<?php } ?>
function validate(form)
{
  var form = document.dbform;
  // only check is to make sure single-user login is specified if
  // in single-user mode
  if ( form.form_user_inc.options[4].selected ) {
    if ( form.form_single_user_login.value.length == 0 ) {
      // No single user login specified
      alert ( "Error: you must specify a\nSingle-User Login" );
      form.form_single_user_login.focus ();
      return false;
    }
  }
  // Submit form...
  form.submit ();
}
function auth_handler () {
  var form = document.dbform;
  if ( form.form_user_inc.options[4].selected ) {
    makeVisible ( "singleuser" );
  } else {
    makeInvisible ( "singleuser" );
  }
}

function show_db_status ( success ) {
  if ( success ) {
    makeVisible ( "db_success" );
    makeInvisible ( "no_db_success" );
  } else {
    makeInvisible ( "db_success" );
    makeVisible ( "no_db_success" );
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
  border: 1px solid #ccc;
}
th.header {
  font-size: 18px;
  background-color: #eee;
}
td {
  padding: 5px;
}
td.prompt {
  font-weight: bold;
  padding-right: 20px;
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
<body onload="<?php echo $onload;?>">
<?php
/* other features coming soon.... 
<div class="nav">
<table border="0" width="100%">
<tr>
<td>&lt;&lt;<b>Database Setup</b>&gt;&gt;</td>
<td>&lt;&lt;<a href="setup.php">Setup Wizard</a>&gt;&gt;</td>
<td>&lt;&lt;<a href="diag.php">Diagnostics</a>&gt;&gt;</td>
</tr></table>
</div>
*/
?>
<div class="main">
<h2>WebCalendar Database Setup</h2>

<p><b>Current Status:</b></p>
<ul>

<li>Supported databases for your PHP installation:
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
  for ( $i = 0; $i < count ( $dbs ); $i++ ) {
    if ( $i ) echo ", ";
    echo $dbs[$i];
    $supported[$dbs[$i]] = true;
  }
?>
</li>
<?php if ( ! empty ( $_SESSION['db_success'] ) && $_SESSION['db_success']  ) { ?>
<li id="db_success"> Your current database settings are able to
  access the database.</li>
<li id="no_db_success" style="visibility: hidden;"> Your current database settings are <b>not</b> able to
  access the database or have not yet been tested.</li>
<?php } else { ?>
<li id="no_db_success"> Your current database settings are <b>not</b> able to
  access the database or have not yet been tested.</li>
<li id="db_success" style="visibility: hidden;"> Your current database settings are able to
  access the database.</li>
<?php } ?>
<?php if ( empty ( $password ) ) { ?>
  <li> You have not set a password for this page. </li>
<?php } ?>
<?php if ( $exists && ! $canWrite ) { ?>
<li><b>Error:</b>
The file permissions of <tt>settings.php</tt> are set so
that this script does not have permission to write changes to it.
You must change the file permissions of the following
file to use this script:
<blockquote><tt>
<?php echo realpath ( $file ); ?>
</tt></blockquote>
</li>
<?php } else if ( ! $exists && ! $canWrite ) { ?>
<li><b>Error:</b>
The file permissions of the <tt>includes</tt> directory are set so
that this script does not have permission to create a new file
in that directory.
You must change the permissions of the follwing directory
to use this script:
<blockquote><tt>
<?php echo realpath ( $fileDir ); ?>
</tt></blockquote>
</li>
<?php } else { ?>
  <?php if ( ! file_exists ( $file ) ) { ?>
  <li>You have not created a <tt>settings.php</tt> file yet.</li>
  <?php } ?>
<?php if ( empty ( $PHP_AUTH_USER ) ) { ?>
<li>HTTP-based authentication was not detected.
You will need to reconfigure your web server if you wish to
select "Web Server" from the "User Authentication" choices below.
</li>
<?php } else { ?>
<li>HTTP-based authentication was detected.
User authentication is being handled by your web server.
You should select "Web Server" from the list of
"User Authentication " choices below.
</li>
<?php } ?>

</ul>

<table>  <tr><td valign="top">
<?php if ( $doLogin ) { ?>
  <form action="index.php" method="post" name="dblogin">

  <p>Please enter the password.</p>
    <br /><br />
  </p>
  <table >
  <tr><th colspan="2" class="header">Enter Password</th></tr>
  <tr><th>Password:</th><td><input name="password" type="password" /></td></tr>
  <tr><td colspan="2" align="center"><input type="submit" value="Login" /></td></tr>
  </table><br />
  </form>
  </td></tr></table>
<?php } else if ( $forcePassword ) { ?>
  <form action="index.php" method="post" name="dbpassword">
  <p>You have not set a password for access to this page yet.
     Please set the password.
    <br /><br />
  </p>
  <table border="0">
  <tr><th colspan="2" class="header">Create Password</th></tr>
  <tr><th>Password:</th><td><input name="password1" type="password" /></td></tr>
  <tr><th>Password (again):</th><td><input name="password2" type="password" /></td></tr>
  <tr><td colspan="2" align="center"><input type="submit" value="Set Password" /></td></tr>
  </table><br />
  </form>
  </td></tr></table>
<?php } else { ?>
<form action="index.php" method="post" name="dbform">

<table>
<tr><th class="header" colspan="2">Database Settings</th></tr>

<tr><td class="prompt">Database Type:</td>
<td>
<select name="form_db_type">
<?php
  if ( ! empty ( $supported['mysql'] ) )
    echo "<option value=\"mysql\" " .
      ( $settings['db_type'] == 'mysql' ? " selected=\"selected\"" : "" ) .
      "> MySQL </option>\n";
      
  if ( ! empty ( $supported['mysqli'] ) )
    echo "<option value=\"mysqli\" " .
      ( $settings['db_type'] == 'mysqli' ? " selected=\"selected\"" : "" ) .
      "> MySQL (Improved)</option>\n";

  if ( ! empty ( $supported['oracle'] ) )
    echo "<option value=\"oracle\" " .
      ( $settings['db_type'] == 'oracle' ? " selected=\"selected\"" : "" ) .
      "> Oracle (OCI) </option>\n";

  if ( ! empty ( $supported['postgresql'] ) )
    echo "<option value=\"postgresql\" " .
      ( $settings['db_type'] == 'postgresql' ? " selected=\"selected\"" : "" ) .
      "> PostgreSQL </option>\n";

  if ( ! empty ( $supported['odbc'] ) )
    echo "<option value=\"odbc\" " .
      ( $settings['db_type'] == 'odbc' ? " selected=\"selected\"" : "" ) .
      "> ODBC </option>\n";

  if ( ! empty ( $supported['ibase'] ) )
    echo "<option value=\"ibase\" " .
      ( $settings['db_type'] == 'ibase' ? " selected=\"selected\"" : "" ) .
      "> Interbase </option>\n";

  if ( ! empty ( $supported['mssql'] ) )
    echo "<option value=\"mssql\" " .
      ( $settings['db_type'] == 'mssql' ? " selected=\"selected\"" : "" ) .
      "> MS SQL Server </option>\n";
?>
</select>
</td></tr>

<tr><td class="prompt">Server:</td>
<td><input name="form_db_host" size="20" value="<?php echo $settings['db_host'];?>" /></td></tr>

<tr><td class="prompt">Database Name:</td>
<td><input name="form_db_database" size="20" value="<?php echo $settings['db_database'];?>" /></td></tr>

<tr><td class="prompt">Login:</td>
<td><input name="form_db_login" size="20" value="<?php echo $settings['db_login'];?>" /></td></tr>

<tr><td class="prompt">Password:</td>
<td><input name="form_db_password" size="20" value="<?php echo $settings['db_password'];?>" /></td></tr>

<tr><td class="prompt">Connection Persistence:</td>
<td><input name="form_db_persistent" value="true" type="radio"
  <?php echo ( $settings['db_persistent'] == 'true' )? " checked=\"checked\"" : "";
  ?> />Enabled
  &nbsp;&nbsp;&nbsp;&nbsp;
  <input name="form_db_persistent" value="false" type="radio"
  <?php echo ( $settings['db_persistent'] != 'true' )? " checked=\"checked\"" : "";
  ?> />Disabled
  </td></tr>
<?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
<tr><td colspan="2" align="center">
<input name="action" type="button" value="Test Settings"
  onclick="testSettings()" />
</td></tr>
<?php } else { ?>
<tr><th class="header" colspan="2">
<p>You must save before proceeding.</p>
</th></tr>
<?php } ?>
</table>

</td>
<td valign="top">
  <table width="100%">
    <tr>
      <th class="header" colspan="2" >Application Settings</th>
    </tr>
    <tr>
      <td class="prompt">User Authentication:</td>
      <td>
        <select name="form_user_inc" onchange="auth_handler()">
<?php
  echo "<option value=\"user.php\" " .
    ( $settings['user_inc'] == 'user.php' && $settings['use_http_auth'] != 'true' ? " selected=\"selected\"" : "" ) .
    "> Web-based via WebCalendar (default) </option>\n";

  echo "<option value=\"http\" " .
    ( $settings['user_inc'] == 'user.php' && $settings['use_http_auth'] == 'true' ? " selected=\"selected\"" : "" ) .
    "> Web Server " .
    ( empty ( $PHP_AUTH_USER ) ? "(not detected)" : "(detected)" ) .
    "</option>\n";

  echo "<option value=\"user-ldap.php\" " .
    ( $settings['user_inc'] == 'user-ldap.php' ? " selected=\"selected\"" : "" ) .
    "> LDAP </option>\n";

  echo "<option value=\"user-nis.php\" " .
    ( $settings['user_inc'] == 'user-nis.php' ? " selected=\"selected\"" : "" ) .
    "> NIS </option>\n";

  echo "<option value=\"none\" " .
    ( $settings['user_inc'] == 'user.php' && $settings['single_user'] == 'true' ? " selected=\"selected\"" : "" ) .
    "> None (Single-User) </option>\n</select>";
?>
        </td>
      </tr>
      <tr id="singleuser">
        <td class="prompt">&nbsp;&nbsp;&nbsp;Single-User Login:</td>
        <td>
          <input name="form_single_user_login" size="20" value="<?php echo $settings['single_user_login'];?>" /></td>
      </tr>
      <tr>
        <td class="prompt">Read-Only:</td>
        <td>
          <input name="form_readonly" value="true" type="radio"
  <?php echo ( $settings['readonly'] == 'true' )? " checked=\"checked\"" : "";?> />Yes
  &nbsp;&nbsp;&nbsp;&nbsp;
  <input name="form_readonly" value="false" type="radio"
  <?php echo ( $settings['readonly'] != 'true' )? " checked=\"checked\"" : "";?> />No
         </td>
       </tr>
    </table>
<?php

$php_settings = array (
  //array ('Safe Mode','safe_mode','OFF'),
  array ('Magic Quotes GPC','magic_quotes_gpc','ON'),
  array ('Register Globals','register_globals','ON'),
  array ('Display Errors','display_errors','ON'),
  //array ('Register Globals','register_globals','OFF'),
  array ('File Uploads','file_uploads','ON'),
);

?>
<table width="100%">
<tr><th class="header"  colspan="2">PHP Settings</th></tr>
<?php foreach ( $php_settings as $setting ) { ?>
  <tr><td class="prompt"><?php echo $setting[0];?></td>
  <?php
    $class = ( get_php_setting ( $setting[1] ) == $setting[2] ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    echo get_php_setting ( $setting[1] );
   ?>
   </td></tr>
<?php } ?>

<?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
<tr><td  align="center" colspan="2"><input name="action" type="button" value="Detailed PHP Info"
  onclick="testPHPInfo()" /></td></tr>
<?php } ?>


</table></td></tr>

<tr><td align="center" colspan="2">
<input name="action" type="button" value="Save Settings"
  onclick="return validate();" />
  <?php if ( ! empty ( $_SESSION['db_success'] ) && $_SESSION['db_success']  && empty ( $dologin ) ) { ?>
    <input type="button" value="Launch WebCalendar"
      onclick="window.open('../index.php', 'webcalendar');" />
  <?php } ?>
  <?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
    <input type="button" value="Logout"
      onclick="document.location.href='index.php?action=logout'" />
  <?php } ?>
</form>
</td></tr></table>
<?php } ?>

<?php } ?>
</div>
<div class="main">
<p>
<b>Documentation:</b>
</p>
<ul>
<li class="doc"><a href="../docs/WebCalendar-SysAdmin.html" target="_docs">System Administrator's Guide</a> (Installation Instructions) </li>
<li class="doc"><a href="../docs/WebCalendar-SysAdmin.html#faq" target="_docs">FAQ</a> </li>
<li class="doc"><a href="../docs/WebCalendar-SysAdmin.html#trouble" target="_docs">Troubleshooting</a> </li>
<li class="doc"><a href="../docs/WebCalendar-SysAdmin.html#help" target="_docs">Getting Help</a> </li>
</ul>
</div>
</body>
</html>
