<?php

if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}

// Global variables for activity log
$LOG_CREATE = "C";
$LOG_APPROVE = "A";
$LOG_REJECT = "X";
$LOG_UPDATE = "U";
$LOG_DELETE = "D";
$LOG_NOTIFICATION = "N";
$LOG_REMINDER = "R";

$ONE_DAY = 86400;

// how many days in a month (regular and leap year)
$days_per_month = array ( 0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
$ldays_per_month = array ( 0, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

// List global variables that will not be allowed to be set via HTTP GET/POST
// This is a security precaution to prevent users from overriding any
// global variables.
$noSet = array (
  "is_admin" => 1,
  "db_type" => 1,
  "db_host" => 1,
  "db_login" => 1,
  "db_password" => 1,
  "db_persistent" => 1,
  "PROGRAM_NAME" => 1,
  "PROGRAM_URL" => 1,
  "readonly" => 1,
  "single_user" => 1,
  "single_user_login" => 1,
  "use_http_auth" => 1,
  "user_inc" => 1,
  "NONUSER_PREFIX" => 1,
  "languages" => 1,
  "browser_languages" => 1,
  "pub_acc_enabled" => 1,
  "user_can_update_password" => 1,
  "admin_can_add_user" => 1,
  "admin_can_delete_user" => 1,
);

// This code is a temporary hack to make the application work when
// register_globals is set to Off in php.ini (the default setting in
// PHP 4.2.0 and after).
if ( ! empty ( $HTTP_GET_VARS ) ) {
  while (list($key, $val) = @each($HTTP_GET_VARS)) {
    // don't allow anything to have <script> in it...
    if ( ! is_array ( $val ) ) {
      if ( preg_match ( "/<\s*script/i", $val ) ) {
        echo "Security violation!"; exit;
      }
    }
    if ( $key == "login" ) {
      if ( strstr ( $PHP_SELF, "login.php" ) ) {
        //$GLOBALS[$key] = $val;
        $GLOBALS[$key] = $val;
      }
    } else {
      if ( empty ( $noSet[$key] ) ) {
        $GLOBALS[$key] = $val;
        //echo "XXX $key<br />\n";
      }
    }
    //echo "GET var '$key' = '$val' <br />\n";
  }
  reset ( $HTTP_GET_VARS );
}
if ( ! empty ( $HTTP_POST_VARS ) ) {
  while (list($key, $val) = @each($HTTP_POST_VARS)) {
    // don't allow anything to have <script> in it... except 'template'
    if ( ! is_array ( $val ) && $key != 'template' ) {
      if ( preg_match ( "/<\s*script/i", $val ) ) {
        echo "Security violation!"; exit;
      }
    }
    if ( empty ( $noSet[$key] ) ) {
      $GLOBALS[$key] = $val;
    }
  }
  reset ( $HTTP_POST_VARS );
}
//while (list($key, $val) = @each($HTTP_POST_FILES)) {
//       $GLOBALS[$key] = $val;
//}
//while (list($key, $val) = @each($HTTP_SESSION_VARS)) {
//       $GLOBALS[$key] = $val;
//}
if ( ! empty ( $HTTP_COOKIE_VARS ) ) {
  while (list($key, $val) = @each($HTTP_COOKIE_VARS)) {
    if ( empty ( $noSet[$key] ) && substr($key,0,12) == "webcalendar_" ) {
      $GLOBALS[$key] = $val;
    }
    //echo "COOKIE var '$key' = '$val' <br />\n";
  }
  reset ( $HTTP_COOKIE_VARS );
}

// Don't allow a user to put "login=XXX" in the URL if they are not
// coming from the login.php page.
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER['PHP_SELF'] ) )
  $PHP_SELF = $_SERVER['PHP_SELF']; // backward compatibility
if ( ! strstr ( $PHP_SELF, "login.php" ) && ! empty ( $GLOBALS["login"] ) ) {
  $GLOBALS["login"] = "";
}

// Define an array to use to jumble up the key: $offsets
// We define a unique key to scramble the cookie we generate.
// We use the admin install password that the user set to make
// the salt unique for each WebCalendar install.
if ( ! empty ( $settings ) && ! empty ( $settings['install_password'] ) ) {
  $salt = $settings['install_password'];
} else {
  $salt = md5 ( $db_login );
}
$salt_len = strlen ( $salt );

if ( ! empty ( $db_password ) ) {
  $salt2 = md5 ( $db_password );
} else {
  $salt2 = md5 ( "oogabooga" );
}
$salt2_len = strlen ( $salt2 );

$offsets = array ();
for ( $i = 0; $i < $salt_len || $i < $salt2_len; $i++ ) {
  $offsets[$i] = 0;
  if ( $i < $salt_len )
    $offsets[$i] += ord ( substr ( $salt, $i, 1 ) );
  if ( $i < $salt2_len )
    $offsets[$i] += ord ( substr ( $salt2, $i, 1 ) );
  $offsets[$i] %= 128;
}
/* debugging code...
for ( $i = 0; $i < count ( $offsets ); $i++ ) {
  echo "offset $i: $offsets[$i] <br />\n";
}
*/

/*
 * Functions start here.  All non-function code should be above this
 *
 * Note to developers:
 *	Documentation is generated from the function comments below.
 *	When adding/updating functions, please follow the following conventions
 *	seen below.  Your cooperation in this matter is appreciated :-)
 *
 *	If you want your documentation to link to the db documentation,
 *	just make sure you mention the db table name followed by "table"
 *	on the same line.  Here's an example:
 *		Retrieve preferences from the webcal_user_pref table.
 *	The same can be done to link to other functions:
 *		Use the getIntValue function to obtain integer values.
 *
 */

/** getPostValue
  * Description:
  *	Get the value resulting from an HTTP POST method.   <br/>
  *	Note: The return value will be affected by the value of
  *	<tt>magic_quotes_gpc</tt> in the <tt>php.ini</tt> file.
  * Parameters:
  *	$name - Name used in the HTML form
  * Returns:
  *	the value used in the HTML form
  */
function getPostValue ( $name ) {
  global $HTTP_POST_VARS;

  if ( isset ( $_POST ) && is_array ( $_POST ) && ! empty ( $_POST[$name] ) )
    return $_POST[$name];
  if ( ! isset ( $HTTP_POST_VARS ) )
    return null;
  if ( ! isset ( $HTTP_POST_VARS[$name] ) )
    return null;
  return ( $HTTP_POST_VARS[$name] );
}

/** getGetValue
  * Description:
  *	Get the value resulting from an HTTP GET method.   <br/>
  *	Note: The return value will be affected by the value of
  *	<tt>magic_quotes_gpc</tt> in the <tt>php.ini</tt> file.
  *	If you need to enforce a specific input format (such
  *	as numeric input), then use the
  *	getValue function.
  * Parameters:
  *	$name - Name used in the HTML form or found in the URL
  * Returns:
  *	The value used in the HTML form (or URL)
  */
function getGetValue ( $name ) {
  global $HTTP_GET_VARS;

  if ( isset ( $_GET ) && is_array ( $_GET ) && ! empty ( $_GET[$name] ) )
    return $_GET[$name];
  if ( ! isset ( $HTTP_GET_VARS ) )
    return null;
  if ( ! isset ( $HTTP_GET_VARS[$name] ) )
    return null;
  return ( $HTTP_GET_VARS[$name] );
}

/** getValue
  * Description:
  *	Get the value resulting from either HTTP GET method or
  *	HTTP POST method.  <br />
  *	Note: The return value will be affected by the value of
  *	<tt>magic_quotes_gpc</tt> in the <tt>php.ini</tt> file.  <br />
  *	Note: If you need to get an integer value, yuou can use the
  *	getIntValue function.
  * Parameters:
  *	$name - Name used in the HTML form or found in the URL
  *	$format - A regular expression format that the input must
  *	  match.  If the input does not match, an empty string is
  *	  returned and a warning is sent to the browser.  If
  *	  The $fatal parameter is true, then execution will also stop
  *	  when the input does not match the format.
  *	$fatal - Is it considered a fatal error requiring execution to
  *	  stop if the value retrieved does not match the format
  *	  regular expression?
  * Returns:
  *	The value used in the HTML form (or URL)
  */
function getValue ( $name, $format="", $fatal=false ) {
  $val = getPostValue ( $name );
  if ( ! isset ( $val ) )
    $val = getGetValue ( $name );
  // for older PHP versions...
  if ( ! isset ( $val  ) && get_magic_quotes_gpc () == 1 &&
    ! empty ( $GLOBALS[$name] ) )
    $val = $GLOBALS[$name];
  if ( ! isset ( $val  ) )
    return "";
  if ( ! empty ( $format ) && ! preg_match ( "/^" . $format . "$/", $val ) ) {
    // does not match
    if ( $fatal ) {
      die_miserable_death ( "Fatal Error: Invalid data format for $name" );
    }
    // ignore value
    return "";
  }
  return $val;
}

/** getIntValue
  * Description:
  *	Get an integer value resulting from an HTTP GET or
  *	HTTP POST method.   <br/>
  *	Note: The return value will be affected by the value of
  *	<tt>magic_quotes_gpc</tt> in the <tt>php.ini</tt> file.
  * Parameters:
  *	$name - Name used in the HTML form or found in the URL
  *	$fatal - Is it considered a fatal error requiring execution to
  *	  stop if the value retrieved does not match the format
  *	  regular expression?
  * Returns:
  *	The value used in the HTML form (or URL)
  */
function getIntValue ( $name, $fatal=false ) {
  $val = getValue ( $name, "-?[0-9]+", $fatal );
  return $val;
}

/** load_global_settings
  * Description:
  *	Load default system settings (which can be updated via admin.php)
  *	System settings are stored in the webcal_config table. <br/>
  *	Note: If the setting for "server_url" is not set, the value will
  *	be calculated and stored in the database.
  */
function load_global_settings () {
  global $login, $readonly;
  global $HTTP_HOST, $SERVER_PORT, $REQUEST_URI, $_SERVER;

  if ( empty ( $HTTP_HOST ) )
    $HTTP_HOST = $_SERVER["HTTP_HOST"];
  if ( empty ( $SERVER_PORT ) )
    $SERVER_PORT = $_SERVER["SERVER_PORT"];
  if ( empty ( $REQUEST_URI ) )
    $REQUEST_URI = $_SERVER["REQUEST_URI"];

  $res = dbi_query ( "SELECT cal_setting, cal_value FROM webcal_config" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $setting = $row[0];
      $value = $row[1];
      //echo "Setting '$setting' to '$value' <br />\n";
      $GLOBALS[$setting] = $value;
    }
    dbi_free_result ( $res );
  }

  // If app name not set.... default to "Title".  This gets translated
  // later since this function is typically called before translate.php
  // is included.
  // Note: We usually use translate($application_name) instead of
  // translate("Title").
  if ( empty ( $GLOBALS["application_name"] ) )
    $GLOBALS["application_name"] = "Title";

  // If $server_url not set, then calculate one for them, then store it
  // in the database.
  if ( empty ( $GLOBALS["server_url"] ) ) {
    if ( ! empty ( $HTTP_HOST ) && ! empty ( $REQUEST_URI ) ) {
      $ptr = strrpos ( $REQUEST_URI, "/" );
      if ( $ptr > 0 ) {
        $uri = substr ( $REQUEST_URI, 0, $ptr + 1 );
        $server_url = "http://" . $HTTP_HOST;
        if ( ! empty ( $SERVER_PORT ) && $SERVER_PORT != 80 )
          $server_url .= ":" . $SERVER_PORT;
        $server_url .= $uri;

        dbi_query ( "INSERT INTO webcal_config ( cal_setting, cal_value ) ".
          "VALUES ( 'server_url', '$server_url' )" );
        $GLOBALS["server_url"] = $server_url;
      }
    }
  }

  // If no font settings, then set some
  if ( empty ( $GLOBALS["FONTS"] ) ) {
    if ( $GLOBALS["LANGUAGE"] == "Japanese" )
      $GLOBALS["FONTS"] = "Osaka, Arial, Helvetica, sans-serif";
    else
      $GLOBALS["FONTS"] = "Arial, Helvetica, sans-serif";
  }
}

// Return a list of active plugins.
// Should be called after load_global_settings() and
// load_user_preferences().
// cek: not documented yet since I am not sure this will ever
// be used...
function get_plugin_list ( $include_disabled=false ) {
  // first get list of available plugins
  $sql = "SELECT cal_setting FROM webcal_config " .
    "WHERE cal_setting LIKE '%.plugin_status'";
  if ( ! $include_disabled )
    $sql .= " AND cal_value = 'Y'";
  $sql .= " ORDER BY cal_setting";
  $res = dbi_query ( $sql );
  $plugins = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $e = explode ( ".", $row[0] );
      if ( $e[0] != "" ) {
        $plugins[] = $e[0];
      }
    }
    dbi_free_result ( $res );
  } else {
    echo translate("Database error") . ": " . dbi_error (); exit;
  }
  if ( count ( $plugins ) == 0 ) {
    $plugins[] = "webcalendar";
  }
  return $plugins;
}

// Get plugins available to the current user.
// Do this by getting a list of all plugins that are not disabled by
// the administrator and make sure this user has not disabled any of
// them.
// It's done this was so that when an admin adds a new plugin, it
// shows up on each users system automatically (until they disable it).
function get_user_plugin_list () {
  $ret = array ();
  $all_plugins = get_plugin_list ();
  for ( $i = 0; $i < count ( $all_plugins ); $i++ ) {
    if ( $GLOBALS[$all_plugins[$i] . ".disabled"] != "N" )
      $ret[] = $all_plugins[$i];
  }
  return $ret;
}

// determine which browser
// currently supported return values:
//      Mozilla (open source Mozilla 5.0) = "Mozilla/5"
//      Netscape (3.X, 4.X) = "Mozilla/[3,4]"
//      MSIE (4.X) = "MSIE 4"
function get_web_browser () {
  if ( ereg ( "MSIE [0-9]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "MSIE";
  if ( ereg ( "Mozilla/[234]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Netscape";
  if ( ereg ( "Mozilla/[5678]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Mozilla";
  return "Unknown";
}


/** do_debug
  * Description:
  *	Log a debug message.  Generally, we do not leave calls to this
  *	function in the code.  It is used for debugging only.
  * Parameters:
  *	$msg - Text to be logged
  */
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  3, "/tmp/webcal-debug.log" );
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  2, "sockieman:2000" );
}

/** do_redirect
  * Description:
  *	Send a redirect to the specified page.
  *	The database connection is closed and execution terminates
  *	in this function. <br/>
  *	Note:
  *	MS IIS/PWS has a bug in which it does not allow us to send a cookie
  *	and a redirect in the same HTTP header.  When we detect that the
  *	web server is IIS, we accomplish the redirect using meta-refresh.
  *	See the following for more info on the IIS bug:
  *	  <blockquote>
  *	  <a href="http://www.faqts.com/knowledge_base/view.phtml/aid/9316/fid/4">
  *	  http://www.faqts.com/knowledge_base/view.phtml/aid/9316/fid/4</a>
  *	  </blockquote>
  * Parameters:
  *	$url - The page to redirect to.  In theory, this should be an
  *	absolute URL, but all browsers accept relative URLs
  *	(like "month.php").
  */
function do_redirect ( $url ) {
  global $SERVER_SOFTWARE, $_SERVER, $c;
  if ( empty ( $SERVER_SOFTWARE ) )
    $SERVER_SOFTWARE = $_SERVER["SERVER_SOFTWARE"];
  //echo "SERVER_SOFTWARE = $SERVER_SOFTWARE <br />\n"; exit;
  if ( ( substr ( $SERVER_SOFTWARE, 0, 5 ) == "Micro" ) ||
    ( substr ( $SERVER_SOFTWARE, 0, 3 ) == "WN/" ) ) {
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
<head>\n<title>Redirect</title>\n" .
      "<meta http-equiv=\"refresh\" content=\"0; url=$url\" />\n</head>\n<body>\n" .
      "Redirecting to.. <a href=\"" . $url . "\">here</a>.</body>\n</html>";
  } else {
    Header ( "Location: $url" );
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
<head>\n<title>Redirect</title>\n</head>\n<body>\n" .
      "Redirecting to ... <a href=\"" . $url . "\">here</a>.</body>\n</html>";
  }
  dbi_close ( $c );
  exit;
}

/** send_http_login
  * Description:
  *	Send an HTTP login request to the browser and stop execution.
  */
function send_http_login () {
  global $lang_file, $application_name;

  if ( strlen ( $lang_file ) ) {
    Header ( "WWW-Authenticate: Basic realm=\"" . translate("Title") . "\"");
    Header ( "HTTP/1.0 401 Unauthorized" );
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
<head>\n<title>Unauthorized</title>\n</head>\n<body>\n" .
      "<h2>" . translate("Title") . "</h2>\n" .
      translate("You are not authorized") .
      "\n</body>\n</html>";
  } else {
    Header ( "WWW-Authenticate: Basic realm=\"WebCalendar\"");
    Header ( "HTTP/1.0 401 Unauthorized" );
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
<head>\n<title>Unauthorized</title>\n</head>\n<body>\n" .
      "<h2>WebCalendar</h2>\n" .
      "You are not authorized" .
      "\n</body>\n</html>";
  }
  exit;
}

/** remember_this_view
  * Description:
  *	Generate a cookie that saves the last calendar view (month, week, day)
  *	based on the current $REQUEST_URI
  *	so we can return to this same page after a user edits/deletes/etc an
  *	event.
  */
function remember_this_view () {
  global $server_url, $REQUEST_URI;
  if ( empty ( $REQUEST_URI ) )
    $REQUEST_URI = $_SERVER["REQUEST_URI"];

  // do not use anything with friendly in the URI
  if ( strstr ( $REQUEST_URI, "friendly=" ) )
    return;

  SetCookie ( "webcalendar_last_view", $REQUEST_URI );
}

/** get_last_view
  * Description:
  *	Get the last page stored using the remember_this_view function.
  *	Return empty string if we don't know.
  * Returns:
  *	The URL of the last view or an empty string if it cannot be
  *	determined.
  */
function get_last_view () {
  global $HTTP_COOKIE_VARS;
  $val = '';

  if ( isset ( $HTTP_COOKIE_VARS["webcalendar_last_view"] ) )
    $val = $HTTP_COOKIE_VARS["webcalendar_last_view"];
  else if ( isset ( $_COOKIE["webcalendar_last_view"] ) )
    $val = $_COOKIE["webcalendar_last_view"];
  return $val;
}

/** send_no_cache_header
  * Description:
  *	Send header stuff that tells the browser not to cache this page.
  *	Different browser use different mechanisms for this, so a series
  *	of HTTP header directives are sent.
  *	<br/>Note: This function needs to be called before any HTML output
  *	is sent to the browser.
  */
function send_no_cache_header () {
  header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
  header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s" ) . " GMT" );
  header ( "Cache-Control: no-store, no-cache, must-revalidate" );
  header ( "Cache-Control: post-check=0, pre-check=0", false );
  header ( "Pragma: no-cache" );
}

/** load_user_preferences
  * Description:
  *	Load the current user's preferences as global variables
  *	from the webcal_user_pref table.
  *	Also load the list of views for this user (not really a preference,
  *	but this is a convenient place to put this...)
  *	<br/>Notes:   <ul>
  *	<li>If the $allow_color_customization is set to 'N',
  *	then we ignore any color preferences.</li>
  *	<li>Other default values will also be set if the user
  *	has not saved a preference and no global value has been set
  *	by the administrator in the system settings.</li>
  *	</ul>
  * Returns:
  *	The value used in the HTML form (or URL)
  */
function load_user_preferences () {
  global $login, $browser, $views, $prefarray, $is_assistant,
    $has_boss, $user, $is_nonuser_admin, $allow_color_customization;
  $lang_found = false;
  $colors = array (
    "BGCOLOR" => 1,
    "H2COLOR" => 1,
    "THBG" => 1,
    "THFG" => 1,
    "CELLBG" => 1,
    "TODAYCELLBG" => 1,
    "WEEKENDBG" => 1,
    "POPUP_BG" => 1,
    "POPUP_FG" => 1,
  );

  $browser = get_web_browser ();
  $browser_lang = get_browser_language ();
  $prefarray = array ();

  // Note: default values are set in config.php
  $res = dbi_query (
    "SELECT cal_setting, cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '$login'" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $setting = $row[0];
      $value = $row[1];
      if ( $allow_color_customization == 'N' ) {
        if ( isset ( $colors[$setting] ) )
          continue;
      }
      $sys_setting = "sys_" . $setting;
      // save system defaults
      if ( ! empty ( $GLOBALS[$setting] ) )
        $GLOBALS["sys_" . $setting] = $GLOBALS[$setting];
      $GLOBALS[$setting] = $value;
      $prefarray[$setting] = $value;
      if ( $setting == "LANGUAGE" )
        $lang_found = true;
    }
    dbi_free_result ( $res );
  }
  // get views for this user
  $res = dbi_query (
    "SELECT cal_view_id, cal_name, cal_view_type FROM webcal_view " .
    "WHERE cal_owner = '$login'" );
  if ( $res ) {
    $views = array ();
    while ( $row = dbi_fetch_row ( $res ) ) {
      $v = array (
        "cal_view_id" => $row[0],
        "cal_name" => $row[1],
        "cal_view_type" => $row[2]
        );
      $views[] = $v;
    }
    dbi_free_result ( $res );
  }

  // If user has not set a language preference, then use their browser
  // settings to figure it out, and save it in the database for future
  // use (email reminders).
  if ( ! $lang_found && strlen ( $login ) && $login != "__public__" ) {
    $LANGUAGE = $browser_lang;
    dbi_query ( "INSERT INTO webcal_user_pref " .
      "( cal_login, cal_setting, cal_value ) VALUES " .
      "( '$login', 'LANGUAGE', '$LANGUAGE' )" );
  }

  if ( empty ( $GLOBALS["DATE_FORMAT_MY"] ) )
    $GLOBALS["DATE_FORMAT_MY"] = "__month__ __yyyy__";
  if ( empty ( $GLOBALS["DATE_FORMAT_MD"] ) )
    $GLOBALS["DATE_FORMAT_MD"] = "__month__ __dd__";
  $is_assistant = empty ( $user ) ? false :
    user_is_assistant ( $login, $user );
  $has_boss = user_has_boss ( $login );
  $is_nonuser_admin = ($user) ? user_is_nonuser_admin ( $login, $user ) : false;
  if ( $is_nonuser_admin ) load_nonuser_preferences ($user);
}

/** event_get_external_users
  * Description:
  *	Get the list of external users for an event from the
  *	webcal_entry_ext_user table in an HTML format.
  * Parameters:
  *	$event_id - event id
  *	$use_mailto - when set to 1, email address will contain an href
  *	  link with a mailto URL.
  * Returns:
  *	The list of external users for an event formatte in HTML.
  */
function event_get_external_users ( $event_id, $use_mailto=0 ) {
  global $error;
  $ret = "";

  $res = dbi_query ( "SELECT cal_fullname, cal_email " .
    "FROM webcal_entry_ext_user " .
    "WHERE cal_id = $event_id " .
    "ORDER by cal_fullname" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( strlen ( $ret ) )
        $ret .= "\n";
      $ret .= $row[0];
      if ( strlen ( $row[1] ) ) {
        if ( $use_mailto ) {
          $ret .= " <a href=\"mailto:$row[1]\">&lt;" .
            htmlentities ( $row[1] ) . "&gt;</a>";
        } else {
          $ret .= " &lt;". htmlentities ( $row[1] ) . "&gt;";
        }
      }
    }
    dbi_free_result ( $res );
  } else {
    echo translate("Database error") .": " . dbi_error ();
    echo "<br />\nSQL:<br />\n$sql";
    exit;
  }
  return $ret;
}

/** activity_log
  * Description:
  *	Add something to the activity log for an event.
  *	The information will be saved to the
  *	webcal_entry_log table.
  * Parameters:
  *	$event_id - event id
  *	$user - user doing this
  *	$user_cal - user whose calendar is affected
  *	$type - type of activity we are logging:  <ul>
  *	  <li>$LOG_CREATE</li>
  *	  <li>$LOG_APPROVE</li>
  *	  <li>$LOG_REJECT</li>
  *	  <li>$LOG_UPDATE</li>
  *	  <li>$LOG_DELETE</li>
  *	  <li>$LOG_NOTIFICATION</li>
  *	  <li>$LOG_REMINDER</li>
  *	</ul>
  *	$text - text comment to add with activity log entry
  * Returns:
  *	The value used in the HTML form (or URL)
  */
function activity_log ( $event_id, $user, $user_cal, $type, $text ) {
  $next_id = 1;

  if ( empty ( $type ) ) {
    echo "Error: type not set for activity log!";
    // but don't exit since we may be in mid-transaction
    return;
  }

  $res = dbi_query ( "SELECT MAX(cal_log_id) FROM webcal_entry_log" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $next_id = $row[0] + 1;
    }
    dbi_free_result ( $res );
  }

  $date = date ( "Ymd" );
  $time = date ( "Gis" );
  $sql_text = empty ( $text ) ? "NULL" : "'$text'";
  $sql_user_cal = empty ( $user_cal ) ? "NULL" : "'$user_cal'";

  $sql = "INSERT INTO webcal_entry_log ( " .
    "cal_log_id, cal_entry_id, cal_login, cal_user_cal, cal_type, " .
    "cal_date, cal_time, cal_text ) VALUES ( $next_id, $event_id, " .
    "'$user', $sql_user_cal, '$type', $date, $time, $sql_text )";
  if ( ! dbi_query ( $sql ) ) {
    echo "Database error: " . dbi_error ();
    echo "<br />\nSQL:<br />\n$sql";
    exit;
  }
}

/** get_my_users
  * Description:
  *	Get a list of users.
  *	If groups are enabled, this will restrict
  *	the list of users to only those users who are in the same group(s)
  *	as the user (unless the user is an admin user).
  *	We allow admin users to see all users because they can also edit
  *	someone else's events (so they may need access to users who are not
  *	in the same groups that they are in).
  * Returns:
  *	An array of users, where each element in the array is an array
  *	with the following keys:<ul>
  *		<li>cal_login</li>
  *		<li>cal_lastname</li>
  *		<li>cal_firstname</li>
  *		<li>cal_is_admin</li>
  *		<li>cal_is_admin</li>
  *		<li>cal_email</li>
  *		<li>cal_password</li>
  *		<li>cal_fullname</li>
  *	</ul>
  */
function get_my_users () {
  global $login, $is_admin, $groups_enabled, $user_sees_only_his_groups;

  if ( $groups_enabled == "Y" && $user_sees_only_his_groups == "Y" &&
    ! $is_admin ) {
    // get groups that current user is in
    $res = dbi_query ( "SELECT cal_group_id FROM webcal_group_user " .
      "WHERE cal_login = '$login'" );
    $groups = array ();
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $groups[] = $row[0];
      }
      dbi_fetch_row ( $res );
    }
    $u = user_get_users ();
    $u_byname = array ();
    for ( $i = 0; $i < count ( $u ); $i++ ) {
      $name = $u[$i]['cal_login'];
      $u_byname[$name] = $u[$i];
    }
    $ret = array ();
    if ( count ( $groups ) == 0 ) {
      // Eek.  User is in no groups... Return only themselves
      $ret[] = $u_byname[$login];
      return $ret;
    }
    // get list of users in the same groups as current user
    $sql = "SELECT DISTINCT(webcal_group_user.cal_login), cal_lastname, cal_firstname from webcal_group_user " .
      "LEFT JOIN webcal_user ON webcal_group_user.cal_login = webcal_user.cal_login " .
      "WHERE cal_group_id ";
    if ( count ( $groups ) == 1 )
      $sql .= "= " . $groups[0];
    else {
      $sql .= "IN ( " . implode ( ", ", $groups ) . " )";
    }
    $sql .= " ORDER BY cal_lastname, cal_firstname, webcal_group_user.cal_login";
    //echo "SQL: $sql <br />\n";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $ret[] = $u_byname[$row[0]];
      }
      dbi_free_result ( $res );
    }
    return $ret;
  } else {
    // groups not enabled... return all users
    //echo "No groups. ";
    return user_get_users ();
  }
}

/** get_pref_setting
  * Description:
  *	Get a preference setting for the specified user.  If no value is
  *	found in the database, then the system default setting will be
  *	returned.
  * Parameters:
  *	$user - user login we are getting preference for
  *	$setting - the name of the setting
  * Returns:
  *	The value found in the webcal_user_pref table for the
  *	specified setting or the sytem default if no user settings
  *	was found.
  */
function get_pref_setting ( $user, $setting ) {
  // set default
  if ( $GLOBALS["sys_" .$setting] == "" ) {
    // this could happen if the current user has not saved any pref. yet
    $ret = $GLOBALS[$setting];
  } else {
    $ret = $GLOBALS["sys_" .$setting];
  }

  $sql = "SELECT cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '" . $user . "' AND " .
    "cal_setting = '" . $setting . "'";
  //echo "SQL: $sql <br />\n";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $ret = $row[0];
    dbi_free_result ( $res );
  }
  return $ret;
}

// Get browser-specified language preference
function get_browser_language () {
  global $HTTP_ACCEPT_LANGUAGE, $browser_languages;
  $ret = "";
  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) )
    $HTTP_ACCEPT_LANGUAGE = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
  if ( strlen ( $HTTP_ACCEPT_LANGUAGE ) == 0 )
    return "none";
  $langs = explode ( ",", $HTTP_ACCEPT_LANGUAGE );
  for ( $i = 0; $i < count ( $langs ); $i++ ) {
    $l = strtolower ( trim ( $langs[$i] ) );
    $ret .= "\"$l\" ";
    if ( ! empty ( $browser_languages[$l] ) ) {
      return $browser_languages[$l];
    }
  }
  //if ( strlen ( $HTTP_ACCEPT_LANGUAGE ) )
  //  return "none ($HTTP_ACCEPT_LANGUAGE not supported)";
  //else
    return "none";
}

/** load_user_layers
  * Description:
  *	Load current user's layer info and stuff it into layer global variable.
  *	If the system setting $allow_view_other is not set to 'Y', then
  *	we ignore all layer functionality.
  *	If $force is 0, we only load layers if the current user preferences
  *	have layers turned on.
  * Parameters:
  *	$user - User to load layers for
  *	$force - If set to 1, then load layers for this user even if
  *		user preferences have layers turned off.
  */
function load_user_layers ($user="",$force=0) {
  global $login;
  global $layers;
  global $LAYERS_STATUS, $allow_view_other;

  if ( $user == "" )
    $user = $login;

  $layers = array ();

  if ( empty ( $allow_view_other ) || $allow_view_other != 'Y' )
    return; // not allowed to view others' calendars, so cannot use layers

  if ( $force || ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS != "N" ) ) {
    $res = dbi_query (
      "SELECT cal_layerid, cal_layeruser, cal_color, cal_dups " .
      "FROM webcal_user_layers " .
      "WHERE cal_login = '$user' ORDER BY cal_layerid" );
    if ( $res ) {
      $count = 1;
      while ( $row = dbi_fetch_row ( $res ) ) {
        $layers[$row[0]] = array (
          "cal_layerid" => $row[0],
          "cal_layeruser" => $row[1],
          "cal_color" => $row[2],
          "cal_dups" => $row[3]
        );
        $count++;
      }
      dbi_free_result ( $res );
    }
  } else {
    //echo "Not loading!";
  }
}

/** site_extras_for_popup
  * Description:
  *	Generate the HTML used in an event popup for the site_extras
  *	fields of an event.
  * Parameters:
  *	$id - event id
  * Returns:
  *	The HTML to be used within the event popup for any site_extra
  *	fields found for the specified event
  */
function site_extras_for_popup ( $id ) {
  global $site_extras_in_popup, $site_extras;
  // These are needed in case the site_extras.php file was already
  // included.
  global $EXTRA_TEXT, $EXTRA_MULTILINETEXT, $EXTRA_URL, $EXTRA_DATE,
    $EXTRA_EMAIL, $EXTRA_USER, $EXTRA_REMINDER, $EXTRA_SELECTLIST;
  global $EXTRA_REMINDER_WITH_DATE, $EXTRA_REMINDER_WITH_OFFSET,
    $EXTRA_REMINDER_DEFAULT_YES;

  $ret = '';

  if ( $site_extras_in_popup != 'Y' )
    return '';

  include_once 'includes/site_extras.php';

  $extras = get_site_extra_fields ( $id );
  for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
    $extra_name = $site_extras[$i][0];
    $extra_type = $site_extras[$i][2];
    $extra_arg1 = $site_extras[$i][3];
    $extra_arg2 = $site_extras[$i][4];
    if ( ! empty ( $extras[$extra_name]['cal_name'] ) ) {
      $ret .= "<dt>" .  translate ( $site_extras[$i][1] ) . ":</dt>\n<dd>";
      if ( $extra_type == $EXTRA_DATE ) {
        if ( $extras[$extra_name]['cal_date'] > 0 )
          $ret .= date_to_str ( $extras[$extra_name]['cal_date'] );
      } else if ( $extra_type == $EXTRA_TEXT ||
        $extra_type == $EXTRA_MULTILINETEXT ) {
        $ret .= nl2br ( $extras[$extra_name]['cal_data'] );
      } else if ( $extra_type == $EXTRA_REMINDER ) {
        if ( $extras[$extra_name]['cal_remind'] <= 0 )
          $ret .= translate ( "No" );
        else {
          $ret .= translate ( "Yes" );
          if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_DATE ) > 0 ) {
            $ret .= "&nbsp;&nbsp;-&nbsp;&nbsp;";
            $ret .= date_to_str ( $extras[$extra_name]['cal_date'] );
          } else if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
            $ret .= "&nbsp;&nbsp;-&nbsp;&nbsp;";
            $minutes = $extras[$extra_name]['cal_data'];
            $d = (int) ( $minutes / ( 24 * 60 ) );
            $minutes -= ( $d * 24 * 60 );
            $h = (int) ( $minutes / 60 );
            $minutes -= ( $h * 60 );
            if ( $d > 0 )
              $ret .= $d . "&nbsp;" . translate("days") . "&nbsp;";
            if ( $h > 0 )
              $ret .= $h . "&nbsp;" . translate("hours") . "&nbsp;";
            if ( $minutes > 0 )
              $ret .= $minutes . "&nbsp;" . translate("minutes");
            $ret .= "&nbsp;" . translate("before event" );
          }
        }
      } else {
        $ret .= $extras[$extra_name]['cal_data'];
      }
      $ret .= "</dd>\n";
    }
  }
  return $ret;
}

/** build_event_popup
  * Description:
  *	Build the HTML for the event popup (but don't print it yet since we
  *	don't want this HTML to go inside the table for the month).
  * Parameters:
  *	$popupid - CSS id to use for event popup
  *	$user - user the event pertains to
  *	$description - event description
  *	$time - time of the event (already formatted in a display format)
  *	$site_extras - the HTML for any site_extras for this event
  * Returns:
  *	The HTML for the event popup
  */
function build_event_popup ( $popupid, $user, $description, $time, $site_extras='' ) {
  global $login, $popup_fullnames, $popuptemp_fullname;
  $ret = "<dl id=\"$popupid\" class=\"popup\">\n";

  if ( empty ( $popup_fullnames ) )
    $popup_fullnames = array ();
  
  if ( $user != $login ) {
    if ( empty ( $popup_fullnames[$user] ) ) {
      user_load_variables ( $user, "popuptemp_" );
      $popup_fullnames[$user] = $popuptemp_fullname;
    }
    $ret .= "<dt>" . translate ("User") .
      ":</dt>\n<dd>$popup_fullnames[$user]</dd>\n";
  }
  if ( strlen ( $time ) )
    $ret .= "<dt>" . translate ("Time") . ":</dt>\n<dd>$time</dd>\n";
  $ret .= "<dt>" . translate ("Description") . ":</dt>\n<dd>";
  if ( ! empty ( $GLOBALS['allow_html_description'] ) &&
    $GLOBALS['allow_html_description'] == 'Y' ) {
    $str = str_replace ( "&", "&amp;", $description );
    $str = str_replace ( "&amp;amp;", "&amp;", $str );
    // If there is no html found, then go ahead and replace
    // the line breaks ("\n") with the html break.
    if ( strstr ( $str, "<" ) && strstr ( $str, ">" ) ) {
      // found some html...
      $ret .= $str;
    } else {
      // no html, replace line breaks
      $ret .= nl2br ( $str );
    }
  } else {
    // html not allowed in description, escape everything
    $ret .= nl2br ( htmlspecialchars ( $description ) );
  }
  $ret .= "</dd>\n";
  if ( ! empty ( $site_extras ) )
    $ret .= $site_extras;
  $ret .= "</dl>\n";
  return $ret;
}

/** print_date_selection
  * Description:
  *	Print out a date selection for use in a form.
  * Parameters:
  *	$prefix - prefix to use in front of form element names
  *	$date - currently selected date (in YYYYMMDD) format
  * Returns:
  *	The value used in the HTML form (or URL)
  */
function print_date_selection ( $prefix, $date ) {
  print date_selection_html ( $prefix, $date );
}

/** date_selection_html
  * Description:
  *	Print out a date selection for use in a form.
  * Parameters:
  *	$prefix - prefix to use in front of form element names
  *	$date - currently selected date (in YYYYMMDD) format
  * Returns:
  *	The value used in the HTML form (or URL)
  */
function date_selection_html ( $prefix, $date ) {
  $ret = "";
  $num_years = 20;
  if ( strlen ( $date ) != 8 )
    $date = date ( "Ymd" );
  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
  if ( $thisyear - date ( "Y" ) >= ( $num_years - 1 ) )
    $num_years = $thisyear - date ( "Y" ) + 2;
  $ret .= "<select name=\"" . $prefix . "day\">\n";
  for ( $i = 1; $i <= 31; $i++ )
    $ret .= "<option" . ( $i == $thisday ? " selected=\"selected\"" : "" ) . ">$i</option>\n";
  $ret .= "</select>\n<select name=\"" . $prefix . "month\">\n";
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    $ret .= "<option value=\"$i\"" .
      ( $i == $thismonth ? " selected=\"selected\"" : "" ) . ">$m</option>\n";
  }
  $ret .= "</select>\n<select name=\"" . $prefix . "year\">\n";
  for ( $i = -10; $i < $num_years; $i++ ) {
    $y = $thisyear + $i;
    $ret .= "<option value=\"$y\"" .
      ( $y == $thisyear ? " selected=\"selected\"" : "" ) . ">$y</option>\n";
  }
  $ret .= "</select>\n";
  $ret .= "<input type=\"button\" onclick=\"selectDate('" .
    $prefix . "day','" . $prefix . "month','" . $prefix . "year',$date)\" value=\"" .
    translate("Select") . "...\" />\n";

  return $ret;
}

/** display_small_month
  * Description:
  *	Prints out a minicalendar for a month
  * Parameters:
  *	$thismonth - number of the month to print
  *	$thisyear - number of the year
  *	$showyear - boolean whether to show the year in the calendar's title
  *	$show_weeknums - boolean whether to show week numbers to the left of each row
  *	$minical_id - id attribute for the minical table
  *	$month_link - URL and query string for month link that should
  *	come before the date specification
  *	(i.e. month.php?  or  view_l.php?id=7&amp;)
  *	[defaults to 'month.php?']
  */
function display_small_month ( $thismonth, $thisyear, $showyear,
  $show_weeknums=false, $minical_id='', $month_link='month.php?' ) {
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved;
  global $DISPLAY_WEEKNUMBER;
  global $SCRIPT, $thisday; // Needed for day.php

  // TODO: Make day.php NOT be a special case
  if ( $user != $login && ! empty ( $user ) ) {
    $u_url = "user=$user";
  } else {
    $u_url = '';
  }

  //start the minical table for each month
  echo "\n<table class=\"minical\"";
  if ( $minical_id != '' ) {
    echo " id=\"$minical_id\"";
  }
  echo ">\n";

  $monthstart = mktime(2,0,0,$thismonth,1,$thisyear);
  $monthend = mktime(2,0,0,$thismonth + 1,0,$thisyear);

  if ( $SCRIPT == 'day.php' ) {
    $month_ago = date ( "Ymd",
      mktime ( 3, 0, 0, $thismonth - 1, $thisday, $thisyear ) );
    $month_ahead = date ( "Ymd",
      mktime ( 3, 0, 0, $thismonth + 1, $thisday, $thisyear ) );

    echo "<caption>$thisday</caption>\n";
    echo "<thead>\n";
    echo "<tr class=\"monthnav\"><th colspan=\"7\">\n";
    echo "<a title=\"" . 
	translate("Previous") . "\" class=\"prev\" href=\"day.php?" .
	( empty ( $u_url ) ? '' : $u_url . '&amp;' ) .
	"date=$month_ago$caturl\"><img src=\"leftarrowsmall.gif\" alt=\"" .
	translate("Previous") . "\" /></a>\n";
    echo "<a title=\"" . 
	translate("Next") . "\" class=\"next\" href=\"day.php?" .
	( empty ( $u_url ) ? '' : $u_url . '&amp;' ) .
	"date=$month_ahead$caturl\"><img src=\"rightarrowsmall.gif\" alt=\"" .
	translate("Next") . "\" /></a>\n";
    echo month_name ( $thismonth - 1 );
    if ( $showyear != '' ) {
      echo " $thisyear";
    }
    echo "</th></tr>\n";
  } else {
    //print the month name
    echo "<caption><a href=\"{$month_link}{$u_url}&amp;year=$thisyear&amp;month=$thismonth\">";
	echo month_name ( $thismonth - 1 ) .
		( $showyear ? " $thisyear" : "" );
    echo "</a></caption>\n";

    echo "<thead>\n<tr>\n";
  }

  //determine if the week starts on sunday or monday
  if ( $WEEK_START == "1" ) {
    $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
  } else {
    $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
  }
  //print the headers to display the day of the week (sun, mon, tues, etc.)

  // if we're showing week numbers we need an extra column
  if ( $show_weeknums && $DISPLAY_WEEKNUMBER == 'Y' )
    echo "<th class=\"empty\">&nbsp;</th>\n";
  //if the week doesn't start on monday, print the day
  if ( $WEEK_START == 0 ) echo "<th>" .
    weekday_short_name ( 0 ) . "</th>\n";
  //cycle through each day of the week until gone
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<th>" .  weekday_short_name ( $i ) .  "</th>\n";
  }
  //if the week DOES start on monday, print sunday
  if ( $WEEK_START == 1 )
    echo "<th>" .  weekday_short_name ( 0 ) .  "</th>\n";
  //end the header row
  echo "</tr>\n</thead>\n<tbody>\n";
  for ($i = $wkstart; date("Ymd",$i) <= date ("Ymd",$monthend);
    $i += (24 * 3600 * 7) ) {
    echo "<tr>\n";
    if ( $show_weeknums && $DISPLAY_WEEKNUMBER == 'Y' ) {
      echo "<td class=\"weeknumber\"><a href=\"week.php?" .
        ( empty ( $u_url ) ? '' : $u_url . '&amp;' ) .
        "date=".date("Ymd", $i)."\">(" . week_number($i) . ")</a></td>\n";
    }
    for ($j = 0; $j < 7; $j++) {
      $date = $i + ($j * 24 * 3600);
      $dateYmd = date ( "Ymd", $date );
      $hasEvents = false;
      if ( $boldDays ) {
        $ev = get_entries ( $user, $dateYmd, $get_unapproved );
        if ( count ( $ev ) > 0 ) {
          $hasEvents = true;
        } else {
          $rep = get_repeating_entries ( $user, $dateYmd, $get_unapproved );
          if ( count ( $rep ) > 0 )
            $hasEvents = true;
        }
      }
      if ( $dateYmd >= date ("Ymd",$monthstart) &&
        $dateYmd <= date ("Ymd",$monthend) ) {
        echo "<td";
        $wday = date ( 'w', $date );
        $class = '';
	//add class="weekend" if it's saturday or sunday
        if ( $wday == 0 || $wday == 6 ) {
          $class = "weekend";
        }
	//if the day being viewed is today's date
        if ( $dateYmd == $thisyear . $thismonth . $thisday ) {
	  //if it's also a weekend, add a space between class names to combine styles
	  if ( $class != '' ) {
            $class .= ' ';
          }
          $class .= "selectedday";
        }
        if ( $hasEvents ) {
          if ( $class != '' ) {
            $class .= ' ';
          }
          $class .= "hasevents";
        }
        if ( $class != '' ) {
          echo " class=\"$class\"";
        }
        if ( $dateYmd == date ( 'Ymd' ) ) {
          echo " id=\"today\"";
        }
        echo "><a href=\"day.php?date=" . $dateYmd .
          ( empty ( $u_url ) ? '' : '&amp;' . $u_url ) .
          "\">";
        echo date ( "d", $date ) . "</a></td>\n";
        } else {
          echo "<td class=\"empty\">&nbsp;</td>\n";
        }
      }                 // end for $j
      echo "</tr>\n";
    }                         // end for $i
  echo "</tbody>\n</table>\n";
}

/** print_entry
  * Description:
  *	Print the HTML for one day's events in the month view.
  * Parameters:
  *	$id - event id
  *	$date - date of event (relavant in repeating events) in YYYYMMDD format
  *	$time - time (in HHMMSS format)
  *	$duration - event duration (in minutes)
  *	$name - event name
  *	$description - long description of event
  *	$status - event status
  *	$pri - event priority
  *	$access - event access
  *	$event_owner - user associated with this event
  *	$event_cat - category of event for event_owner
  */
function print_entry ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner, $event_cat=-1 ) {
  global $eventinfo, $login, $user, $PHP_SELF, $TZ_OFFSET;
  static $key = 0;
  
  global $layers;

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" ) $class = "unapprovedentry";
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" ) ||
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  if ( $pri == 3 ) echo "<strong>";
  $popupid = "eventinfo-$id-$key";
  $key++;
  echo "<a title=\"" . 
    translate("View this entry") . "\" class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $user ) > 0 )
    echo "&amp;user=" . $user;
  echo "\" onmouseover=\"window.status='" . 
    translate("View this entry") .
    "'; show(event, '$popupid'); return true;\" onmouseout=\"window.status=''; hide('$popupid'); return true;\">";
  $icon = "circle.gif";
  $catIcon = '';
  if ( $event_cat > 0 ) {
    $catIcon = "icons/cat-" . $event_cat . ".gif";
    if ( ! file_exists ( $catIcon ) )
      $catIcon = '';
  }

  if ( empty ( $catIcon ) ) {
    echo "<img src=\"$icon\" class=\"bullet\" alt=\"" . 
      translate("View this entry") . "\" />";
  } else {
    // Use category icon
    echo "<img src=\"$catIcon\" alt=\"" . 
      translate("View this entry") . "\" /><br />";
  }

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ($layer['cal_layeruser'] == $event_owner) {
        echo("<span style=\"color:" . $layer['cal_color'] . ";\">");
      }
    }
  }


  $timestr = "";
  if ( $duration == ( 24 * 60 ) ) {
    $timestr = translate("All day event");
  } else if ( $time != -1 ) {
    $timestr = display_time ( $time );
    $time_short = preg_replace ("/(:00)/", '', $timestr);
    echo $time_short . "&raquo;&nbsp;";
    if ( $duration > 0 ) {
        // calc end time
        $h = (int) ( $time / 10000 );
        $m = ( $time / 100 ) % 100;
        $m += $duration;
        $d = $duration;
        while ( $m >= 60 ) {
          $h++;
          $m -= 60;
        }
        $end_time = sprintf ( "%02d%02d00", $h, $m );
        $timestr .= " - " . display_time ( $end_time );
    }
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) ) {
    echo "(" . translate("Private") . ")";
  } else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) ) {
    echo "(" . translate("Private") . ")";
  } else {
    echo htmlspecialchars ( $name );
  }

  echo "</a>";
  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event_owner) {
            echo "</span>\n";
        }
    }
  }

  if ( $pri == 3 ) echo "</strong>\n"; //end font-weight span
  echo "<br />";
	if ( $login != $user && $access == 'R' && strlen ( $user ) )
		$eventinfo .= build_event_popup ( $popupid, $event_owner,
			translate("This event is confidential"), "" );
	else
	if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
		$eventinfo .= build_event_popup ( $popupid, $event_owner,
			translate("This event is confidential"), "" );
	else
		$eventinfo .= build_event_popup ( $popupid, $event_owner,
			$description, $timestr, site_extras_for_popup ( $id ) );
}

/** get_site_extra_fields
  * Description:
  *	Get any site-specific fields for an entry that are stored
  *	in the database in the webcal_site_extras table.
  * Parameters:
  *	$eventid - event id
  * Returns:
  *	Return an array of array with the keys as follows:  <ul>
  *	  <li> cal_name </li>
  *	  <li> cal_type </li>
  *	  <li> cal_date </li>
  *	  <li> cal_remind </li>
  *	  <li> cal_data </li>
  *	</ul>
  */
function get_site_extra_fields ( $eventid ) {
  $sql = "SELECT cal_name, cal_type, cal_date, cal_remind, cal_data " .
    "FROM webcal_site_extras " .
    "WHERE cal_id = $eventid";
  $res = dbi_query ( $sql );
  $extras = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      // save by cal_name (e.g. "URL")
      $extras[$row[0]] = array (
        "cal_name" => $row[0],
        "cal_type" => $row[1],
        "cal_date" => $row[2],
        "cal_remind" => $row[3],
        "cal_data" => $row[4]
      );
    }
    dbi_free_result ( $res );
  }
  return $extras;
}

/** read_events
  * Description:
  *	Read all the events for a user for the specified range of dates.
  *	This is only called once per page request to improve performance.
  *	All the events get loaded into the array $events sorted by
  *	time of day (not date).
  * Parameters:
  *	$user - username
  *	$startdate - start date range, inclusive (in YYYYMMDD format)
  *	$enddate - end date range, inclusive (in YYYYMMDD format)
  *	$cat_id - category ID to filter on
  * Returns:
  *	An array of events
  */
function read_events ( $user, $startdate, $enddate, $cat_id = ''  ) {
  global $login;
  global $layers;
  global $TZ_OFFSET;

  $sy = substr ( $startdate, 0, 4 );
  $sm = substr ( $startdate, 4, 2 );
  $sd = substr ( $startdate, 6, 2 );
  $ey = substr ( $enddate, 0, 4 );
  $em = substr ( $enddate, 4, 2 );
  $ed = substr ( $enddate, 6, 2 );
  if ( $startdate == $enddate ) {
    if ( $TZ_OFFSET == 0 ) {
      $date_filter = " AND webcal_entry.cal_date = $startdate";
    } else if ( $TZ_OFFSET > 0 ) {
      $prev_day = mktime ( 3, 0, 0, $sm, $sd - 1, $sy );
      $cutoff = 24 - $TZ_OFFSET .  "0000";
      $date_filter = " AND ( ( webcal_entry.cal_date = $startdate AND " .
        "( webcal_entry.cal_time <= $cutoff OR " .
        "webcal_entry.cal_time = -1 ) ) OR " .
        "( webcal_entry.cal_date = " . date("Ymd", $prev_day ) .
        " AND webcal_entry.cal_time >= $cutoff ) )";
    } else {
      $next_day = mktime ( 3, 0, 0, $sm, $sd + 1, $sy );
      $cutoff = ( 0 - $TZ_OFFSET ) * 10000;
      $date_filter = " AND ( ( webcal_entry.cal_date = $startdate AND " .
        "( webcal_entry.cal_time > $cutoff OR " .
        "webcal_entry.cal_time = -1 ) ) OR " .
        "( webcal_entry.cal_date = " . date("Ymd", $next_day ) .
        " AND webcal_entry.cal_time <= $cutoff ) )";
    }
  } else {
    if ( $TZ_OFFSET == 0 ) {
      $date_filter = " AND webcal_entry.cal_date >= $startdate " .
        "AND webcal_entry.cal_date <= $enddate";
    } else if ( $TZ_OFFSET > 0 ) {
      $prev_day = date ( ( "Ymd" ), mktime ( 3, 0, 0, $sm, $sd - 1, $sy ) );
      $enddate_minus1 = date ( ( "Ymd" ), mktime ( 3, 0, 0, $em, $ed - 1, $ey ) );
      $cutoff = 24 - $TZ_OFFSET . "0000";
      $date_filter = " AND ( ( webcal_entry.cal_date >= $startdate " .
        "AND webcal_entry.cal_date <= $enddate AND " .
        "webcal_entry.cal_time = -1 ) OR " .
        "( webcal_entry.cal_date = $prev_day AND " .
        "webcal_entry.cal_time >= $cutoff ) OR " .
        "( webcal_entry.cal_date = $enddate AND " .
        "webcal_entry.cal_time < $cutoff ) OR " .
        "( webcal_entry.cal_date >= $startdate AND " .
        "webcal_entry.cal_date <= $enddate_minus1 ) )";
    } else {
      // TZ_OFFSET < 0
      $next_day = date ( ( "Ymd" ), mktime ( 3, 0, 0, $sm, $sd + 1, $sy ) );
      $enddate_plus1 =
        date ( ( "Ymd" ), mktime ( 3, 0, 0, $em, $ed + 1, $ey ) );
      $cutoff = ( 0 - $TZ_OFFSET ) * 10000;
      $date_filter = " AND ( ( webcal_entry.cal_date >= $startdate " .
        "AND webcal_entry.cal_date <= $enddate AND " .
        "webcal_entry.cal_time = -1 ) OR " .
        "( webcal_entry.cal_date = $startdate AND " .
        "webcal_entry.cal_time > $cutoff ) OR " .
        "( webcal_entry.cal_date = $enddate_plus1 AND " .
        "webcal_entry.cal_time <= $cutoff ) OR " .
        "( webcal_entry.cal_date > $startdate AND " .
        "webcal_entry.cal_date < $enddate_plus1 ) )";
    }
  }
  return query_events ( $user, false, $date_filter, $cat_id  );
}

/** get_entries
  * Description:
  *	Get all the events for a specific date from the array of pre-loaded
  *	events (which was loaded all at once to improve performance).
  *	The returned events will be sorted by time of day.
  * Parameters:
  *	$user - username
  *	$date - date to get events for in YYYYMMDD format
  *	$get_unapproved - load unapproved events (true/false)
  * Returns:
  *	An array of events
  */
function get_entries ( $user, $date, $get_unapproved=true ) {
  global $events, $TZ_OFFSET;
  $n = 0;
  $ret = array ();

  //echo "<br />\nChecking " . count ( $events ) . " events.  TZ_OFFSET = $TZ_OFFSET, get_unapproved=" . $get_unapproved . "<br />\n";

  for ( $i = 0; $i < count ( $events ); $i++ ) {
    if ( ( ! $get_unapproved ) && $events[$i]['cal_status'] == 'W' ) {
      // ignore this event
    } else if ( empty ( $TZ_OFFSET ) ) {
      if ( $events[$i]['cal_date'] == $date )
        $ret[$n++] = $events[$i];
    } else if ( $TZ_OFFSET > 0 ) {
      $cutoff = ( 24 - $TZ_OFFSET ) * 10000;
      //echo "<br /> cal_time " . $events[$i]['cal_time'] . "<br />\n";
      $sy = substr ( $date, 0, 4 );
      $sm = substr ( $date, 4, 2 );
      $sd = substr ( $date, 6, 2 );
      $prev_day = date ( ( "Ymd" ), mktime ( 3, 0, 0, $sm, $sd - 1, $sy ) );
        //echo "prev_date = $prev_day <br />\n";
      if ( $events[$i]['cal_date'] == $date &&
        $events[$i]['cal_time'] == -1 ) {
        $ret[$n++] = $events[$i];
        //echo "added event $events[$i][cal_id] <br />\n";
      } else if ( $events[$i]['cal_date'] == $date &&
        $events[$i]['cal_time'] < $cutoff ) {
        $ret[$n++] = $events[$i];
        //echo "added event {$events[$i][cal_id]} <br />\n";
      } else if ( $events[$i]['cal_date'] == $prev_day &&
        $events[$i]['cal_time'] >= $cutoff ) {
        $ret[$n++] = $events[$i];
        //echo "added event {$events[$i][cal_id]} <br />\n";
      }
    } else {
      //TZ < 0
      $cutoff = ( 0 - $TZ_OFFSET ) * 10000;
      //echo "<br />\ncal_time " . $events[$i]['cal_time'] . "<br />\n";
      $sy = substr ( $date, 0, 4 );
      $sm = substr ( $date, 4, 2 );
      $sd = substr ( $date, 6, 2 );
      $next_day = date ( ( "Ymd" ), mktime ( 3, 0, 0, $sm, $sd + 1, $sy ) );
      //echo "next_date = $next_day <br />\n";
      if ( $events[$i]['cal_time'] == -1 ) {
	if ( $events[$i]['cal_date'] == $date ) {
          $ret[$n++] = $events[$i];
          //echo "added event $events[$i][cal_id] <br />\n";
        }
      } else {
	if ( $events[$i]['cal_date'] == $date &&
          $events[$i]['cal_time'] > $cutoff ) {
          $ret[$n++] = $events[$i];
          //echo "added event $events[$i][cal_id] <br />\n";
        } else if ( $events[$i]['cal_date'] == $next_day &&
          $events[$i]['cal_time'] <= $cutoff ) {
          $ret[$n++] = $events[$i];
          //echo "added event $events[$i][cal_id] <br />\n";
        }
      }
    }
  }
  return $ret;
}

/** query_events
  * Description:
  *	Read events visible to a user (including layers and possibly
  *	public access if enabled); Return results
  *	in an array sorted by time of day.
  * Parameters:
  *	$user - username
  *	$want_repeated - true to get repeating events; false to get
  *	  non-repeating.
  *	$date_filter - SQL phrase starting with AND, to be appended to
  *	  the WHERE clause.  May be empty string.
  *	$cat_id - category ID to filter on.  May be empty.
  * Returns:
  *	An array of events
  */
function query_events ( $user, $want_repeated, $date_filter, $cat_id = '' ) {
  global $login;
  global $layers, $public_access_default_visible;
  $result = array ();
  $layers_byuser = array ();

  $sql = "SELECT webcal_entry.cal_name, webcal_entry.cal_description, "
    . "webcal_entry.cal_date, webcal_entry.cal_time, "
    . "webcal_entry.cal_id, webcal_entry.cal_ext_for_id, "
    . "webcal_entry.cal_priority, "
    . "webcal_entry.cal_access, webcal_entry.cal_duration, "
    . "webcal_entry_user.cal_status, "
    . "webcal_entry_user.cal_category, "
    . "webcal_entry_user.cal_login ";
  if ( $want_repeated ) {
    $sql .= ", "
      . "webcal_entry_repeats.cal_type, webcal_entry_repeats.cal_end, "
      . "webcal_entry_repeats.cal_frequency, webcal_entry_repeats.cal_days "
      . "FROM webcal_entry, webcal_entry_repeats, webcal_entry_user "
      . "WHERE webcal_entry.cal_id = webcal_entry_repeats.cal_id AND ";
  } else {
    $sql .= "FROM webcal_entry, webcal_entry_user WHERE ";
  }
  $sql .= "webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND webcal_entry_user.cal_status IN ('A','W') ";

  if ( $cat_id != '' ) $sql .= "AND webcal_entry_user.cal_category LIKE '$cat_id' ";

  if ( strlen ( $user ) > 0 )
    $sql .= "AND (webcal_entry_user.cal_login = '" . $user . "' ";

  if ( $user == $login && strlen ( $user ) > 0 ) {
    if ($layers) foreach ($layers as $layer) {
      $layeruser = $layer['cal_layeruser'];

      $sql .= "OR webcal_entry_user.cal_login = '" . $layeruser . "' ";

      // while we are parsing the whole layers array, build ourselves
      // a new array that will help when we have to check for dups
      $layers_byuser["$layeruser"] = $layer['cal_dups'];
    }
  }
  if ( $user == $login && strlen ( $user ) &&
    $public_access_default_visible == 'Y' ) {
    $sql .= "OR webcal_entry_user.cal_login = '__public__' ";
  }
  if ( strlen ( $user ) > 0 )
    $sql .= ") ";
  $sql .= $date_filter;

  // now order the results by time and by entry id.
  $sql .= " ORDER BY webcal_entry.cal_time, webcal_entry.cal_id";

  //echo "<strong>SQL:</strong> $sql<br />\n";
  
  $res = dbi_query ( $sql );
  if ( $res ) {
    $i = 0;
    $checkdup_id = -1;
    $first_i_this_id = -1;

    while ( $row = dbi_fetch_row ( $res ) ) {

      if ($row[9] == 'R' || $row[9] == 'D') {
        continue;  // don't show rejected/deleted ones
      }
      $item = array (
        "cal_name" => $row[0],
        "cal_description" => $row[1],
        "cal_date" => $row[2],
        "cal_time" => $row[3],
        "cal_id"   => $row[4],
        "cal_ext_for_id"   => $row[5],
        "cal_priority" => $row[6],
        "cal_access" => $row[7],
        "cal_duration" => $row[8],
        "cal_status" => $row[9],
        "cal_category" => $row[10],
        "cal_login" => $row[11],
	"cal_exceptions" => array()
        );
      if ( $want_repeated && ! empty ( $row[12] ) ) {
        $item['cal_type'] = empty ( $row[12] ) ? "" : $row[12];
        $item['cal_end'] = empty ( $row[13] ) ? "" : $row[13];
        $item['cal_frequency'] = empty ( $row[14] ) ? "" : $row[14];
        $item['cal_days'] = empty ( $row[15] ) ? "" : $row[15];
      }

      if ( $item['cal_id'] != $checkdup_id ) {
        $checkdup_id = $item['cal_id'];
        $first_i_this_id = $i;
      }

      if ( $item['cal_login'] == $user ) {
        // Insert this one before all other ones with this ID.
        my_array_splice ( $result, $first_i_this_id, 0, array($item) );
        $i++;

        if ($first_i_this_id + 1 < $i) {
          // There's another one with the same ID as the one we inserted.
          // Check for dup and if so, delete it.
          $other_item = $result[$first_i_this_id + 1];
          if ($layers_byuser[$other_item['cal_login']] == 'N') {
            // NOTE: array_splice requires PHP4
            my_array_splice ( $result, $first_i_this_id + 1, 1, "" );
            $i--;
          }
        }
      } else {
        if ($i == $first_i_this_id
          || ( ! empty ( $layers_byuser[$item['cal_login']] ) &&
          $layers_byuser[$item['cal_login']] != 'N' ) ) {
          // This item either is the first one with its ID, or allows dups.
          // Add it to the end of the array.
          $result [$i++] = $item;
        }
      }
    }
    dbi_free_result ( $res );
  }

  // Now load event exceptions and store as array in 'cal_exceptions' field
  if ( $want_repeated ) {
    for ( $i = 0; $i < count ( $result ); $i++ ) {
      if ( ! empty ( $result[$i]['cal_id'] ) ) {
        $res = dbi_query ( "SELECT cal_date FROM webcal_entry_repeats_not " .
            "WHERE cal_id = " . $result[$i]['cal_id'] );
        while ( $row = dbi_fetch_row ( $res ) ) {
          $result[$i]['cal_exceptions'][] = $row[0];
        }
      }
    }
  }

  return $result;
}

/** read_repeated_events
  * Description:
  *	Read all the repeated events for a user. This is only called once
  *	per page request to improve performance. All the events get loaded
  *	into the array $repeated_events sorted by time of day (not date).
  *	This will load all the repeated events into memory.
  *	<br/>Notes:  <ul>
  *	<li> To get which events repeat on a specific date, use the
  *	     get_repeating_entries function.</li>
  *	<li> To get all the dates that one specific event repeats on, the
  *	     get_all_dates function should be called.  </li>
  *	</ul>
  * Parameters:
  *	$user   - username
  *	$cat_id - category ID to filter on  (May be empty)
  *	$date   - cutoff date for repeating event endtimes in YYYYMMDD
  *		  format (may be empty)
  * Returns:
  *	An array of repeating events
  */
function read_repeated_events ( $user, $cat_id = '', $date = ''  ) {
  global $login;
  global $layers;

  $filter = ($date != '') ? "AND (webcal_entry_repeats.cal_end >= $date OR webcal_entry_repeats.cal_end IS NULL) " : '';
  return query_events ( $user, true, $filter, $cat_id );
}

/** get_all_dates
  * Description:
  *	Returns all the dates a specific event will fall on accounting for
  *	the repeating. Any event with no end will be assigned one.
  * Parameters:
  *	$date - initial date in raw format
  *	$rpt_type - repeating type as stored in the database
  *	$end  - end date
  *	$days - days events occurs on (for weekly)
  *	$ex_dates - array of exception dates for this event in YYYYMMDD format
  *	$freq - frequency of repetition
  * Returns:
  *	An array of dates (in UNIX time format)
  */
function get_all_dates ( $date, $rpt_type, $end, $days, $ex_days, $freq=1 ) {
  global $conflict_repeat_months, $days_per_month, $ldays_per_month;
  global $ONE_DAY;
  //echo "get_all_dates ( $date, '$rpt_type', $end, '$days', [array], $freq ) <br>\n";
  $currentdate = floor($date/$ONE_DAY)*$ONE_DAY;
  $realend = floor($end/$ONE_DAY)*$ONE_DAY;
  $dateYmd = date ( "Ymd", $date );
  if ($end=='NULL') {
    // Check for $conflict_repeat_months months into future for conflicts
    $thismonth = substr($dateYmd, 4, 2);
    $thisyear = substr($dateYmd, 0, 4);
    $thisday = substr($dateYmd, 6, 2);
    $thismonth += $conflict_repeat_months;
    if ($thismonth > 12) {
      $thisyear++;
      $thismonth -= 12;
    }
    $realend = mktime(3,0,0,$thismonth,$thisday,$thisyear);
  }
  $ret = array();
  $ret[0] = $date;
  //do iterative checking here.
  //I floored the $realend so I check it against the floored date
  if ($rpt_type && $currentdate < $realend) {
    $cdate = $date;
    if (!$freq) $freq = 1;
    $n = 1;
    if ($rpt_type == 'daily') {
      //we do inclusive counting on end dates.
      $cdate += $ONE_DAY * $freq;
      while ($cdate <= $realend+$ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++]=$cdate;
        $cdate += $ONE_DAY * $freq;
      }
    } else if ($rpt_type == 'weekly') {
      $daysarray = array();
      $r=0;
      $dow = date("w",$date);
      $cdate = $date - ($dow * $ONE_DAY);
      for ($i = 0; $i < 7; $i++) {
        $isDay = substr($days, $i, 1);
        if (strcmp($isDay,"y")==0) {
          $daysarray[$r++]=$i * $ONE_DAY;
        }
      }
      //we do inclusive counting on end dates.
      while ($cdate <= $realend+$ONE_DAY) {
        //add all of the days of the week.
        for ($j=0; $j<$r;$j++) {
          $td = $cdate + $daysarray[$j];
          if ($td >= $date) {
            if ( ! is_exception ( $cdate, $ex_days ) )
              $ret[$n++] = $td;
          }
        }
        //skip to the next week in question.
        $cdate += ( $ONE_DAY * 7 ) * $freq;
      }
    } else if ($rpt_type == 'monthlyByDay') {
      $dow  = date('w', $date);
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $week  = floor(date("d", $date)/7);
      $thismonth+=$freq;
      //dow1 is the weekday that the 1st of the month falls on
      $dow1 = date('w',mktime (3,0,0,$thismonth,1,$thisyear));
      $t = $dow - $dow1;
      if ($t < 0) $t += 7;
      $day = 7*$week + $t + 1;
      $cdate = mktime (3,0,0,$thismonth,$day,$thisyear);
      while ($cdate <= $realend+$ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thismonth+=$freq;
        //dow1 is the weekday that the 1st of the month falls on
        $dow1time = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
        $dow1 = date ( 'w', $dow1time );
        $t = $dow - $dow1;
        if ($t < 0) $t += 7;
        $day = 7*$week + $t + 1;
        $cdate = mktime (3,0,0,$thismonth,$day,$thisyear);
      }
    } else if ($rpt_type == 'monthlyByDayR') {
      // by weekday of month reversed (i.e., last Monday of month)
      $dow  = date('w', $date);
      $thisday = substr($dateYmd, 6, 2);
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      // get number of days in this month
      $daysthismonth = $thisyear % 4 == 0 ? $ldays_per_month[$thismonth] :
        $days_per_month[$thismonth];
      // how many weekdays like this one remain in the month?
      // 0=last one, 1=one more after this one, etc.
      $whichWeek = floor ( ( $daysthismonth - $thisday ) / 7 );
      // find first repeat date
      $thismonth += $freq;
      if ( $thismonth > 12 ) {
        $thisyear++;
        $thismonth -= 12;
      }
      // get weekday for last day of month
      $dowLast += date('w',mktime (3,0,0,$thismonth + 1, -1,$thisyear));
      if ( $dowLast >= $dow ) {
        // last weekday is in last week of this month
        $day = $daysinmonth - ( $dowLast - $dow ) -
          ( 7 * $whichWeek );
      } else {
        // last weekday is NOT in last week of this month
        $day = $daysinmonth - ( $dowLast - $dow ) -
          ( 7 * ( $whichWeek + 1 ) );
      }
      $cdate = mktime (3,0,0,$thismonth,$day,$thisyear);
      while ($cdate <= $realend+$ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thismonth += $freq;
        if ( $thismonth > 12 ) {
          $thisyear++;
          $thismonth -= 12;
        }
        // get weekday for last day of month
        $dowLast += date('w',mktime (3,0,0,$thismonth + 1, -1,$thisyear));
        if ( $dowLast >= $dow ) {
          // last weekday is in last week of this month
          $day = $daysinmonth - ( $dowLast - $dow ) -
            ( 7 * $whichWeek );
        } else {
          // last weekday is NOT in last week of this month
          $day = $daysinmonth - ( $dowLast - $dow ) -
            ( 7 * ( $whichWeek + 1 ) );
        }
        $cdate = mktime (3,0,0,$thismonth,$day,$thisyear);
      }
    } else if ($rpt_type == 'monthlyByDate') {
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
      $hour      = date('H',$date);
      $minute    = date('i',$date);

      $thismonth += $freq;
      $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      while ($cdate <= $realend+$ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thismonth += $freq;
        $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      }
    } else if ($rpt_type == 'yearly') {
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
      $hour      = date('H',$date);
      $minute    = date('i',$date);

      $thisyear += $freq;
      $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      while ($cdate <= $realend+$ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thisyear += $freq;
        $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      }
    }
  }
  return $ret;
}

/** get_repeating_entries
  * Description:
  *	Get all the repeating events for the specified data and return them
  *	in an array (which is sorted by time of day).
  *	<br/>Note:
  *	The global variable <tt>$repeated_events</tt> needs to be
  *	set by calling the read_repeated_events function first.
  * Parameters:
  *	$user - username
  *	$date - date to get events for in YYYYMMDD format
  *	$get_unapproved - include unapproved events in results
  * Returns:
  *	The query result resource on queries (which can then be
  *	passed to the dbi_fetch_row function to obtain the results),
  *	or true/false on insert or delete queries.
  */
function get_repeating_entries ( $user, $dateYmd, $get_unapproved=true ) {
  global $repeated_events;
  $n = 0;
  $ret = array ();
  //echo count($repeated_events)."<br />\n";
  for ( $i = 0; $i < count ( $repeated_events ); $i++ ) {
    if ( $repeated_events[$i]['cal_status'] == 'A' || $get_unapproved ) {
      if ( repeated_event_matches_date ( $repeated_events[$i], $dateYmd ) ) {
        // make sure this is not an exception date...
        $unixtime = date_to_epoch ( $dateYmd );
        if ( ! is_exception ( $unixtime, $repeated_events[$i]['cal_exceptions'] ) )
          $ret[$n++] = $repeated_events[$i];
      }
    }
  }
  return $ret;
}

/** repeated_event_matches_date
  * Description:
  *	Returns a boolean stating whether or not the event passed
  *	in will fall on the date passed.
  * Parameters:
  *	$event - the event as an array
  *	$dateYmd - the date to check in YYYYMMDD format
  * Returns:
  *	true or false
  */
function repeated_event_matches_date($event,$dateYmd) {
  global $days_per_month, $ldays_per_month, $ONE_DAY;
  // only repeat after the beginning, and if there is an end
  // before the end
  $date = date_to_epoch ( $dateYmd );
  $thisyear = substr($dateYmd, 0, 4);
  $start = date_to_epoch ( $event['cal_date'] );
  $end   = date_to_epoch ( $event['cal_end'] );
  $freq = $event['cal_frequency'];
  $thismonth = substr($dateYmd, 4, 2);
  if ($event['cal_end'] && $dateYmd > date("Ymd",$end) )
    return false;
  if ( $dateYmd <= date("Ymd",$start) )
    return false;
  $id = $event['cal_id'];

  if ($event['cal_type'] == 'daily') {
    if ( (floor(($date - $start)/$ONE_DAY)%$freq) )
      return false;
    return true;
  } else if ($event['cal_type'] == 'weekly') {
    $dow  = date("w", $date);
    $dow1 = date("w", $start);
    $isDay = substr($event['cal_days'], $dow, 1);
    $wstart = $start - ($dow1 * $ONE_DAY);
    if (floor(($date - $wstart)/604800)%$freq)
      return false;
    return (strcmp($isDay,"y") == 0);
  } else if ($event['cal_type'] == 'monthlyByDay') {
    $dowS = date("w", $start);
    $dow  = date("w", $date);
    // do this comparison first in hopes of best performance
    if ( $dowS != $dow )
      return false;
    $mthS = date("m", $start);
    $yrS  = date("Y", $start);
    $dayS  = floor(date("d", $start));
    $dowS1 = ( date ( "w", $start - ( $ONE_DAY * ( $dayS - 1 ) ) ) + 35 ) % 7;
    $days_in_first_weekS = ( 7 - $dowS1 ) % 7;
    $whichWeekS = floor ( ( $dayS - $days_in_first_weekS ) / 7 );
    if ( $dowS >= $dowS1 && $days_in_first_weekS )
      $whichWeekS++;
    //echo "dayS=$dayS;dowS=$dowS;dowS1=$dowS1;wWS=$whichWeekS<br />\n";
    $mth  = date("m", $date);
    $yr   = date("Y", $date);
    $day  = date("d", $date);
    $dow1 = ( date ( "w", $date - ( $ONE_DAY * ( $day - 1 ) ) ) + 35 ) % 7;
    $days_in_first_week = ( 7 - $dow1 ) % 7;
    $whichWeek = floor ( ( $day - $days_in_first_week ) / 7 );
    if ( $dow >= $dow1 && $days_in_first_week )
      $whichWeek++;
    //echo "day=$day;dow=$dow;dow1=$dow1;wW=$whichWeek<br />\n";

    if ((($yr - $yrS)*12 + $mth - $mthS) % $freq)
      return false;

    return ( $whichWeek == $whichWeekS );
  } else if ($event['cal_type'] == 'monthlyByDayR') {
    $dowS = date("w", $start);
    $dow  = date("w", $date);
    // do this comparison first in hopes of best performance
    if ( $dowS != $dow )
      return false;

    $dayS = ceil(date("d", $start));
    $mthS = ceil(date("m", $start));
    $yrS  = date("Y", $start);
    $daysthismonthS = $mthS % 4 == 0 ? $ldays_per_month[$mthS] :
      $days_per_month[$mthS];
    $whichWeekS = floor ( ( $daysthismonthS - $dayS ) / 7 );

    $day = ceil(date("d", $date));
    $mth = ceil(date("m", $date));
    $yr  = date("Y", $date);
    $daysthismonth = $mth % 4 == 0 ? $ldays_per_month[$mth] :
      $days_per_month[$mth];
    $whichWeek = floor ( ( $daysthismonth - $day ) / 7 );

    if ((($yr - $yrS)*12 + $mth - $mthS) % $freq)
      return false;

    return ( $whichWeekS == $whichWeek );
  } else if ($event['cal_type'] == 'monthlyByDate') {
    $mthS = date("m", $start);
    $yrS  = date("Y", $start);

    $mth  = date("m", $date);
    $yr   = date("Y", $date);

    if ((($yr - $yrS)*12 + $mth - $mthS) % $freq)
      return false;

    return (date("d", $date) == date("d", $start));
  }
  else if ($event['cal_type'] == 'yearly') {
    $yrS = date("Y", $start);
    $yr  = date("Y", $date);

    if (($yr - $yrS)%$freq)
      return false;

    return (date("dm", $date) == date("dm", $start));
  } else {
    // unknown repeat type
    return false;
  }
  return false;
}

function date_to_epoch ( $d ) {
  if ( $d == 0 )
    return 0;
  $T = mktime ( 3, 0, 0, substr ( $d, 4, 2 ), substr ( $d, 6, 2 ), substr ( $d, 0, 4 ) );
  $lt = localtime($T);
  if ($lt[8]) {
    return mktime ( 4, 0, 0, substr ( $d, 4, 2 ), substr ( $d, 6, 2 ), substr ( $d, 0, 4 ) );
  } else {
    return $T;
  }
}

// check if a date is an exception for an event
// $date - date in timestamp format
// $exdays - array of dates in YYYYMMDD format
// [internal function to functions.php.  do not document.]
function is_exception ( $date, $ex_days ) {
  $size = count ( $ex_days );
  $count = 0;
  $date = date ( "Ymd", $date );
  //echo "Exception $date check.. count is $size <br />\n";
  while ( $count < $size ) {
    //echo "Exception date: $ex_days[$count] <br />\n";
    if ( $date == $ex_days[$count++] )
      return true;
  }
  return false;
}

/** get_sunday_before
  * Description:
  *	Get the Sunday of the week that the specified date is in.
  *	(If the date specified is a Sunday, then that date is returned.)
  * Parameters:
  *	$year - year in YYYY format
  *	$month - month (1-12)
  *	$day - day of the month
  * Returns:
  *	The date (in unix time format)
  */
function get_sunday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 3, 0, 0, $month, $day, $year ) );
  $newdate = mktime ( 3, 0, 0, $month, $day - $weekday, $year );
  return $newdate;
}

/** get_monday_before
  * Description:
  *	Get the Monday of the week that the specified date is in.
  *	(If the date specified is a Monday, then that date is returned.)
  * Parameters:
  *	$year - year in YYYY format
  *	$month - month (1-12)
  *	$day - day of the month
  * Returns:
  *	The date (in unix time format)
  */
function get_monday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 3, 0, 0, $month, $day, $year ) );
  if ( $weekday == 0 )
    return mktime ( 3, 0, 0, $month, $day - 6, $year );
  if ( $weekday == 1 )
    return mktime ( 3, 0, 0, $month, $day, $year );
  return mktime ( 3, 0, 0, $month, $day - ( $weekday - 1 ), $year );
}

/** week_number
  * Description:
  *	Returns week number for specified date.
  *	depending from week numbering settings.
  * Parameters:
  *	$date - date in UNIX time format
  * Returns:
  *	The week number of the specified date
  */
function week_number ( $date ) {
  $tmp = getdate($date);
  $iso = gregorianToISO($tmp['mday'], $tmp['mon'], $tmp['year']);
  $parts = explode('-',$iso);
  $week_number = intval($parts[1]);
  return sprintf("%02d",$week_number);
}

// This function is not yet used.  Some of the places that will call it
// have to be updated to also get the event owner so we know if the current
// user has access to edit and delete.
function icon_text ( $id, $can_edit, $can_delete ) {
  global $readonly, $is_admin;
  $ret = "<a title=\"" . 
	translate("View this entry") . "\" href=\"view_entry.php?id=$id\"><img src=\"view.gif\" alt=\"" . 
	translate("View this entry") . "\" style=\"border-width:0px; width:10px; height:10px;\" /></a>";
  if ( $can_edit && $readonly == "N" )
    $ret .= "<a title=\"" . 
	translate("Edit entry") . "\" href=\"edit_entry.php?id=$id\"><img src=\"edit.gif\" alt=\"" . 
	translate("Edit entry") . "\" style=\"border-width:0px; width:10px; height:10px;\" /></a>";
  if ( $can_delete && ( $readonly == "N" || $is_admin ) )
    $ret .= "<a title=\"" . 
    	translate("Delete entry") . "\" href=\"del_entry.php?id=$id\" onclick=\"return confirm('" .
	translate("Are you sure you want to delete this entry?") . "\\n\\n" . 
	translate("This will delete this entry for all users.") . "');\"><img src=\"delete.gif\" alt=\"" . 
	translate("Delete entry") . "\" style=\"border-width:0px; width:10px; height:10px;\" /></a>";
  return $ret;
}

/** print_date_entries
  * Description:
  *	Print all the calendar entries for the specified user for the
  *	specified date.  If we are displaying data from someone other than
  *	the logged in user, then check the access permission of the entry.
  * Parameters:
  *	$date - date in YYYYMMDD format
  *	$user - username
  *	$is_ssi - is this being called from week_ssi.php?  If so, then
  */
function print_date_entries ( $date, $user, $ssi ) {
  global $events, $readonly, $is_admin, $login,
    $public_access, $public_access_can_add;
  $cnt = 0;
  $get_unapproved = ( $GLOBALS["DISPLAY_UNAPPROVED"] == "Y" );
  // public access events always must be approved before being displayed
  if ( $user == "__public__" )
    $get_unapproved = false;

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );
  $dateu = mktime ( 3, 0, 0, $month, $day, $year );
  $can_add = ( $readonly == "N" || $is_admin );
  if ( $public_access == "Y" && $public_access_can_add != "Y" &&
    $login == "__public__" )
    $can_add = false;
  if ( $readonly == 'Y' )
    $can_add = false;
  if ( ! $ssi && $can_add ) {
    print "<a title=\"" .
      translate("New Entry") . "\" href=\"edit_entry.php?";
    if ( strcmp ( $user, $GLOBALS["login"] ) )
      print "user=$user&amp;";
    print "date=$date\"><img src=\"new.gif\" alt=\"" .
      translate("New Entry") . "\" class=\"new\" /></a>";
    $cnt++;
  }
  if ( ! $ssi ) {
    echo "<a class=\"dayofmonth\" href=\"day.php?";
    if ( strcmp ( $user, $GLOBALS["login"] ) )
      echo "user=$user&amp;";
    echo "date=$date\">$day</a>";
    if ( $GLOBALS["DISPLAY_WEEKNUMBER"] == "Y" &&
      date ( "w", $dateu ) == $GLOBALS["WEEK_START"] ) {
      echo "&nbsp;<a title=\"" .
        translate("Week") . "&nbsp;" . week_number ( $dateu ) . "\" href=\"week.php?date=$date";
      if ( strcmp ( $user, $GLOBALS["login"] ) )
        echo "&amp;user=$user";
       echo "\" class=\"weeknumber\">";
      echo "(" .
        translate("Week") . "&nbsp;" . week_number ( $dateu ) . ")</a>";
    }
    print "<br />\n";
    $cnt++;
  }

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date, $get_unapproved );
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $user, $date, $get_unapproved );

  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
        if ( ! empty ( $rep[$cur_rep]['cal_ext_for_id'] ) ) {
          $viewid = $rep[$cur_rep]['cal_ext_for_id'];
          $viewname = $rep[$cur_rep]['cal_name'] . " (" .
            translate("cont.") . ")";
        } else {
          $viewid = $rep[$cur_rep]['cal_id'];
          $viewname = $rep[$cur_rep]['cal_name'];
        }
        print_entry ( $viewid,
          $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
          $viewname, $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'],
          $rep[$cur_rep]['cal_category'] );
        $cnt++;
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]['cal_status'] == 'A' ) {
      if ( ! empty ( $ev[$i]['cal_ext_for_id'] ) ) {
        $viewid = $ev[$i]['cal_ext_for_id'];
        $viewname = $ev[$i]['cal_name'] . " (" .
          translate("cont.") . ")";
      } else {
        $viewid = $ev[$i]['cal_id'];
        $viewname = $ev[$i]['cal_name'];
      }
      print_entry ( $viewid,
        $date, $ev[$i]['cal_time'], $ev[$i]['cal_duration'],
        $viewname, $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_login'],
        $ev[$i]['cal_category'] );
      $cnt++;
    }
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
      if ( ! empty ( $rep[$cur_rep]['cal_ext_for_id'] ) ) {
        $viewid = $rep[$cur_rep]['cal_ext_for_id'];
        $viewname = $rep[$cur_rep]['cal_name'] . " (" .
          translate("cont.") . ")";
      } else {
        $viewid = $rep[$cur_rep]['cal_id'];
        $viewname = $rep[$cur_rep]['cal_name'];
      }
      print_entry ( $viewid,
        $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
        $viewname, $rep[$cur_rep]['cal_description'],
        $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'],
        $rep[$cur_rep]['cal_category'] );
      $cnt++;
    }
    $cur_rep++;
  }
  if ( $cnt == 0 )
    echo "&nbsp;"; // so the table cell has at least something
}

/** times_overlap
  * Description:
  *	Check to see if two events overlap.
  * Parameters:
  *	$time1 - time 1 in HHMMSS format
  *	$duration1 - duration 1 in minutes
  *	$time2 - time 2 in HHMMSS format
  *	$duration1 - duration 1 in minutes
  * Returns:
  *	true if the two times overlap, false if they do not
  */
function times_overlap ( $time1, $duration1, $time2, $duration2 ) {
  //echo "times_overlap ( $time1, $duration1, $time2, $duration2 )<br />\n";
  $hour1 = (int) ( $time1 / 10000 );
  $min1 = ( $time1 / 100 ) % 100;
  $hour2 = (int) ( $time2 / 10000 );
  $min2 = ( $time2 / 100 ) % 100;
  // convert to minutes since midnight
  // remove 1 minute from duration so 9AM-10AM will not conflict with 10AM-11AM
  if ( $duration1 > 0 )
    $duration1 -= 1;
  if ( $duration2 > 0 )
    $duration2 -= 1;
  $tmins1start = $hour1 * 60 + $min1;
  $tmins1end = $tmins1start + $duration1;
  $tmins2start = $hour2 * 60 + $min2;
  $tmins2end = $tmins2start + $duration2;
  //echo "tmins1start=$tmins1start, tmins1end=$tmins1end, tmins2start=$tmins2start, tmins2end=$tmins2end<br />\n";
  if ( ( $tmins1start >= $tmins2end ) || ( $tmins2start >= $tmins1end ) )
    return false;
  return true;
}

/** check_for_conflicts
  * Description:
  *	Check for conflicts.
  *	Find overlaps between an array of dates and the other dates in
  *	the database.
  *	<br/>Limits on number of appointments: if enabled in
  *	System Settings ($limit_appts global variable),
  *	too many appointments can also generate a scheduling conflict.
  *	<br/>TODO: Update this to handle exceptions to repeating events
  * Parameters:
  *	$date - is an array of dates in YYYYMMDD format that is checked
  *	  for overlaps.
  *	$duration - event duration in minutes
  *	$hour - hour of event (0-23)
  *	$minute - minute of the event (0-59)
  *	$participants - an array of users whose calendars are to be checked
  *	$login - the current user name
  *	$id - current event id
  *	  (this keeps overlaps from wrongly checking an event against itself)
  * Returns:
  *	Return empty string for no conflicts or return the HTML of the
  *	conflicts when one or more are found.
  */
function check_for_conflicts ( $dates, $duration, $hour, $minute,
  $participants, $login, $id ) {
  global $single_user_login, $single_user;
  global $repeated_events, $limit_appts, $limit_appts_number;
  if (!count($dates)) return false;

  $evtcnt = array ();

  $sql = "SELECT distinct webcal_entry_user.cal_login, webcal_entry.cal_time," .
    "webcal_entry.cal_duration, webcal_entry.cal_name, " .
    "webcal_entry.cal_id, webcal_entry.cal_ext_for_id, " .
    "webcal_entry.cal_access, " .
    "webcal_entry_user.cal_status, webcal_entry.cal_date " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND (";
  for ($x = 0; $x < count($dates); $x++) {
    if ($x != 0) $sql .= " OR ";
    $sql.="webcal_entry.cal_date = " . date ( "Ymd", $dates[$x] );
  }
  $sql .=  ") AND webcal_entry.cal_time >= 0 " .
    "AND webcal_entry_user.cal_status IN ('A','W') AND ( ";
  if ( $single_user == "Y" ) {
     $participants[0] = $single_user_login;
  } else if ( strlen ( $participants[0] ) == 0 ) {
     // likely called from a form with 1 user
     $participants[0] = $login;
  }
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    if ( $i > 0 )
      $sql .= " OR ";
    $sql .= " webcal_entry_user.cal_login = '" . $participants[$i] . "'";
  }
  $sql .= " )";
  // make sure we don't get something past the end date of the
  // event we are saving.
  //echo "SQL: $sql<br />\n";
  $conflicts = "";
  $res = dbi_query ( $sql );
  $found = array();
  $count = 0;
  if ( $res ) {
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    while ( $row = dbi_fetch_row ( $res ) ) {
      //Add to an array to see if it has been found already for the next part.
      $found[$count++] = $row[4];
      // see if either event overlaps one another
      if ( $row[4] != $id && ( empty ( $row[5] ) || $row[5] != $id ) ) {
        $time2 = $row[1];
        $duration2 = $row[2];
        $cntkey = $row[0] . "-" . $row[8];
        if ( empty ( $evtcnt[$cntkey] ) )
          $evtcnt[$cntkey] = 0;
        else
          $evtcnt[$cntkey]++;
        $over_limit = 0;
        if ( $limit_appts == "Y" && $limit_appts_number > 0
          && $evtcnt[$cntkey] >= $limit_appts_number ) {
          $over_limit = 1;
        }
        if ( $over_limit ||
          times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
          $conflicts .= "<li>";
          if ( $single_user != "Y" )
            $conflicts .= "$row[0]: ";
          if ( $row[6] == 'R' && $row[0] != $login )
            $conflicts .=  "(" . translate("Private") . ")";
          else {
            $conflicts .=  "<a href=\"view_entry.php?id=$row[4]";
            if ( $row[0] != $login )
              $conflicts .= "&amp;user=$row[0]";
            $conflicts .= "\">$row[3]</a>";
          }
          if ( $duration2 == ( 24 * 60 ) ) {
            $conflicts .= " (" . translate("All day event") . ")";
          } else {
            $conflicts .= " (" . display_time ( $time2 );
            if ( $duration2 > 0 )
              $conflicts .= "-" .
                display_time ( add_duration ( $time2, $duration2 ) );
            $conflicts .= ")";
          }
          $conflicts .= " on " . date_to_str( $row[8] );
          if ( $over_limit ) {
            $tmp = translate ( "exceeds limit of XXX events per day" );
            $tmp = str_replace ( "XXX", $limit_appts_number, $tmp );
            $conflicts .= " (" . $tmp . ")";
          }
          $conflicts .= "</li>\n";
        }
      }
    }
    dbi_free_result ( $res );
  } else {
    echo translate("Database error") . ": " . dbi_error (); exit;
  }
  
  //echo "<br />\nhello";
  for ($q=0;$q<count($participants);$q++) {
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    //This date filter is not necessary for functional reasons, but it eliminates some of the
    //events that couldn't possibly match.  This could be made much more complex to put more
    //of the searching work onto the database server, or it could be dropped all together to put
    //the searching work onto the client.
    $date_filter  = "AND (webcal_entry.cal_date <= " . date("Ymd",$dates[count($dates)-1]);
    $date_filter .= " AND (webcal_entry_repeats.cal_end IS NULL OR webcal_entry_repeats.cal_end >= " . date("Ymd",$dates[0]) . "))";
    //Read repeated events for the participants only once for a participant for
    //for performance reasons.
    $repeated_events=query_events($participants[$q],true,$date_filter);
    //for ($dd=0; $dd<count($repeated_events); $dd++) {
    //  echo $repeated_events[$dd]['cal_id'] . "<br />";
    //}
    for ($i=0; $i < count($dates); $i++) {
      $dateYmd = date ( "Ymd", $dates[$i] );
      $list = get_repeating_entries($participants[$q],$dateYmd);
      $thisyear = substr($dateYmd, 0, 4);
      $thismonth = substr($dateYmd, 4, 2);
      for ($j=0; $j < count($list);$j++) {
        //okay we've narrowed it down to a day, now I just gotta check the time...
        //I hope this is right...
        $row = $list[$j];
        if ( $row['cal_id'] != $id && ( empty ( $row['cal_ext_for_id'] ) || 
          $row['cal_ext_for_id'] != $id ) ) {
          $time2 = $row['cal_time'];
          $duration2 = $row['cal_duration'];
          if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
            $conflicts .= "<li>";
            if ( $single_user != "Y" )
              $conflicts .= $row['cal_login'] . ": ";
            if ( $row['cal_access'] == 'R' && $row['cal_login'] != $login )
              $conflicts .=  "(" . translate("Private") . ")";
            else {
              $conflicts .=  "<a href=\"view_entry.php?id=" . $row['cal_id'];
              if ( $user != $login )
                $conflicts .= "&amp;user=$user";
              $conflicts .= "\">" . $row['cal_name'] . "</a>";
            }
            $conflicts .= " (" . display_time ( $time2 );
            if ( $duration2 > 0 )
              $conflicts .= "-" .
                display_time ( add_duration ( $time2, $duration2 ) );
            $conflicts .= ")";
            $conflicts .= " on " . date("l, F j, Y", $dates[$i]);
            $conflicts .= "</li>\n";
          }
        }
      }
    }
  }
   
  return $conflicts;
}

/** time_to_minutes
  * Description:
  *	Convert a time format HHMMSS (like 131000 for 1PM) into number of
  *	minutes past midnight.
  * Parameters:
  *	$time - input time in HHMMSS format
  * Returns:
  *	The number of minutes since midnight
  */
function time_to_minutes ( $time ) {
  $h = (int) ( $time / 10000 );
  $m = (int) ( $time / 100 ) % 100;
  $num = $h * 60 + $m;
  return $num;
}

/** calc_time_slot
  * Description:
  *	Calculate which row/slot this time represents.
  *	This is used in day and week views where hours of the time
  *	are separeted into different cells in a table.
  * Parameters:
  *	$time - input time in YYYYMMDD format
  *	$round_down - should we change 1100 to 1059?
  *	  (This will make sure a 10AM-100AM appointment just
  *	  shows up in the 10AM slow and not in the 11AM slot also.)
  *	<br/>Note: the global variable $TIME_SLOTS is used to determine
  *	how many time slots there are and how many minutes each is.
  *	This variable is defined user preferences (or defaulted to
  *	admin system settings).
  * Returns:
  *	The time slot index
  */
function calc_time_slot ( $time, $round_down = false ) {
  global $TIME_SLOTS, $TZ_OFFSET;

  $interval = ( 24 * 60 ) / $TIME_SLOTS;
  $mins_since_midnight = time_to_minutes ( $time );
  $ret = (int) ( $mins_since_midnight / $interval );
  if ( $round_down ) {
    if ( $ret * $interval == $mins_since_midnight )
      $ret--;
  }
  //echo "$mins_since_midnight / $interval = $ret <br />\n";
  if ( $ret > $TIME_SLOTS )
    $ret = $TIME_SLOTS;

  //echo "<br />\ncalc_time_slot($time) = $ret <br />\nTIME_SLOTS = $TIME_SLOTS<br />\n";
  return $ret;
}

/** html_for_add_icon
  * Description:
  *	Generate the HTML for an icon to add a new event.
  * Parameters:
  *	$date = date for new event in YYYYMMDD format
  *	$hour = hour of day (eg. 1,13,23) (optional)
  *	$user = participant to initially select for new event (optional)
  * Returns:
  *	The HTML for the add event icon
  */
function html_for_add_icon ( $date=0,$hour="", $minute="", $user="" ) {
  global $TZ_OFFSET;
  global $login, $readonly;
  $u_url = '';

  if ( $readonly == 'Y' )
    return '';

  if ( ! empty ( $user ) && $user != $login )
    $u_url = "user=$user&amp;";
  if ( ! empty ( $hour ) )
    $hour += $TZ_OFFSET;
  return "<a title=\"" . 
	translate("New Entry") . "\" href=\"edit_entry.php?" . $u_url .
    "date=$date" . ( $hour > 0 ? "&amp;hour=$hour" : "" ) .
    ( $minute > 0 ? "&amp;minute=$minute" : "" ) .
    ( empty ( $user ) ? "" :  "&amp;defusers=$user&amp;user=$user" ) .
    "\"><img src=\"new.gif\" class=\"new\" alt=\"" . 
	translate("New Entry") . "\" /></a>\n";
}

/** html_for_event_week_at_a_glance
  * Description:
  *	Generate the HTML for an event to be viewed in the week-at-glance
  *	(week.php).
  *	The HTML will be stored in an array (global variable $hour_arr)
  *	indexed on the event's starting hour.
  * Parameters:
  *	$id - event id
  *	$date - date of event in YYYYMMDD format
  *	$time - time of event in HHMM format
  *	$name - brief description of event
  *	$description - full description of event
  *	$status - status of event ('A', 'W')
  *	$pri - priority of event
  *	$access - access to event by others ('P', 'R')
  *	$duration - duration of event in minutes
  *	$event_owner - user who created event
  *	$event_category - category id for event
  */
function html_for_event_week_at_a_glance ( $id, $date, $time,
  $name, $description, $status, $pri, $access, $duration, $event_owner,
  $event_category=-1 ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user;
  static $key = 0;
  global $DISPLAY_ICONS, $PHP_SELF, $TIME_SLOTS;
  global $layers;

  $popupid = "eventinfo-day-$id-$key";
  $key++;
  
  // Figure out which time slot it goes in.
  if ( $time >= 0 && $duration != ( 24 * 60 ) ) {
    $ind = calc_time_slot ( $time );
    if ( $ind < $first_slot )
      $first_slot = $ind;
    if ( $ind > $last_slot )
      $last_slot = $ind;
  } else
    $ind = 9999;

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" ) $class = "unapprovedentry";
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" ) ||
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  // avoid php warning for undefined array index
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  $catIcon = "icons/cat-" . $event_category . ".gif";
  if ( $event_category > 0 && file_exists ( $catIcon ) ) {
    $hour_arr[$ind] .= "<img src=\"$catIcon\" alt=\"$catIcon\" />";
  }

  $hour_arr[$ind] .= "<a title=\"" . 
	translate("View this entry") . "\" class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $GLOBALS["user"] ) > 0 )
    $hour_arr[$ind] .= "&amp;user=" . $GLOBALS["user"];
  $hour_arr[$ind] .= "\" onmouseover=\"window.status='" .
    translate("View this entry") . "'; show(event, '$popupid'); return true;\" onmouseout=\"hide('$popupid'); return true;\">";
  if ( $pri == 3 )
    $hour_arr[$ind] .= "<strong>";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event_owner ) {
        $hour_arr[$ind] .= "<span style=\"color:" . $layer['cal_color'] . ";\">";
      }
    }
  }
  if ( $duration == ( 24 * 60 ) ) {
    $timestr = translate("All day event");
  } else if ( $time >= 0 ) {
    $hour_arr[$ind] .= display_time ( $time ) . "&raquo;&nbsp;";
    $timestr = display_time ( $time );
    if ( $duration > 0 ) {
      // calc end time
      $h = (int) ( $time / 10000 );
      $m = ( $time / 100 ) % 100;
      $m += $duration;
      $d = $duration;
      while ( $m >= 60 ) {
        $h++;
        $m -= 60;
      }
      $end_time = sprintf ( "%02d%02d00", $h, $m );
      $timestr .= "-" . display_time ( $end_time );
    } else {
      $end_time = 0;
    }
    if ( empty ( $rowspan_arr[$ind] ) )
      $rowspan_arr[$ind] = 0; // avoid warning below
    // which slot is end time in? take one off so we don't
    // show 11:00-12:00 as taking up both 11 and 12 slots.
    $endind = calc_time_slot ( $end_time, true );
    if ( $endind == $ind )
      $rowspan = 0;
    else
      $rowspan = $endind - $ind + 1;
    if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
      $rowspan_arr[$ind] = $rowspan;
  } else {
    $timestr = "";
  }

  // avoid php warning of undefined index when using .= below
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  if ( $login != $user && $access == 'R' && strlen ( $user ) ) {
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  } else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) ) {
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  } else if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
    $hour_arr[$ind] .= "</span>"; //end color span
  } else {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  }

  if ( $pri == 3 ) $hour_arr[$ind] .= "</strong>"; //end font-weight span
    $hour_arr[$ind] .= "</a>";
  //if ( $DISPLAY_ICONS == "Y" ) {
  //  $hour_arr[$ind] .= icon_text ( $id, true, true );
  //}
  $hour_arr[$ind] .= "<br />\n";
  if ( $login != $user && $access == 'R' && strlen ( $user ) ) {
    $eventinfo .= build_event_popup ( $popupid, $event_owner,
      translate("This event is confidential"), "" );
  } else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) ) {
    $eventinfo .= build_event_popup ( $popupid, $event_owner,
      translate("This event is confidential"), "" );
  } else {
    $eventinfo .= build_event_popup ( $popupid, $event_owner,
      $description, $timestr, site_extras_for_popup ( $id ) );
  }
}

/** html_for_event_day_at_a_glance
  * Description:
  *	Generate the HTML for an event to be viewed in the day-at-glance
  *	(day.php).
  *	The HTML will be stored in an array ($hour_arr) indexed on the event's
  *	starting hour.
  * Parameters:
  *	$id - event id
  *	$date - date of event in YYYYMMDD format
  *	$time - time of event in HHMM format
  *	$name - brief description of event
  *	$description - full description of event
  *	$status - status of event ('A', 'W')
  *	$pri - priority of event
  *	$access - access to event by others ('P', 'R')
  *	$duration - duration of event in minutes
  *	$event_owner - user who created event
  *	$event_category - category id for event
  */
function html_for_event_day_at_a_glance ( $id, $date, $time,
  $name, $description, $status, $pri, $access, $duration, $event_owner,
  $event_category=-1 ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user;
  static $key = 0;
  global $layers, $PHP_SELF, $TIME_SLOTS, $TZ_OFFSET;

  $popupid = "eventinfo-day-$id-$key";
  $key++;

  if ( $login != $user && $access == 'R' && strlen ( $user ) )
    $eventinfo .= build_event_popup ( $popupid, $event_owner,
      translate("This event is confidential"), "" );
  else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) )
    $eventinfo .= build_event_popup ( $popupid, $event_owner,
      translate("This event is confidential"), "" );
  else
    $eventinfo .= build_event_popup ( $popupid, $event_owner, $description,
      "", site_extras_for_popup ( $id ) );

  // calculate slot length in minutes
  $interval = ( 60 * 24 ) / $TIME_SLOTS;

  // If TZ_OFFSET make this event before the start of the day or
  // after the end of the day, adjust the time slot accordingly.
  if ( $time >= 0 && $duration != ( 24 * 60 ) ) {
    if ( $time + ( $TZ_OFFSET * 10000 ) > 240000 )
      $time -= 240000;
    else if ( $time + ( $TZ_OFFSET * 10000 ) < 0 )
      $time += 240000;
    $ind = calc_time_slot ( $time );
    if ( $ind < $first_slot )
      $first_slot = $ind;
    if ( $ind > $last_slot )
      $last_slot = $ind;
  } else
    $ind = 9999;
  //echo "time = $time <br />\nind = $ind <br />\nfirst_slot = $first_slot<br />\n";

  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" )
      $class = "unapprovedentry";
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" )  || 
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  $catIcon = "icons/cat-" . $event_category . ".gif";
  if ( $event_category > 0 && file_exists ( $catIcon ) ) {
    $hour_arr[$ind] .= "<img src=\"$catIcon\" alt=\"$catIcon\" />";
  }

  $hour_arr[$ind] .= "<a title=\"" .
    translate("View this entry") . "\" class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $GLOBALS["user"] ) > 0 )
    $hour_arr[$ind] .= "&amp;user=" . $GLOBALS["user"];
  $hour_arr[$ind] .= "\" onmouseover=\"window.status='" .
    translate("View this entry") . "'; show(event, '$popupid'); return true;\" onmouseout=\"hide('$popupid'); return true;\">";
  if ( $pri == 3 ) $hour_arr[$ind] .= "<strong>";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event_owner) {
        $hour_arr[$ind] .= "<span style=\"color:" . $layer['cal_color'] . ";\">";
      }
    }
  }

  if ( $duration == ( 24 * 60 ) ) {
    $hour_arr[$ind] .= "[" . translate("All day event") . "] ";
  } else if ( $time >= 0 ) {
    $hour_arr[$ind] .= "[" . display_time ( $time );
    if ( $duration > 0 ) {
      // calc end time
      $h = (int) ( $time / 10000 );
      $m = ( $time / 100 ) % 100;
      $m += $duration;
      $d = $duration;
      while ( $m >= 60 ) {
        $h++;
        $m -= 60;
      }
      $end_time = sprintf ( "%02d%02d00", $h, $m );
      $hour_arr[$ind] .= "-" . display_time ( $end_time );
      // which slot is end time in? take one off so we don't
      // show 11:00-12:00 as taking up both 11 and 12 slots.
      $endind = calc_time_slot ( $end_time, true );
      if ( $endind == $ind )
        $rowspan = 0;
      else
        $rowspan = $endind - $ind + 1;
      if ( ! isset ( $rowspan_arr[$ind] ) )
        $rowspan_arr[$ind] = 0;
      if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
        $rowspan_arr[$ind] = $rowspan;
    }
    $hour_arr[$ind] .= "] ";
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) )
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  else
  if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  else
  if ( $login != $event_owner && strlen ( $event_owner ) )
  {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
    $hour_arr[$ind] .= "</span>"; //end color span
  }

  else
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  if ( $pri == 3 ) $hour_arr[$ind] .= "</strong>"; //end font-weight span

  $hour_arr[$ind] .= "</a>";
  if ( $GLOBALS["DISPLAY_DESC_PRINT_DAY"] == "Y" ) {
    $hour_arr[$ind] .= "\n<dl class=\"desc\">\n";
    $hour_arr[$ind] .= "<dt>Description:</dt>\n<dd>";
    $hour_arr[$ind] .= htmlspecialchars ( $description );
    $hour_arr[$ind] .= "</dd>\n</dl>\n";
  }

  $hour_arr[$ind] .= "<br />\n";
}

/** print_day_at_a_glance
  * Description:
  *	Print all the calendar entries for the specified user for the
  *	specified date in day-at-a-glance format.
  *	If we are displaying data from someone other than
  *	the logged in user, then check the access permission of the entry.
  * Parameters:
  *	$date - date in YYYYMMDD format
  *	$user - username of calendar
  */
function print_day_at_a_glance ( $date, $user, $can_add=0 ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan;
  global $TABLEBG, $CELLBG, $TODAYCELLBG, $THFG, $THBG, $TIME_SLOTS, $TZ_OFFSET;
  global $WORK_DAY_START_HOUR, $WORK_DAY_END_HOUR;
  global $repeated_events;
  $get_unapproved = ( $GLOBALS["DISPLAY_UNAPPROVED"] == "Y" );
  if ( $user == "__public__" )
    $get_unapproved = false;
  if ( empty ( $TIME_SLOTS ) ) {
    echo "Error: TIME_SLOTS undefined!<br />\n";
    return;
  }

  // $interval is number of minutes per slot
  $interval = ( 24 * 60 ) / $TIME_SLOTS;
    
  $rowspan_arr = array ();
  for ( $i = 0; $i < $TIME_SLOTS; $i++ ) {
    $rowspan_arr[$i] = 0;
  }

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // Get static non-repeating events
  $ev = get_entries ( $user, $date, $get_unapproved );
  $hour_arr = array ();
  $interval = ( 24 * 60 ) / $TIME_SLOTS;
  $first_slot = (int) ( ( ( $WORK_DAY_START_HOUR - $TZ_OFFSET ) * 60 ) / $interval );
  $last_slot = (int) ( ( ( $WORK_DAY_END_HOUR - $TZ_OFFSET ) * 60 ) / $interval);
  //echo "first_slot = $first_slot<br />\nlast_slot = $last_slot<br />\ninterval = $interval<br />\nTIME_SLOTS = $TIME_SLOTS<br />\n";
  $rowspan_arr = array ();
  $all_day = 0;
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
        if ( ! empty ( $rep[$cur_rep]['cal_ext_for_id'] ) ) {
          $viewid = $rep[$cur_rep]['cal_ext_for_id'];
          $viewname = $rep[$cur_rep]['cal_name'] . " (" .
            translate("cont.") . ")";
        } else {
          $viewid = $rep[$cur_rep]['cal_id'];
          $viewname = $rep[$cur_rep]['cal_name'];
        }
        if ( $rep[$cur_rep]['cal_duration'] == ( 24 * 60 ) )
          $all_day = 1;
        html_for_event_day_at_a_glance ( $viewid,
          $date, $rep[$cur_rep]['cal_time'],
          $viewname, $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_duration'],
          $rep[$cur_rep]['cal_login'], $rep[$cur_rep]['cal_category'] );
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]['cal_status'] == 'A' ) {
      if ( ! empty ( $ev[$i]['cal_ext_for_id'] ) ) {
        $viewid = $ev[$i]['cal_ext_for_id'];
        $viewname = $ev[$i]['cal_name'] . " (" .
          translate("cont.") . ")";
      } else {
        $viewid = $ev[$i]['cal_id'];
        $viewname = $ev[$i]['cal_name'];
      }
      if ( $ev[$i]['cal_duration'] == ( 24 * 60 ) )
        $all_day = 1;
      html_for_event_day_at_a_glance ( $viewid,
        $date, $ev[$i]['cal_time'],
        $viewname, $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_duration'],
        $ev[$i]['cal_login'], $ev[$i]['cal_category'] );
    }
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
      if ( ! empty ( $rep[$cur_rep]['cal_ext_for_id'] ) ) {
        $viewid = $rep[$cur_rep]['cal_ext_for_id'];
        $viewname = $rep[$cur_rep]['cal_name'] . " (" .
          translate("cont.") . ")";
      } else {
        $viewid = $rep[$cur_rep]['cal_id'];
        $viewname = $rep[$cur_rep]['cal_name'];
      }
      if ( $rep[$cur_rep]['cal_duration'] == ( 24 * 60 ) )
        $all_day = 1;
      html_for_event_day_at_a_glance ( $viewid,
        $date, $rep[$cur_rep]['cal_time'],
        $viewname, $rep[$cur_rep]['cal_description'],
        $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_duration'],
        $rep[$cur_rep]['cal_login'], $rep[$cur_rep]['cal_category'] );
    }
    $cur_rep++;
  }

  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  //echo "First SLot: $first_slot; Last Slot: $last_slot<br />\n";
  $i = 0;
  if ( $first_slot < 0 )
    $i = $first_slot;
  for ( ; $i < $TIME_SLOTS; $i++ ) {
    if ( $rowspan > 1 ) {
      if ( ! empty ( $hour_arr[$i] ) ) {
        if ( $rowspan_arr[$i] > 1 ) {
          $rowspan_arr[$last_row] += ( $rowspan_arr[$i] - 1 );
          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else
          $rowspan_arr[$last_row] += $rowspan_arr[$i];
        // this will move entries apart that appear in one field,
        // yet start on different hours
        $start_time = $i;
        $diff_start_time = $start_time - $last_row;
        for ( $u = $diff_start_time ; $u > 0 ; $u-- )
          $hour_arr[$last_row] .= "<br />\n";
        $hour_arr[$last_row] .= $hour_arr[$i];
        $hour_arr[$i] = "";
        $rowspan_arr[$i] = 0;
      }
      $rowspan--;
    } else if ( ! empty ( $rowspan_arr[$i] ) && $rowspan_arr[$i] > 1 ) {
      $rowspan = $rowspan_arr[$i];
      $last_row = $i;
    }
  }
  if ( ! empty ( $hour_arr[9999] ) ) {
    echo "<tr><th class=\"empty\">&nbsp;</th>\n<td>$hour_arr[9999]</td></tr>\n";
  }
  $rowspan = 0;
  //echo "first_slot = $first_slot<br />\nlast_slot = $last_slot<br />\ninterval = $interval<br />\n";
  for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
    $time_h = (int) ( ( $i * $interval ) / 60 );
    $time_m = ( $i * $interval ) % 60;
    $time = display_time ( ( $time_h * 100 + $time_m ) * 100 );
    echo "<tr>\n<th class=\"row\">" . $time . "</th>\n";
    if ( $rowspan > 1 ) {
      // this might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( ! empty ( $hour_arr[$i] ) ) {
        echo "<td>";
        if ( $can_add )
          echo html_for_add_icon ( $date, $time_h, $time_m, $user );
        echo "$hour_arr[$i]</td>\n";
      }
      $rowspan--;
    } else {
      if ( empty ( $hour_arr[$i] ) ) {
        echo "<td>";
        if ( $can_add ) {
          echo html_for_add_icon ( $date, $time_h, $time_m, $user ) . "</td>";
	} else {
	  echo "&nbsp;</td>";
	}
        echo "</tr>\n";
      } else {
        if ( empty ( $rowspan_arr[$i] ) )
          $rowspan = '';
        else
          $rowspan = $rowspan_arr[$i];
        if ( $rowspan > 1 ) {
          echo "<td rowspan=\"$rowspan\">";
          if ( $can_add )
            echo html_for_add_icon ( $date, $time_h, $time_m, $user );
          echo "$hour_arr[$i]</td></tr>\n";
        } else {
          echo "<td>";
          if ( $can_add )
            echo html_for_add_icon ( $date, $time_h, $time_m, $user );
          echo "$hour_arr[$i]</td></tr>\n";
        }
      }
    }
  }
}

/** display_unapproved_events
  * Description:
  *	Check for any unnaproved events.  If any are found,
  *	display a link to the unapproved events (where they
  *	can be approved).
  *	If the user is an admin user, also count up any public events.
  *	If the user is a nonuser admin, count up events on the nonuser calendar.
  * Parameters:
  *	$user - current user login
  */
function display_unapproved_events ( $user ) {
  global $public_access, $is_admin, $nonuser_enabled, $login;

  // Don't do this for public access login, admin user must approve public
  // events
  if ( $user == "__public__" )
    return;

  $sql = "SELECT COUNT(webcal_entry_user.cal_id) " .
    "FROM webcal_entry_user, webcal_entry " .
    "WHERE webcal_entry_user.cal_id = webcal_entry.cal_id " .
    "AND webcal_entry_user.cal_status = 'W' " .
    "AND ( webcal_entry.cal_ext_for_id IS NULL " .
    "OR webcal_entry.cal_ext_for_id = 0 ) " .
    "AND ( webcal_entry_user.cal_login = '$user'";
  if ( $public_access == "Y" && $is_admin ) {
    $sql .= " OR webcal_entry_user.cal_login = '__public__'";
  }
  if ( $nonuser_enabled == 'Y' ) {
    $admincals = get_nonuser_cals ( $login );
    for ( $i = 0; $i < count ( $admincals ); $i++ ) {
      $sql .= " OR webcal_entry_user.cal_login = '" .
        $admincals[$i]['cal_login'] . "'";
    }
  }
  $sql .= " )";
  //print "SQL: $sql<br />\n";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] > 0 )
	$str = translate ("You have XXX unapproved events");
	$str = str_replace ( "XXX", $row[0], $str );
        echo "<a class=\"nav\" href=\"list_unapproved.php";
        if ( $user != $login )
          echo "?user=$user\"";
        echo "\">" . $str .  "</a><br />\n";
    }
    dbi_free_result ( $res );
  }
}

/** activate_urls
  * Description:
  *	Look for URLs in the given text, and make them into links.
  * Parameters:
  *	$text - input text
  * Returns:
  *	The text altered to have HTML links for any web links
  *	(http or https)
  */
function activate_urls ( $text ) {
  $str = eregi_replace ( "(http://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $text );
  $str = eregi_replace ( "(https://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $str );
  return $str;
}

/** display_time
  * Description:
  *	Display a time in either 12 or 24 hour format.
  *	The global variable $TZ_OFFSET to adjust the time.
  *	Note that this is somewhat of a kludge for timezone support.  If an
  *	event is set for 11PM server time and the user is 2 hours ahead, it
  *	will show up as 1AM, but the date will not be adjusted to the next day.
  * Parameters:
  *	$time - input time in HHMMSS format
  *	$ignore_offset - if true, then do not use the timezone offset
  *	  (optional)
  * Returns:
  *	The query result resource on queries (which can then be
  *	passed to the dbi_fetch_row function to obtain the results),
  *	or true/false on insert or delete queries.
  */
function display_time ( $time, $ignore_offset=0 ) {
  global $TZ_OFFSET;
  $hour = (int) ( $time / 10000 );
  if ( ! $ignore_offset )
    $hour += $TZ_OFFSET;
  while ( $hour < 0 )
    $hour += 24;
  while ( $hour > 23 )
    $hour -= 24;
  $min = ( $time / 100 ) % 100;
  if ( $GLOBALS["TIME_FORMAT"] == "12" ) {
    $ampm = ( $hour >= 12 ) ? translate("pm") : translate("am");
    $hour %= 12;
    if ( $hour == 0 )
      $hour = 12;
    $ret = sprintf ( "%d:%02d%s", $hour, $min, $ampm );
  } else {
    $ret = sprintf ( "%d:%02d", $hour, $min );
  }
  return $ret;
}

/** month_name
  * Description:
  *	Return the full name of the specified month.
  *	Use the month_short_name function to get the abbreviated
  *	name of the month.
  * Parameters:
  *	$m - month (0-11)
  * Returns:
  *	The full name of the specified month
  */
function month_name ( $m ) {
  switch ( $m ) {
    case 0: return translate("January");
    case 1: return translate("February");
    case 2: return translate("March");
    case 3: return translate("April");
    case 4: return translate("May_"); // needs to be different than "May"
    case 5: return translate("June");
    case 6: return translate("July");
    case 7: return translate("August");
    case 8: return translate("September");
    case 9: return translate("October");
    case 10: return translate("November");
    case 11: return translate("December");
  }
  return "unknown-month($m)";
}

/** month_short_name
  * Description:
  *	Return the abbreviated name of the specified month (such as "Jan").
  *	Use the month_name function to get the full
  *	name of the month.
  * Parameters:
  *	$m - month (0-11)
  * Returns:
  *	The abbreviated name of the specified month (example: "Jan")
  */
function month_short_name ( $m ) {
  switch ( $m ) {
    case 0: return translate("Jan");
    case 1: return translate("Feb");
    case 2: return translate("Mar");
    case 3: return translate("Apr");
    case 4: return translate("May");
    case 5: return translate("Jun");
    case 6: return translate("Jul");
    case 7: return translate("Aug");
    case 8: return translate("Sep");
    case 9: return translate("Oct");
    case 10: return translate("Nov");
    case 11: return translate("Dec");
  }
  return "unknown-month($m)";
}

/** weekday_name
  * Description:
  *	Return the full weekday name.  Use the
  *	weekday_short_name function to get the abbreviated weekday name.
  * Parameters:
  *	$w - weekday (0=Sunday,...,6=Saturday)
  * Returns:
  *	the full weekday name ("Sunday")
  */
function weekday_name ( $w ) {
  switch ( $w ) {
    case 0: return translate("Sunday");
    case 1: return translate("Monday");
    case 2: return translate("Tuesday");
    case 3: return translate("Wednesday");
    case 4: return translate("Thursday");
    case 5: return translate("Friday");
    case 6: return translate("Saturday");
  }
  return "unknown-weekday($w)";
}

/** weekday_short_name
  * Description:
  *	Return the abbreviated weekday name.  Use the
  *	weekday_name function to get the full weekday name.
  * Parameters:
  *	$w - weekday (0=Sunday,...,6=Saturday)
  * Returns:
  *	the abbreviated weekday name ("Sun")
  */
function weekday_short_name ( $w ) {
  switch ( $w ) {
    case 0: return translate("Sun");
    case 1: return translate("Mon");
    case 2: return translate("Tue");
    case 3: return translate("Wed");
    case 4: return translate("Thu");
    case 5: return translate("Fri");
    case 6: return translate("Sat");
  }
  return "unknown-weekday($w)";
}

/** date_to_str
  * Description:
  *	Convert a date in YYYYMMDD format into
  *	"Friday, December 31, 1999", "Friday, 12-31-1999" or whatever format
  *	the user prefers.
  * Parameters:
  *	$indate - date in YYYYMMDD format
  *	$format - format to use for date (default is
  *	  "__month__ __dd__, __yyyy__")
  *	$show_weekday - should the day of week also be included (true/false)
  *	$short_months - should the abbreviated month names be used
  *	  instead of the full month names
  *	$server_time -  ???
  * Returns:
  *	The query result resource on queries (which can then be
  *	passed to the dbi_fetch_row function to obtain the results),
  *	or true/false on insert or delete queries.
  */
function date_to_str ( $indate, $format="", $show_weekday=true, $short_months=false, $server_time="" ) {
  global $DATE_FORMAT, $TZ_OFFSET;

  if ( strlen ( $indate ) == 0 ) {
    $indate = date ( "Ymd" );
  }

  $newdate = $indate;
  if ( $server_time != "" && $server_time >= 0 ) {
    $y = substr ( $indate, 0, 4 );
    $m = substr ( $indate, 4, 2 );
    $d = substr ( $indate, 6, 2 );
    if ( $server_time + $TZ_OFFSET * 10000 > 240000 ) {
       $newdate = date ( "Ymd", mktime ( 3, 0, 0, $m, $d + 1, $y ) );
    } else if ( $server_time + $TZ_OFFSET * 10000 < 0 ) {
       $newdate = date ( "Ymd", mktime ( 3, 0, 0, $m, $d - 1, $y ) );
    }
  }

  // if they have not set a preference yet...
  if ( $DATE_FORMAT == "" )
    $DATE_FORMAT = "__month__ __dd__, __yyyy__";

  if ( empty ( $format ) )
    $format = $DATE_FORMAT;

  $y = (int) ( $newdate / 10000 );
  $m = (int) ( $newdate / 100 ) % 100;
  $d = $newdate % 100;
  $date = mktime ( 3, 0, 0, $m, $d, $y );
  $wday = strftime ( "%w", $date );

  if ( $short_months ) {
    $weekday = weekday_short_name ( $wday );
    $month = month_short_name ( $m - 1 );
  } else {
    $weekday = weekday_name ( $wday );
    $month = month_name ( $m - 1 );
  }
  $yyyy = $y;
  $yy = sprintf ( "%02d", $y %= 100 );

  $ret = $format;
  $ret = str_replace ( "__yyyy__", $yyyy, $ret );
  $ret = str_replace ( "__yy__", $yy, $ret );
  $ret = str_replace ( "__month__", $month, $ret );
  $ret = str_replace ( "__mon__", $month, $ret );
  $ret = str_replace ( "__dd__", $d, $ret );
  $ret = str_replace ( "__mm__", $m, $ret );

  if ( $show_weekday )
    return "$weekday, $ret";
  else
    return $ret;
}



function hextoint ( $val ) {
  if ( empty ( $val ) )
    return 0;
  switch ( strtoupper ( $val ) ) {
    case "0": return 0;
    case "1": return 1;
    case "2": return 2;
    case "3": return 3;
    case "4": return 4;
    case "5": return 5;
    case "6": return 6;
    case "7": return 7;
    case "8": return 8;
    case "9": return 9;
    case "A": return 10;
    case "B": return 11;
    case "C": return 12;
    case "D": return 13;
    case "E": return 14;
    case "F": return 15;
  }
  return 0;
}

/** decode_string
  * Description:
  *	Extract a user's name from a session id.
  *	This prevents users from begin
  *	able to edit their cookies.txt file and set the username in plain
  *	text.
  * Parameters:
  *	$instr - a hex-encoded string. "Hello" would be "678ea786a5".
  * Returns:
  *	The decoded string
  */
function decode_string ( $instr ) {
  global $offsets;
  //echo "<br />\nDECODE<br />\n";
  $orig = "";
  for ( $i = 0; $i < strlen ( $instr ); $i += 2 ) {
    //echo "<br />\n";
    $ch1 = substr ( $instr, $i, 1 );
    $ch2 = substr ( $instr, $i + 1, 1 );
    $val = hextoint ( $ch1 ) * 16 + hextoint ( $ch2 );
    //echo "decoding \"" . $ch1 . $ch2 . "\" = $val<br />\n";
    $j = ( $i / 2 ) % count ( $offsets );
    //echo "Using offsets $j = " . $offsets[$j] . "<br />\n";
    $newval = $val - $offsets[$j] + 256;
    $newval %= 256;
    //echo " neval \"$newval\"<br />\n";
    $dec_ch = chr ( $newval );
    //echo " which is \"$dec_ch\"<br />\n";
    $orig .= $dec_ch;
  }
  //echo "Decode string: '$orig' <br/>\n";
  return $orig;
}

/** encode_string
  * Description:
  *	Take an input string and encode it into a slightly encoded hexval
  *	that we can use as a session cookie.
  * Parameters:
  *	$instr - text to encode
  * Returns:
  *	the encoded text
  */
function encode_string ( $instr ) {
  global $offsets;
  //echo "<br />\nENCODE<br />\n";
  $ret = "";
  for ( $i = 0; $i < strlen ( $instr ); $i++ ) {
    //echo "<br />\n";
    $ch1 = substr ( $instr, $i, 1 );
    $val = ord ( $ch1 );
    //echo "val = $val for \"$ch1\"<br />\n";
    $j = $i % count ( $offsets );
    //echo "Using offsets $j = $offsets[$j]<br />\n";
    $newval = $val + $offsets[$j];
    $newval %= 256;
    //echo "newval = $newval for \"$ch1\"<br />\n";
    $ret .= bin2hex ( chr ( $newval ) );
  }
  return $ret;
}

// an implementatin of array_splice() for PHP3
//   test cases:
//     insert an element
//       array_splice($array,$offset,0,array($item));
//     delete an element
//       array_splice($array,$offset,1);
function my_array_splice(&$input,$offset,$length,$replacement) {
  if ( floor(phpversion()) < 4 ) {
    // if offset is negative, then it starts at the end of array
    if ( $offset < 0 )
      $offset = count($input) + $offset;

    for ($i=0;$i<$offset;$i++) {
      $new_array[] = $input[$i];
    }

    // if we have a replacement, insert it
    for ($i=0;$i<count($replacement);$i++) {
      $new_array[] = $replacement[$i];
    }

    // now tack on the rest of the original array
    for ($i=$offset+$length;$i<count($input);$i++) {
      $new_array[] = $input[$i];
    }

    $input = $new_array;
  } else {
    array_splice($input,$offset,$length,$replacement);
  }
}

/** load_user_categories
  * Description:
  *	Load current user's category info and stuff it into
  *	category global variable.
  * Parameters:
  *	$ex_global - Don't include global categories ('' or '1')
  */
function load_user_categories ($ex_global = '') {
  global $login, $user;
  global $categories, $category_owners;
  global $categories_enabled;

  $cat_owner =  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login;
  $categories = array ();
  $category_owners = array ();
  if ( $categories_enabled == "Y" ) {
    $sql = "SELECT cat_id, cat_name, cat_owner FROM webcal_categories WHERE ";
    $sql .=  ($ex_global == '') ? " (cat_owner = '$cat_owner') OR  (cat_owner IS NULL) ORDER BY cat_owner, cat_name" : " cat_owner = '$cat_owner' ORDER BY cat_name";

    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $cat_id = $row[0];
	       $categories[$cat_id] = $row[1];
	       $category_owners[$cat_id] = $row[2];
      }
      dbi_free_result ( $res );
    }
  } else {
    //echo "Categories disabled.";
  }
}

/** print_category_menu
  * Description:
  *	Print dropdown HTML for categories.
  * Parameters:
  *	$form - the page to submit data to (without .php)
  *	$date - date in YYYYMMDD format
  *	$cat_id - category id that should be pre-selected
  */
function print_category_menu ( $form, $date = '', $cat_id = '' ) {
  global $categories, $category_owners, $user, $login;
  echo "<form action=\"{$form}.php\" method=\"get\" name=\"SelectCategory\" class=\"categories\">\n";
  if ( ! empty($date) ) echo "<input type=\"hidden\" name=\"date\" value=\"$date\" />\n";
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  echo translate ('Category') . ": <select name=\"cat_id\" onchange=\"document.SelectCategory.submit()\">\n";
  echo "<option value=\"\"";
  if ( $cat_id == '' ) echo " selected=\"selected\"";
  echo ">" . translate("All") . "</option>\n";
  $cat_owner =  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login;
  if (  is_array ( $categories ) ) {
    foreach ( $categories as $K => $V ){
      if ( $cat_owner ||
        empty ( $category_owners[$K] ) ) {
        echo "<option value=\"$K\"";
        if ( $cat_id == $K ) echo " selected=\"selected\"";
        echo ">$V</option>\n";
      }
    }
  }
  echo "</select>\n";
  echo "</form>\n";
  echo "<span id=\"cat\">" . translate ('Category') . ": ";
  echo ( strlen ( $cat_id ) ? $categories[$cat_id] : translate ('All') ) . "</span>\n";
}

/** html_to_8bits
  * Description:
  *	Convert HTML entities in 8bit.
  *	<br/>Note: Only supported for PHP4 (not PHP3).
  * Parameters:
  *	$html - HTML text
  * Returns:
  *	The converted text
  */
function html_to_8bits ( $html ) {
  if ( floor(phpversion()) < 4 ) {
    return $html;
  } else {
    return strtr ( $html, array_flip (
      get_html_translation_table (HTML_ENTITIES) ) );
  }
}

// ***********************************************************************
// Functions for getting information about boss and their assistant.
// ***********************************************************************

/** user_get_boss_list
  * Description:
  *	Get a list of an assistant's boss from the webcal_asst table.
  * Parameters:
  *	$assistant - login of assistant
  * Returns:
  *	An array of bosses, where each boss is an array with the following
  *	fields: <ul>
  *	  <li> cal_login </li>
  *	  <li> cal_fullname </li>
  *	</ul>
  */
function user_get_boss_list ( $assistant ) {
  global $bosstemp_fullname;

  $res = dbi_query (
    "SELECT cal_boss " .
    "FROM webcal_asst " .
    "WHERE cal_assistant = '$assistant'" );
  $count = 0;
  $ret = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      user_load_variables ( $row[0], "bosstemp_" );
      $ret[$count++] = array (
        "cal_login" => $row[0],
        "cal_fullname" => $bosstemp_fullname
      );
    }
    dbi_free_result ( $res );
  }
  return $ret;
}

/** user_is_assistant
  * Description:
  *	Return true if $user is $boss assistant by
  *	checking in the webcal_asst table.
  * Parameters:
  *	$assistant - login of potential assistant
  *	$boss - login of potential boss
  * Returns:
  *	true or false
  */
function user_is_assistant ( $assistant, $boss ) {
  $ret = false;

  if ( empty ( $boss ) )
    return false;
  $res = dbi_query ( "SELECT * FROM webcal_asst " . 
     "WHERE cal_assistant = '$assistant' AND cal_boss = '$boss'" );
  if ( $res ) {
    if ( dbi_fetch_row ( $res ) )
      $ret = true;
    dbi_free_result ( $res );
  }
  return $ret;
}

/** user_has_boss
  * Description:
  *	Check the webcal_asst table to see if the specified user login
  *	has any bosses associated with it.
  * Parameters:
  *	$assistant - login for user
  * Returns:
  *	true if the user is an assistant to one or more bosses
  */
function user_has_boss ( $assistant ) {
  $ret = false;
  $res = dbi_query ( "SELECT * FROM webcal_asst " .
    "WHERE cal_assistant = '$assistant'" );
  if ( $res ) {
    if ( dbi_fetch_row ( $res ) )
      $ret = true;
    dbi_free_result ( $res );
  }
  return $ret;
}

/** boss_must_be_notified
  * Description:
  *	Check the boss user preferences to see if the boss wants to be
  *	notified via email on changes to their calendar.
  * Parameters:
  *	$assistant - assistant login
  *	$boss - boss login
  * Returns:
  *	true if the boss wants email notifications
  */
function boss_must_be_notified ( $assistant, $boss ) {
  if (user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, "EMAIL_ASSISTANT_EVENTS" )=="Y" ? true : false );
  return true;
}

/** boss_must_approve_event
  * Description:
  *	Check the boss user preferences to see if the boss must approve
  *	events added to their calendar.
  * Parameters:
  *	$assistant - assistant login
  *	$boss - boss login
  * Returns:
  *	true if the boss must approve new events
  */
function boss_must_approve_event ( $assistant, $boss ) {
  if (user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, "APPROVE_ASSISTANT_EVENT" )=="Y" ? true : false );
  return true;
}

// Use this for testing....
function fake_mail ( $mailto, $subj, $text, $hdrs ) { 
  echo "To: $mailto <br />\n" .
    "Subject: $subj <br />\n" .
    nl2br ( $hdrs ) . "<br />\n" .
    nl2br ( $text );
}

/** print_date_entries_timebar
  * Description:
  *	Print all the entries in a time bar format for the specified user
  *	for the
  *	specified date.  If we are displaying data from someone other than
  *	the logged in user, then check the access permission of the entry.
  * Parameters:
  *	$date - date in YYYYMMDD format
  *	$user - username
  *	$ssi - should we not include links to add new events?
  */
function print_date_entries_timebar ( $date, $user, $ssi ) {
  global $events, $readonly, $is_admin,
    $public_access, $public_access_can_add;
  $cnt = 0;
  $get_unapproved = ( $GLOBALS["DISPLAY_UNAPPROVED"] == "Y" );
  // public access events always must be approved before being displayed
  if ( $GLOBALS["login"] == "__public__" )
    $get_unapproved = false;

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );
 
  $dateu = mktime ( 3, 0, 0, $month, $day, $year );

  $can_add = ( $readonly == "N" || $is_admin );
  if ( $public_access == "Y" && $public_access_can_add != "Y" &&
    $GLOBALS["login"] == "__public__" )
    $can_add = false;

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date ) ;
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $user, $date, $get_unapproved );

  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
        print_entry_timebar ( $rep[$cur_rep]['cal_id'],
          $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
          $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'],
          $rep[$cur_rep]['cal_category'] );
        $cnt++;
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]['cal_status'] == 'A' ) {
      print_entry_timebar ( $ev[$i]['cal_id'],
        $date, $ev[$i]['cal_time'], $ev[$i]['cal_duration'],
        $ev[$i]['cal_name'], $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_login'],
        $ev[$i]['cal_category'] );
      $cnt++;
    }
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
      print_entry_timebar ( $rep[$cur_rep]['cal_id'],
        $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
        $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
        $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'],
        $rep[$cur_rep]['cal_category'] );
      $cnt++;
    }
    $cur_rep++;
  }
  if ( $cnt == 0 )
    echo "&nbsp;"; // so the table cell has at least something
}

/** print_entry_timebar
  * Description:
  *	Print the HTML for an events with a timebar.
  * Parameters:
  *	$id - event id
  *	$date - date of event in YYYYMMDD format
  *	$time - time of event in HHMM format
  *	$duration - duration of event in minutes
  *	$name - brief description of event
  *	$description - full description of event
  *	$status - status of event ('A', 'W')
  *	$pri - priority of event
  *	$access - access to event by others ('P', 'R')
  *	$event_owner - user who created event
  *	$event_category - category id for event
  */
function print_entry_timebar ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner, $event_category=-1 ) {
  global $eventinfo, $login, $user, $PHP_SELF, $prefarray;
  static $key = 0;
  
  global $layers;

  // compute time offsets in % of total table width
  $day_start=$prefarray["WORK_DAY_START_HOUR"] * 60;
  if ( $day_start == 0 ) $day_start = 9*60;
  $day_end=$prefarray["WORK_DAY_END_HOUR"] * 60;
  if ( $day_end == 0 ) $day_end = 19*60;
  if ( $day_end <= $day_start ) $day_end = $day_start + 60; //avoid exceptions

  if ($time >= 0) {
	$bar_units= 100/(($day_end - $day_start)/60) ; // Percentage each hour occupies
	$ev_start = round((floor(($time/10000) - ($day_start/60)) + (($time/100)%100)/60) * $bar_units);
  }else{
    $ev_start= 0;
  }
  if ($ev_start < 0) $ev_start = 0;
  if ($duration > 0) {
    $ev_duration = round(100 * $duration / ($day_end - $day_start)) ;
    if ($ev_start + $ev_duration > 100 ) {
      $ev_duration = 100 - $ev_start;
    }
  } else {
    if ($time >= 0) {
      $ev_duration = 1;
    } else {
      $ev_duration=100-$ev_start;
    }
  }
  $ev_padding = 100 - $ev_start - $ev_duration;
  // choose where to position the text (pos=0->before,pos=1->on,pos=2->after)
  if ($ev_duration > 20) 	{ $pos = 1; }
   elseif ($ev_padding > 20) 	{ $pos = 2; }
   else				{ $pos = 0; }
 
  echo "\n<!-- ENTRY BAR -->\n<table class=\"entrycont\" cellpadding=\"0\" cellspacing=\"0\">\n";
   echo "<tr>\n";
   echo ($ev_start > 0 ?  "<td style=\"text-align:right;  width:$ev_start%;\">" : "" );
   if ( $pos > 0 ) {
     echo ($ev_start > 0 ?  "&nbsp;</td>\n": "" ) ;
    echo "<td style=\"width:$ev_duration%;\">\n<table class=\"entrybar\">\n<tr>\n<td class=\"entry\">";
     if ( $pos > 1 ) {
       echo ($ev_padding > 0 ?  "&nbsp;</td>\n": "" ) . "</tr>\n</table></td>\n";
       echo ($ev_padding > 0 ?  "<td style=\"text-align:left; width:$ev_padding%;\">" : "");
    }
  };

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" ) $class = "unapprovedentry";
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" ) ||
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  if ( $pri == 3 ) echo "<strong>";
  $popupid = "eventinfo-$id-$key";
  $key++;
  echo "<a class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $user ) > 0 )
    echo "&amp;user=" . $user;
  echo "\" onmouseover=\"window.status='" . 
  	translate("View this entry") . "'; show(event, '$popupid'); return true;\" onmouseout=\"hide('$popupid'); return true;\">";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event_owner) {
            echo("<span style=\"color:" . $layer['cal_color'] . ";\">");
        }
    }
  }

  echo "[$event_owner]&nbsp;";
  $timestr = "";
  if ( $duration == ( 24 * 60 ) ) {
    $timestr = translate("All day event");
  } else if ( $time >= 0 ) {
    $timestr = display_time ( $time );
    if ( $duration > 0 ) {
      // calc end time
      $h = (int) ( $time / 10000 );
      $m = ( $time / 100 ) % 100;
      $m += $duration;
      $d = $duration;
      while ( $m >= 60 ) {
        $h++;
        $m -= 60;
      }
      $end_time = sprintf ( "%02d%02d00", $h, $m );
      $timestr .= " - " . display_time ( $end_time );
    }
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) )
    echo "(" . translate("Private") . ")";
  else
  if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
    echo "(" . translate("Private") . ")";
  else
  if ( $login != $event_owner && strlen ( $event_owner ) )
  {
    echo htmlspecialchars ( $name );
    echo ("</span>"); //end color span
  }
  else
    echo htmlspecialchars ( $name );
  echo "</a>";
  if ( $pri == 3 ) echo "</strong>"; //end font-weight span
  echo "</td>\n";
  if ( $pos < 2 ) {
    if ( $pos < 1 ) {
      echo "<td style=\"width:n%;\"><table  class=\"entrybar\">\n<tr>\n<td class=\"entry\">&nbsp;</td>\n";
    }
    echo "</tr>\n</table></td>\n";
    echo ($ev_padding > 0 ? "<td style=\"text-align:left; width:$ev_padding%;\">&nbsp;</td>\n" : "" );
  }
  echo "</tr>\n</table>\n";
	if ( $login != $user && $access == 'R' && strlen ( $user ) )
		$eventinfo .= build_event_popup ( $popupid, $event_owner,
			translate("This event is confidential"), "" );
	else
	if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
		$eventinfo .= build_event_popup ( $popupid, $event_owner,
			translate("This event is confidential"), "" );
	else
		$eventinfo .= build_event_popup ( $popupid, $event_owner,
			$description, $timestr, site_extras_for_popup ( $id ) );
}

/** print_header_timebar
  * Description:
  *	Print the header for the timebar.
  * Parameters:
  *	$start_hour - start hour
  *	$end_hour - end hour
  */
function print_header_timebar($start_hour, $end_hour) {
  //      sh+1   ...   eh-1
  // +------+----....----+------+
  // |      |            |      |

  // print hours
  if ( ($end_hour - $start_hour) == 0 )
    $offset = 0;
  else
    $offset = round(100/($end_hour - $start_hour));
    echo "\n<!-- TIMEBAR -->\n<table class=\"timebar\">\n<tr><td style=\"width:$offset%;\">&nbsp;</td>\n";
   for ($i = $start_hour+1; $i < $end_hour; $i++) {
//     $prev_offset = $offset;
//     $offset = round(100/($end_hour - $start_hour)*($i - $start_hour + .5));
     $offset = round(100/($end_hour - $start_hour));
     $width = $offset;
    echo "<td style=\"width:$width%;text-align:left;\">$i</td>\n";
   }
//   $width = 100 - $offset;
//   echo "<td style=\"width:$width%;\">&nbsp;</td>\n";
   echo "</tr>\n</table>\n<!-- /TIMEBAR -->\n";
 
   // print yardstick
  echo "\n<!-- YARDSTICK -->\n<table class=\"yardstick\">\n<tr>\n";
	$width = round(100/($end_hour - $start_hour));
  for ($i = $start_hour; $i < $end_hour; $i++) {
    echo "<td style=\"width:$width%;\">&nbsp;</td>\n";
   }
   echo "</tr>\n</table>\n<!-- /YARDSTICK -->\n";
 }

/** get_nonuser_cals
  * Description:
  *	Get a list of nonuser calendars and return info in an array.
  * Parameters:
  *	$user - (optional) login of admin of the nonuser calendars
  * Returns:
  *	An array of nonuser cals, where each is an array with
  *	the following fields: <ul>
  *	  <li> cal_login </li>
  *	  <li> cal_lastname </li>
  *	  <li> cal_firstname </li>
  *	  <li> cal_admin </li>
  *	  <li> cal_fullname </li>
  *	</ul>
  */
function get_nonuser_cals ($user = '') {
  $count = 0;
  $ret = array ();
  $sql = "SELECT cal_login, cal_lastname, cal_firstname, " .
    "cal_admin FROM webcal_nonuser_cals ";
  if ($user != '') $sql .= "WHERE cal_admin = '$user' ";
  $sql .= "ORDER BY cal_lastname, cal_firstname, cal_login";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( strlen ( $row[1] ) || strlen ( $row[2] ) )
        $fullname = "$row[2] $row[1]";
      else
        $fullname = $row[0];
      $ret[$count++] = array (
        "cal_login" => $row[0],
        "cal_lastname" => $row[1],
        "cal_firstname" => $row[2],
        "cal_admin" => $row[3],
        "cal_fullname" => $fullname
      );
    }
    dbi_free_result ( $res );
  }
  return $ret;
}

/** nonuser_load_variables
  * Description:
  *	Loads nonuser variables (login, firstname, etc.)
  *	The following variables will be set:  <ul>
  *	  <li> login </li>
  *	  <li> firstname </li>
  *	  <li> lastname </li>
  *	  <li> fullname </li>
  *	  <li> admin </li>
  *	  <li> email </li>
  *	</ul>
  * Parameters:
  *	$login - login name of nonuser calendar
  *	$prefix - prefix to use for variables that will be set.
  *	  For example, if prefix is "temp", then the login will
  *	  be stored in the $templogin global variable.
  *	
  */
function nonuser_load_variables ( $login, $prefix ) {
  global $error,$nuloadtmp_email;
  $ret =  false;
  $res = dbi_query ( "SELECT cal_login, cal_lastname, cal_firstname, " .
    "cal_admin FROM webcal_nonuser_cals WHERE cal_login = '$login'" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( strlen ( $row[1] ) || strlen ( $row[2] ) )
        $fullname = "$row[2] $row[1]";
      else
        $fullname = $row[0];

        // We need the email address for the admin
        user_load_variables ( $row[3], 'nuloadtmp_' );

        $GLOBALS[$prefix . "login"] = $row[0];
        $GLOBALS[$prefix . "firstname"] = $row[2];
        $GLOBALS[$prefix . "lastname"] = $row[1];
        $GLOBALS[$prefix . "fullname"] = $fullname;
        $GLOBALS[$prefix . "admin"] = $row[3];
        $GLOBALS[$prefix . "email"] = $nuloadtmp_email;
        $ret = true;
    }
    dbi_free_result ( $res );
  }
  return $ret;
}

/** user_is_nonuser_admin
  * Description:
  *	Check the webcal_nonuser_cals table to determine
  *	if the user is the administrator for the nonuser calendar.
  * Parameters:
  *	$login - login of user that is the potential administrator
  *	$nonuser - login name for nonuser calendar
  * Returns:
  *	true if the user is the administrator for the nonuser calendar
  */
function user_is_nonuser_admin ( $login, $nonuser ) {
  $ret = false;

  $res = dbi_query ( "SELECT * FROM webcal_nonuser_cals " .
    "WHERE cal_login = '$nonuser' AND cal_admin = '$login'" );
  if ( $res ) {
    if ( dbi_fetch_row ( $res ) )
      $ret = true;
    dbi_free_result ( $res );
  }
  return $ret;
}

/** load_nonuser_preferences
  * Description:
  *	Loads nonuser preferences from the
  *	webcal_user_pref table if on a nonuser admin page.
  * Parameters:
  *	$nonuser - login name for nonuser calendar
  */
function load_nonuser_preferences ($nonuser) {
  global $prefarray;
  $res = dbi_query (
    "SELECT cal_setting, cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '$nonuser'" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $setting = $row[0];
      $value = $row[1];
      $sys_setting = "sys_" . $setting;
      // save system defaults
      // ** don't override ones set by load_user_prefs
      if ( ! empty ( $GLOBALS[$setting] ) && empty ( $GLOBALS["sys_" . $setting] ))
        $GLOBALS["sys_" . $setting] = $GLOBALS[$setting];
      $GLOBALS[$setting] = $value;
      $prefarray[$setting] = $value;
    }
    dbi_free_result ( $res );
  }
}

/** set_today
  * Description:
  *	Determines what the day is after the $TZ_OFFSET and sets it globally.
  *	The following global variables will be set:  <ul>
  *	  <li> $thisyear </li>
  *	  <li> $thismonth </li>
  *	  <li> $thisday </li>
  *	  <li> $thisdate </li>
  *	  <li> $today </li>
  *	</ul>
  * Parameters:
  *	$date - the date in YYYYMMDD format
  */
function set_today($date) {
  global $thisyear, $thisday, $thismonth, $today;
  global $TZ_OFFSET, $month, $day, $year, $thisday;

  // Adjust for TimeZone
  $today = time() + ($TZ_OFFSET * 60 * 60);

  if ( ! empty ( $date ) && ! empty ( $date ) ) {
    $thisyear = substr ( $date, 0, 4 );
    $thismonth = substr ( $date, 4, 2 );
    $thisday = substr ( $date, 6, 2 );
  } else {
    if ( empty ( $month ) || $month == 0 )
      $thismonth = date("m", $today);
    else
      $thismonth = $month;
    if ( empty ( $year ) || $year == 0 )
      $thisyear = date("Y", $today);
    else
      $thisyear = $year;
    if ( empty ( $day ) || $day == 0 )
      $thisday = date("d", $today);
    else
      $thisday = $day;
  }
  $thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );
}

// JGH borrowed gregorianToISO from PEAR Date_Calc Class
//   and added $GLOBALS["WEEK_START"] (change noted)
//
// Converts from Gregorian Year-Month-Day to ISO YearNumber-WeekNumber-WeekDay
function gregorianToISO($day,$month,$year) {
    $mnth = array (0,31,59,90,120,151,181,212,243,273,304,334);
    $y_isleap = isLeapYear($year);
    $y_1_isleap = isLeapYear($year - 1);
    $day_of_year_number = $day + $mnth[$month - 1];
    if ($y_isleap && $month > 2) {
        $day_of_year_number++;
    }
    // find Jan 1 weekday (monday = 1, sunday = 7)
    $yy = ($year - 1) % 100;
    $c = ($year - 1) - $yy;
    $g = $yy + intval($yy/4);
    $jan1_weekday = 1 + intval((((($c / 100) % 4) * 5) + $g) % 7);


    // JGH added next if/else to compensate for week begins on Sunday
    if (! $GLOBALS["WEEK_START"] && $jan1_weekday < 7) {
      $jan1_weekday++;
    } elseif (! $GLOBALS["WEEK_START"] && $jan1_weekday == 7) {
      $jan1_weekday=1;
    }

    // weekday for year-month-day
    $h = $day_of_year_number + ($jan1_weekday - 1);
    $weekday = 1 + intval(($h - 1) % 7);
    // find if Y M D falls in YearNumber Y-1, WeekNumber 52 or
    if ($day_of_year_number <= (8 - $jan1_weekday) && $jan1_weekday > 4){
        $yearnumber = $year - 1;
        if ($jan1_weekday == 5 || ($jan1_weekday == 6 && $y_1_isleap)) {
            $weeknumber = 53;
        } else {
            $weeknumber = 52;
        }
    } else {
        $yearnumber = $year;
    }
    // find if Y M D falls in YearNumber Y+1, WeekNumber 1
    if ($yearnumber == $year) {
        if ($y_isleap) {
            $i = 366;
        } else {
            $i = 365;
        }
        if (($i - $day_of_year_number) < (4 - $weekday)) {
            $yearnumber++;
            $weeknumber = 1;
        }
    }
    // find if Y M D falls in YearNumber Y, WeekNumber 1 through 53
    if ($yearnumber == $year) {
        $j = $day_of_year_number + (7 - $weekday) + ($jan1_weekday - 1);
        $weeknumber = intval($j / 7);
        if ($jan1_weekday > 4) {
            $weeknumber--;
        }
    }
    // put it all together
    if ($weeknumber < 10)
        $weeknumber = '0'.$weeknumber;
    return "{$yearnumber}-{$weeknumber}-{$weekday}";
}

// JGH Borrowed isLeapYear from PEAR Date_Calc Class
//
// Returns true for a leap year, else false
function isLeapYear($year='') {
  if (empty($year)) $year = strftime("%Y",time());
  if (strlen($year) != 4) return false;
  if (preg_match('/\D/',$year)) return false;
  return (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0);
}

/** clean_html
  * Description:
  *	Replace unsafe characters with HTML encoded equivalents
  * Parameters:
  *	$value - input text
  * Returns:
  *	the cleaned text
  */
function clean_html($value){
  $value = htmlspecialchars($value, ENT_QUOTES);
  $value = strtr($value, array(
    '('   => '&#40;',
    ')'   => '&#41;'
  ));
  return $value;
}

/** clean_word
  * Description:
  *	Remove non-word characters from the specified text
  * Parameters:
  *	$data - input text
  * Returns:
  *	the converted text
  */
function clean_word($data) { 
  return preg_replace("/\W/", '', $data);
}

/** clean_int
  * Description:
  *	Remove non-digits from the specified text
  * Parameters:
  *	$data - input text
  * Returns:
  *	the converted text
  */
function clean_int($data) { 
  return preg_replace("/\D/", '', $data);
}

/** clean_whitespace
  * Description:
  *	Remove whitespace from the specified text
  * Parameters:
  *	$data - input text
  * Returns:
  *	the converted text
  */
function clean_whitespace($data) { 
  return preg_replace("/\s/", '', $data);
}

/** languageToAbbrev
  * Description:
  *	Converts language names to their abbreviation
  * Parameters:
  *	$name - Name of the language (such as "French")
  * Returns:
  *	The abbreviation ("fr" for "French")
  */
function languageToAbbrev ( $name ) {
  global $browser_languages;
  foreach ( $browser_languages as $abbrev => $langname ) {
    if ( $langname == $name )
      return $abbrev;
  }
  return false;
}

/** background_css
  * Description:
  *	Creates the CSS for using gradient.php, if the appropriate GD
  *	functions are available.  A one-pixel wide image will be used
  *	for the background image.
  *	<br/>Note:
  *	The gd library module needs to be available to use gradient
  *	images.  If it is not available, a single background color
  *	will be used instead.
  * Parameters:
  *	$color - base color
  *	$height - height of gradient image
  *	$percent - how many percent lighter the top color should be
  *		than the base color at the bottom of the image
  * Returns:
  *	The style sheet text to use
  */
function background_css ( $color, $height = '', $percent = '' ) {
  $ret = '';

  if ( ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' ) )
    && ( empty ( $GLOBALS['enable_gradients'] ) ||
    $GLOBALS['enable_gradients'] == 'Y' ) ) {
    $ret = "background: $color url(\"gradient.php?base=" . substr ( $color, 1 );

    if ( $height != '' ) {
      $ret .= "&height=$height";
    }

    if ( $percent != '' ) {
      $ret .= "&percent=$percent";
    }

    $ret .= "\") repeat-x;\n";
  } else {
    $ret = "background-color: $color;\n";
  }

  return $ret;
}

?>
