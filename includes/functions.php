<?php
/**
 * All of WebCalendar's functions
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 */

if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}

/**#@+
 * Used for activity log
 * @global string
 */
$LOG_CREATE = "C";
$LOG_APPROVE = "A";
$LOG_REJECT = "X";
$LOG_UPDATE = "U";
$LOG_DELETE = "D";
$LOG_NOTIFICATION = "N";
$LOG_REMINDER = "R";
/**#@-*/

/**
 * Number of seconds in a day
 *
 * @global int $ONE_DAY
 */
$ONE_DAY = 86400;

/**
 * Array containing the number of days in each month in a non-leap year
 *
 * @global array $days_per_month
 */
$days_per_month = array ( 0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

/**
 * Array containing the number of days in each month in a leap year
 *
 * @global array $ldays_per_month
 */
$ldays_per_month = array ( 0, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

/**
 * Array of global variables which are not allowed to by set via HTTP GET/POST
 *
 * This is a security precaution to prevent users from overriding any global
 * variables
 *
 * @global array $noSet
 */
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
  "includedir" => 1,
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
if ( empty ( $HTTP_GET_VARS ) ) $HTTP_GET_VARS = $_GET;
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

if ( empty ( $HTTP_POST_VARS ) ) $HTTP_POST_VARS = $_POST;
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
if ( empty ( $HTTP_COOKIE_VARS ) ) $HTTP_COOKIE_VARS = $_COOKIE;
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
if ( empty ( $PHP_SELF ) )
  $PHP_SELF = ''; // this happens when running send_reminders.php from CL
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
 *  Documentation is generated from the function comments below.
 *  When adding/updating functions, please follow the following conventions
 *  seen below.  Your cooperation in this matter is appreciated :-)
 *
 *  If you want your documentation to link to the db documentation,
 *  just make sure you mention the db table name followed by "table"
 *  on the same line.  Here's an example:
 *    Retrieve preferences from the webcal_user_pref table.
 *
 */

/**
 * Gets the value resulting from an HTTP POST method.
 * 
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 * 
 * @param string $name Name used in the HTML form
 *
 * @return string The value used in the HTML form
 *
 * @see getGetValue
 */
function getPostValue ( $name ) {
  global $HTTP_POST_VARS;

  if ( isset ( $_POST ) && is_array ( $_POST ) && ! empty ( $_POST[$name] ) ) {
	  $HTTP_POST_VARS[$name] = $_POST[$name];
    return $_POST[$name];
   } else if ( ! isset ( $HTTP_POST_VARS ) ) {
    return null;
  } else if ( ! isset ( $HTTP_POST_VARS[$name] ) ) {
    return null;
	}
  return ( $HTTP_POST_VARS[$name] );
}

/**
 * Gets the value resulting from an HTTP GET method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * If you need to enforce a specific input format (such as numeric input), then
 * use the {@link getValue()} function.
 *
 * @param string $name Name used in the HTML form or found in the URL
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @see getPostValue
 */
function getGetValue ( $name ) {
  global $HTTP_GET_VARS;

  if ( isset ( $_GET ) && is_array ( $_GET ) && ! empty ( $_GET[$name] ) ) {
	  $HTTP_GET_VARS[$name] = $_GET[$name];
    return $_GET[$name];
  } else if ( ! isset ( $HTTP_GET_VARS ) )  {
    return null;
   } else if ( ! isset ( $HTTP_GET_VARS[$name] ) ) {
    return null;
	}
  return ( $HTTP_GET_VARS[$name] );
}

/**
 * Gets the value resulting from either HTTP GET method or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * <b>Note:</b> If you need to get an integer value, yuou can use the
 * getIntValue function.
 *
 * @param string $name   Name used in the HTML form or found in the URL
 * @param string $format A regular expression format that the input must match.
 *                       If the input does not match, an empty string is
 *                       returned and a warning is sent to the browser.  If The
 *                       <var>$fatal</var> parameter is true, then execution
 *                       will also stop when the input does not match the
 *                       format.
 * @param bool   $fatal  Is it considered a fatal error requiring execution to
 *                       stop if the value retrieved does not match the format
 *                       regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getGetValue
 * @uses getPostValue
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

/**
 * Gets an integer value resulting from an HTTP GET or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * @param string $name  Name used in the HTML form or found in the URL
 * @param bool   $fatal Is it considered a fatal error requiring execution to
 *                      stop if the value retrieved does not match the format
 *                      regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getValue
 */
function getIntValue ( $name, $fatal=false ) {
  $val = getValue ( $name, "-?[0-9]+", $fatal );
  return $val;
}

/**
 * Loads default system settings (which can be updated via admin.php).
 *
 * System settings are stored in the webcal_config table.
 *
 * <b>Note:</b> If the setting for <var>server_url</var> is not set, the value
 * will be calculated and stored in the database.
 *
 * @global string User's login name
 * @global bool   Readonly
 * @global string HTTP hostname
 * @global int    Server's port number
 * @global string Request string
 * @global array  Server variables
 */
function load_global_settings () {
  global $login, $readonly, $HTTP_HOST, $SERVER_PORT, $REQUEST_URI, $_SERVER;

  // Note: when running from the command line (send_reminders.php),
  // these variables are (obviously) not set.
  // TODO: This type of checking should be moved to a central locationm
  // like init.php.
  if ( isset ( $_SERVER ) && is_array ( $_SERVER ) ) {
    if ( empty ( $HTTP_HOST ) && isset ( $_SERVER["HTTP_POST"] ) )
      $HTTP_HOST = $_SERVER["HTTP_HOST"];
    if ( empty ( $SERVER_PORT ) && isset ( $_SERVER["SERVER_PORT"] ) )
      $SERVER_PORT = $_SERVER["SERVER_PORT"];
    if ( empty ( $REQUEST_URI ) && isset ( $_SERVER["REQUEST_URI"] ) )
      $REQUEST_URI = $_SERVER["REQUEST_URI"];
  }

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

/**
 * Gets the list of active plugins.
 *
 * Should be called after {@link load_global_settings()} and {@link load_user_preferences()}.
 *
 * @internal cek: ignored since I am not sure this will ever be used...
 *
 * @return array Active plugins
 *
 * @ignore
 */
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

/**
 * Get plugins available to the current user.
 *
 * Do this by getting a list of all plugins that are not disabled by the
 * administrator and make sure this user has not disabled any of them.
 * 
 * It's done this was so that when an admin adds a new plugin, it shows up on
 * each users system automatically (until they disable it).
 *
 * @return array Plugins available to current user
 *
 * @ignore
 */
function get_user_plugin_list () {
  $ret = array ();
  $all_plugins = get_plugin_list ();
  for ( $i = 0; $i < count ( $all_plugins ); $i++ ) {
    if ( $GLOBALS[$all_plugins[$i] . ".disabled"] != "N" )
      $ret[] = $all_plugins[$i];
  }
  return $ret;
}

/**
 * Identify user's browser.
 *
 * Returned value will be one of:
 * - "Mozilla/5" = Mozilla (open source Mozilla 5.0)
 * - "Mozilla/[3,4]" = Netscape (3.X, 4.X)
 * - "MSIE 4" = MSIE (4.X)
 *
 * @return string String identifying browser
 *
 * @ignore
 */
function get_web_browser () {
  if ( ereg ( "MSIE [0-9]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "MSIE";
  if ( ereg ( "Mozilla/[234]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Netscape";
  if ( ereg ( "Mozilla/[5678]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Mozilla";
  return "Unknown";
}


/**
 * Logs a debug message.
 *
 * Generally, we do not leave calls to this function in the code.  It is used
 * for debugging only.
 *
 * @param string $msg Text to be logged
 */
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  3, "/tmp/webcal-debug.log" );
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  2, "sockieman:2000" );
}

/**
 * Gets user's preferred view.
 *
 * The user's preferred view is stored in the $STARTVIEW global variable.  This
 * is loaded from the user preferences (or system settings if there are no user
 * prefererences.)
 *
 * @param string $indate Date to pass to preferred view in YYYYMMDD format
 * @param string $args   Arguments to include in the URL (such as "user=joe")
 *
 * @return string URL of the user's preferred view
 */
function get_preferred_view ( $indate="", $args="" ) {
  global $STARTVIEW, $thisdate;

  $url = empty ( $STARTVIEW ) ? "month.php" : $STARTVIEW;
  // We used to just store "month" in $STARTVIEW without the ".php"
  // This is just to prevent users from getting a "404 not found" if
  // they have not updated their preferences.
  if ( $url == "month" || $url == "day" || $url == "week" || $url == "year" )
    $url .= ".php";

  $url = str_replace ( '&amp;', '&', $url );
  $url = str_replace ( '&', '&amp;', $url );

  $xdate = empty ( $indate ) ? $thisdate : $indate;
  if ( ! empty ( $xdate ) ) {
    if ( strstr ( $url, "?" ) )
      $url .= '&amp;' . "date=$xdate";
    else
      $url .= '?' . "date=$xdate";
  }

  if ( ! empty ( $args ) ) {
    if ( strstr ( $url, "?" ) )
      $url .= '&amp;' . $args;
    else
      $url .= '?' . $args;
  }

  return $url;
}

/**
 * Sends a redirect to the user's preferred view.
 *
 * The user's preferred view is stored in the $STARTVIEW global variable.  This
 * is loaded from the user preferences (or system settings if there are no user
 * prefererences.)
 *
 * @param string $indate Date to pass to preferred view in YYYYMMDD format
 * @param string $args   Arguments to include in the URL (such as "user=joe")
 */
function send_to_preferred_view ( $indate="", $args="" ) {
  $url = get_preferred_view ( $indate, $args );
  do_redirect ( $url );
}

/** Sends a redirect to the specified page.
 *
 * The database connection is closed and execution terminates in this function.
 *
 * <b>Note:</b> MS IIS/PWS has a bug in which it does not allow us to send a
 * cookie and a redirect in the same HTTP header.  When we detect that the web
 * server is IIS, we accomplish the redirect using meta-refresh.  See the
 * following for more info on the IIS bug:
 *
 * {@link http://www.faqts.com/knowledge_base/view.phtml/aid/9316/fid/4}
 *
 * @param string $url The page to redirect to.  In theory, this should be an
 *                    absolute URL, but all browsers accept relative URLs (like
 *                    "month.php").
 *
 * @global string   Type of webserver
 * @global array    Server variables
 * @global resource Database connection
 */
function do_redirect ( $url ) {
  global $SERVER_SOFTWARE, $_SERVER, $c;

  // Replace any '&amp;' with '&' since we don't want that in the HTTP
  // header.
  $url = str_replace ( '&amp;', '&', $url );

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

/**
 * Sends an HTTP login request to the browser and stops execution.
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

/**
 * Generates a cookie that saves the last calendar view.
 *
 * Cookie is based on the current <var>$REQUEST_URI</var>.
 *
 * We save this cookie so we can return to this same page after a user
 * edits/deletes/etc an event.
 *
 * @global string Request string
 */
function remember_this_view () {
  global $REQUEST_URI;
  if ( empty ( $REQUEST_URI ) )
    $REQUEST_URI = $_SERVER["REQUEST_URI"];

  // do not use anything with friendly in the URI
  if ( strstr ( $REQUEST_URI, "friendly=" ) )
    return;

  SetCookie ( "webcalendar_last_view", $REQUEST_URI );
}

/**
 * Gets the last page stored using {@link remember_this_view()}.
 *
 * @return string The URL of the last view or an empty string if it cannot be
 *                determined.
 *
 * @global array Cookies
 */
function get_last_view () {
  global $HTTP_COOKIE_VARS;
  $val = '';

  if ( isset ( $_COOKIE["webcalendar_last_view"] ) ) {
	  $HTTP_COOKIE_VARS["webcalendar_last_view"] = $_COOKIE["webcalendar_last_view"];
    $val = $_COOKIE["webcalendar_last_view"];
  } else if ( isset ( $HTTP_COOKIE_VARS["webcalendar_last_view"] ) ) {
    $val = $HTTP_COOKIE_VARS["webcalendar_last_view"];
	}
  $val =   str_replace ( "&", "&amp;", $val );
  return $val;
}

/**
 * Sends HTTP headers that tell the browser not to cache this page.
 *
 * Different browser use different mechanisms for this, so a series of HTTP
 * header directives are sent.
 *
 * <b>Note:</b> This function needs to be called before any HTML output is sent
 * to the browser.
 */
function send_no_cache_header () {
  header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
  header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s" ) . " GMT" );
  header ( "Cache-Control: no-store, no-cache, must-revalidate" );
  header ( "Cache-Control: post-check=0, pre-check=0", false );
  header ( "Pragma: no-cache" );
}

/**
 * Loads the current user's preferences as global variables from the webcal_user_pref table.
 *
 * Also loads the list of views for this user (not really a preference, but
 * this is a convenient place to put this...)
 *
 * <b>Notes:</b>
 * - If <var>$allow_color_customization</var> is set to 'N', then we ignore any
 *   color preferences.
 * - Other default values will also be set if the user has not saved a
 *   preference and no global value has been set by the administrator in the
 *   system settings.
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
  // get views for this user and global views
  $res = dbi_query (
    "SELECT cal_view_id, cal_name, cal_view_type, cal_is_global " .
    "FROM webcal_view " .
    "WHERE cal_owner = '$login' OR cal_is_global = 'Y' " .
    "ORDER BY cal_name" );
  if ( $res ) {
    $views = array ();
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[2] == 'S' )
        $url = "view_t.php?timeb=1&amp;id=$row[0]";
      else if ( $row[2] == 'T' )
        $url = "view_t.php?timeb=0&amp;id=$row[0]";
      else
        $url = "view_" . strtolower ( $row[2] ) . ".php?id=$row[0]";
      $v = array (
        "cal_view_id" => $row[0],
        "cal_name" => $row[1],
        "cal_view_type" => $row[2],
        "cal_is_global" => $row[3],
        "url" => $url
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

/**
 * Gets the list of external users for an event from the webcal_entry_ext_user table in an HTML format.
 *
 * @param int $event_id   Event ID
 * @param int $use_mailto When set to 1, email address will contain an href
 *                        link with a mailto URL.
 *
 * @return string The list of external users for an event formatte in HTML.
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
      // Remove [\d] if duplicate name
      $trow = trim( preg_replace( '/\[[\d]]/' , "", $row[0] ) );
      $ret .= $trow;
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

/**
 * Adds something to the activity log for an event.
 *
 * The information will be saved to the webcal_entry_log table.
 *
 * @param int    $event_id Event ID
 * @param string $user     Username of user doing this
 * @param string $user_cal Username of user whose calendar is affected
 * @param string $type     Type of activity we are logging:
 *   - $LOG_CREATE
 *   - $LOG_APPROVE
 *   - $LOG_REJECT
 *   - $LOG_UPDATE
 *   - $LOG_DELETE
 *   - $LOG_NOTIFICATION
 *   - $LOG_REMINDER
 * @param string $text     Text comment to add with activity log entry
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

/**
 * Gets a list of users.
 *
 * If groups are enabled, this will restrict the list of users to only those
 * users who are in the same group(s) as the user (unless the user is an admin
 * user).  We allow admin users to see all users because they can also edit
 * someone else's events (so they may need access to users who are not in the
 * same groups that they are in).
 *
 * @return array Array of users, where each element in the array is an array
 *               with the following keys:
 *    - cal_login
 *    - cal_lastname
 *    - cal_firstname
 *    - cal_is_admin
 *    - cal_is_admin
 *    - cal_email
 *    - cal_password
 *    - cal_fullname
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

/**
 * Gets a preference setting for the specified user.
 *
 * If no value is found in the database, then the system default setting will
 * be returned.
 *
 * @param string $user    User login we are getting preference for
 * @param string $setting Name of the setting
 *
 * @return string The value found in the webcal_user_pref table for the
 *                specified setting or the sytem default if no user settings
 *                was found.
 */
function get_pref_setting ( $user, $setting ) {
  $ret = '';
  // set default
  if ( ! isset ( $GLOBALS["sys_" .$setting] ) ) {
    // this could happen if the current user has not saved any pref. yet
    if ( ! empty ( $GLOBALS[$setting] ) )
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

/**
 * Gets browser-specified language preference.
 *
 * @return string Preferred language
 *
 * @ignore
 */
function get_browser_language () {
  global $HTTP_ACCEPT_LANGUAGE, $browser_languages;
  $ret = "";
  if ( empty ( $HTTP_ACCEPT_LANGUAGE ) &&
    isset ( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) )
    $HTTP_ACCEPT_LANGUAGE = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
  if (  empty ( $HTTP_ACCEPT_LANGUAGE ) ) {
    return "none";
  } else {
    $langs = explode ( ",", $HTTP_ACCEPT_LANGUAGE );
    for ( $i = 0; $i < count ( $langs ); $i++ ) {
     $l = strtolower ( trim ( ereg_replace(';.*', '', $langs[$i] ) ) );
      $ret .= "\"$l\" ";
      if ( ! empty ( $browser_languages[$l] ) ) {
        return $browser_languages[$l];
      }
    }
  }
  //if ( strlen ( $HTTP_ACCEPT_LANGUAGE ) )
  //  return "none ($HTTP_ACCEPT_LANGUAGE not supported)";
  //else
    return "none";
}

/**
 * Loads current user's layer info into layer global variable.
 *
 * If the system setting <var>$allow_view_other</var> is not set to 'Y', then
 * we ignore all layer functionality.  If <var>$force</var> is 0, we only load
 * layers if the current user preferences have layers turned on.
 *
 * @param string $user  Username of user to load layers for
 * @param int    $force If set to 1, then load layers for this user even if
 *                      user preferences have layers turned off.
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

/**
 * Generates the HTML used in an event popup for the site_extras fields of an event.
 *
 * @param int $id Event ID
 *
 * @return string The HTML to be used within the event popup for any site_extra
 *                fields found for the specified event
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

/**
 * Builds the HTML for the event popup.
 *
 * @param string $popupid     CSS id to use for event popup
 * @param string $user        Username of user the event pertains to
 * @param string $description Event description
 * @param string $time        Time of the event (already formatted in a display format)
 * @param string $site_extras HTML for any site_extras for this event
 *
 * @return string The HTML for the event popup
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

/**
 * Prints out a date selection box for use in a form.
 *
 * @param string $prefix Prefix to use in front of form element names
 * @param int    $date   Currently selected date (in YYYYMMDD format)
 *
 * @uses date_selection_html
 */
function print_date_selection ( $prefix, $date ) {
  print date_selection_html ( $prefix, $date );
}

/**
 * Generate HTML for a date selection for use in a form.
 *
 * @param string $prefix Prefix to use in front of form element names
 * @param int    $date   Currently selected date (in YYYYMMDD format)
 *
 * @return string HTML for the selection box
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
    $ret .= "<option value=\"$i\"" .
      ( $i == $thisday ? " selected=\"selected\"" : "" ) . ">$i</option>\n";
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
  $ret .= "<input type=\"button\" onclick=\"selectDate( '" .
    $prefix . "day','" . $prefix . "month','" . $prefix . "year',$date, event)\" value=\"" .
    translate("Select") . "...\" />\n";

  return $ret;
}

/**
 * Prints out a minicalendar for a month.
 *
 * @todo Make day.php NOT be a special case
 *
 * @param int    $thismonth     Number of the month to print
 * @param int    $thisyear      Number of the year
 * @param bool   $showyear      Show the year in the calendar's title?
 * @param bool   $show_weeknums Show week numbers to the left of each row?
 * @param string $minical_id    id attribute for the minical table
 * @param string $month_link    URL and query string for month link that should
 *                              come before the date specification (e.g.
 *                              month.php?  or  view_l.php?id=7&amp;)
 */
function display_small_month ( $thismonth, $thisyear, $showyear,
  $show_weeknums=false, $minical_id='', $month_link='month.php?' ) {
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved;
  global $DISPLAY_WEEKNUMBER;
  global $SCRIPT, $thisday; // Needed for day.php
  global $caturl, $today;

  if ( $user != $login && ! empty ( $user ) ) {
    $u_url = "user=$user" . "&amp;";
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
 translate("Previous") . "\" class=\"prev\" href=\"day.php?" . $u_url  .
 "date=$month_ago$caturl\"><img src=\"leftarrowsmall.gif\" alt=\"" .
 translate("Previous") . "\" /></a>\n";
    echo "<a title=\"" . 
 translate("Next") . "\" class=\"next\" href=\"day.php?" . $u_url .
 "date=$month_ahead$caturl\"><img src=\"rightarrowsmall.gif\" alt=\"" .
 translate("Next") . "\" /></a>\n";
    echo month_name ( $thismonth - 1 );
    if ( $showyear != '' ) {
      echo " $thisyear";
    }
    echo "</th></tr>\n<tr>\n";
  } else {  //not day script
    //print the month name
    echo "<caption><a href=\"{$month_link}{$u_url}year=$thisyear&amp;month=$thismonth\">";
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
      echo "<td class=\"weeknumber\"><a href=\"week.php?" . $u_url .
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
  //if the day being viewed is today's date AND script = day.php
        if ( $dateYmd == $thisyear . $thismonth . $thisday &&
          $SCRIPT == 'day.php'  ) {
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
        if ( date ( "Ymd", $date  ) == date ( "Ymd", $today ) ){
          echo " id=\"today\"";
        }
        echo "><a href=\"day.php?" .$u_url  . "date=" .  $dateYmd . 
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

/**
 * Prints the HTML for one day's events in the month view.
 *
 * @param int    $id          Event ID
 * @param int    $date        Date of event (relevant in repeating events) in
 *                            YYYYMMDD format
 * @param int    $time        Time (in HHMMSS format)
 * @param int    $duration    Event duration in minutes
 * @param string $name        Event name
 * @param string $description Long description of event
 * @param string $status      Event status
 * @param int    $pri         Event priority
 * @param string $access      Event access
 * @param string $event_owner Username of user associated with this event
 * @param int    $event_cat   Category of event for <var>$event_owner</var>
 *
 * @staticvar int Used to ensure all event popups have a unique id
 *
 * @uses build_event_popup
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

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event_owner) {
            echo "</span>";
        }
    }
  }
  echo "</a>\n";
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

/** 
 * Gets any site-specific fields for an entry that are stored in the database in the webcal_site_extras table.
 *
 * @param int $eventid Event ID
 *
 * @return array Array with the keys as follows:
 *    - <var>cal_name</var>
 *    - <var>cal_type</var>
 *    - <var>cal_date</var>
 *    - <var>cal_remind</var>
 *    - <var>cal_data</var>
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

/**
 * Reads all the events for a user for the specified range of dates.
 *
 * This is only called once per page request to improve performance.  All the
 * events get loaded into the array <var>$events</var> sorted by time of day
 * (not date).
 *
 * @param string $user      Username
 * @param string $startdate Start date range, inclusive (in YYYYMMDD format)
 * @param string $enddate   End date range, inclusive (in YYYYMMDD format)
 * @param int    $cat_id    Category ID to filter on
 *
 * @return array Array of events
 *
 * @uses query_events
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

/**
 * Gets all the events for a specific date.
 *
 * Events are retreived from the array of pre-loaded events (which was loaded
 * all at once to improve performance).
 *
 * The returned events will be sorted by time of day.
 *
 * @param string $user           Username
 * @param string $date           Date to get events for in YYYYMMDD format
 * @param bool   $get_unapproved Load unapproved events?
 *
 * @return array Array of events
 */
function get_entries ( $user, $date, $get_unapproved=true ) {
  global $events, $TZ_OFFSET;
  $n = 0;
  $ret = array ();

  //echo "<br />\nChecking " . count ( $events ) . " events.  TZ_OFFSET = $TZ_OFFSET, get_unapproved=" . $get_unapproved . "<br />\n";

  //print_r ( $events );

  for ( $i = 0; $i < count ( $events ); $i++ ) {
    // In case of data corruption (or some other bug...)
    if ( empty ( $events[$i] ) || empty ( $events[$i]['cal_id'] ) )
      continue;
    if ( ( ! $get_unapproved ) && $events[$i]['cal_status'] == 'W' ) {
      // ignore this event
    //don't adjust anything  if  no TZ offset or ALL Day Event or Untimed
    } else if ( empty ( $TZ_OFFSET) ||  ( $events[$i]['cal_time'] <= 0 ) ) {
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

/**
 * Reads events visible to a user.
 *
 * Includes layers and possibly public access if enabled
 *
 * @param string $user          Username
 * @param bool   $want_repeated Get repeating events?
 * @param string $date_filter   SQL phrase starting with AND, to be appended to
 *                              the WHERE clause.  May be empty string.
 * @param int    $cat_id        Category ID to filter on.  May be empty.
 *
 * @return array Array of events sorted by time of day
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

/**
 * Reads all the repeated events for a user.
 *
 * This is only called once per page request to improve performance. All the
 * events get loaded into the array <var>$repeated_events</var> sorted by time of day (not
 * date).
 *
 * This will load all the repeated events into memory.
 *
 * <b>Notes:</b>
 * - To get which events repeat on a specific date, use
 *   {@link get_repeating_entries()}.
 * - To get all the dates that one specific event repeats on, call
 *   {@link get_all_dates()}.
 *
 * @param string $user   Username
 * @param int    $cat_id Category ID to filter on  (May be empty)
 * @param string $date   Cutoff date for repeating event endtimes in YYYYMMDD
 *                       format (may be empty)
 *
 * @return Array of repeating events sorted by time of day
 *
 * @uses query_events
 */
function read_repeated_events ( $user, $cat_id = '', $date = ''  ) {
  global $login;
  global $layers;

  $filter = ($date != '') ? "AND (webcal_entry_repeats.cal_end >= $date OR webcal_entry_repeats.cal_end IS NULL) " : '';
  return query_events ( $user, true, $filter, $cat_id );
}

/**
 * Returns all the dates a specific event will fall on accounting for the repeating.
 *
 * Any event with no end will be assigned one.
 *
 * @param string $date     Initial date in raw format
 * @param string $rpt_type Repeating type as stored in the database
 * @param string $end      End date
 * @param string $days     Days events occurs on (for weekly)
 * @param array  $ex_dates Array of exception dates for this event in YYYYMMDD format
 * @param int    $freq     Frequency of repetition
 *
 * @return array Array of dates (in UNIX time format)
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
            if ( ! is_exception ( $td, $ex_days ) )
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
        $day = $daysthismonth - ( $dowLast - $dow ) -
          ( 7 * $whichWeek );
      } else {
        // last weekday is NOT in last week of this month
        $day = $daysthismonth - ( $dowLast - $dow ) -
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
          $day = $daysthismonth - ( $dowLast - $dow ) -
            ( 7 * $whichWeek );
        } else {
          // last weekday is NOT in last week of this month
          $day = $daysthismonth - ( $dowLast - $dow ) -
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

/**
 * Gets all the repeating events for the specified date.
 *
 * <b>Note:</b>
 * The global variable <var>$repeated_events</var> needs to be
 * set by calling {@link read_repeated_events()} first.
 *
 * @param string $user           Username
 * @param string $date           Date to get events for in YYYYMMDD format
 * @param bool   $get_unapproved Include unapproved events in results?
 *
 * @return mixed The query result resource on queries (which can then be
 *               passed to {@link dbi_fetch_row()} to obtain the results), or
 *               true/false on insert or delete queries.
 *
 * @global array Array of repeating events retreived using {@link read_repeated_events()}
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

/**
 * Determines whether the event passed in will fall on the date passed.
 *
 * @param array  $event   The event as an array
 * @param string $dateYmd Date to check in YYYYMMDD format
 *
 * @return bool Does <var>$event</var> occur on <var>$dateYmd</var>?
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

/**
 * Converts a date to a timestamp.
 * 
 * @param string $d Date in YYYYMMDD format
 *
 * @return int Timestamp representing 3:00 (or 4:00 if during Daylight Saving
 *             Time) in the morning on that day
 */
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

/**
 * Checks if a date is an exception for an event.
 *
 * @param string $date   Date in YYYYMMDD format
 * @param array  $exdays Array of dates in YYYYMMDD format
 *
 * @ignore
 */
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

/**
 * Gets the Sunday of the week that the specified date is in.
 *
 * If the date specified is a Sunday, then that date is returned.
 *
 * @param int $year  Year
 * @param int $month Month (1-12)
 * @param int $day   Day of the month
 *
 * @return int The date (in UNIX timestamp format)
 *
 * @see get_monday_before
 */
function get_sunday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 3, 0, 0, $month, $day, $year ) );
  $newdate = mktime ( 3, 0, 0, $month, $day - $weekday, $year );
  return $newdate;
}

/** 
 * Gets the Monday of the week that the specified date is in.
 *
 * If the date specified is a Monday, then that date is returned.
 *
 * @param int $year  Year
 * @param int $month Month (1-12)
 * @param int $day   Day of the month
 *
 * @return int The date (in UNIX timestamp format)
 *
 * @see get_sunday_before
 */
function get_monday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 3, 0, 0, $month, $day, $year ) );
  if ( $weekday == 0 )
    return mktime ( 3, 0, 0, $month, $day - 6, $year );
  if ( $weekday == 1 )
    return mktime ( 3, 0, 0, $month, $day, $year );
  return mktime ( 3, 0, 0, $month, $day - ( $weekday - 1 ), $year );
}

/**
 * Returns the week number for specified date.
 * 
 * Depends on week numbering settings.
 *
 * @param int $date Date in UNIX timestamp format
 *
 * @return string The week number of the specified date
 */
function week_number ( $date ) {
  $tmp = getdate($date);
  $iso = gregorianToISO($tmp['mday'], $tmp['mon'], $tmp['year']);
  $parts = explode('-',$iso);
  $week_number = intval($parts[1]);
  return sprintf("%02d",$week_number);
}

/**
 * Generates the HTML for an add/edit/delete icon.
 *
 * This function is not yet used.  Some of the places that will call it have to
 * be updated to also get the event owner so we know if the current user has
 * access to edit and delete.
 *
 * @param int  $id         Event ID
 * @param bool $can_edit   Can this user edit this event?
 * @param bool $can_delete Can this user delete this event?
 *
 * @return HTML for add/edit/delete icon.
 *
 * @ignore
 */
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

/**
 * Prints all the calendar entries for the specified user for the specified date.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username
 * @param bool   $ssi  Is this being called from week_ssi.php?
 */
function print_date_entries ( $date, $user, $ssi ) {
  global $events, $readonly, $is_admin, $login,
    $public_access, $public_access_can_add, $cat_id;
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
    if ( ! empty ( $cat_id ) )
      print "cat_id=$cat_id&amp;";
    print "date=$date\"><img src=\"new.gif\" alt=\"" .
      translate("New Entry") . "\" class=\"new\" /></a>";
    $cnt++;
  }
  if ( ! $ssi ) {
    echo "<a class=\"dayofmonth\" href=\"day.php?";
    if ( strcmp ( $user, $GLOBALS["login"] ) )
      echo "user=$user&amp;";
    if ( ! empty ( $cat_id ) )
      echo "cat_id=$cat_id&amp;";
    echo "date=$date\">$day</a>";
    if ( $GLOBALS["DISPLAY_WEEKNUMBER"] == "Y" &&
      date ( "w", $dateu ) == $GLOBALS["WEEK_START"] ) {
      echo "&nbsp;<a title=\"" .
        translate("Week") . "&nbsp;" . week_number ( $dateu ) . "\" href=\"week.php?date=$date";
      if ( strcmp ( $user, $GLOBALS["login"] ) )
        echo "&amp;user=$user";
      if ( ! empty ( $cat_id ) )
      echo "&amp;cat_id=$cat_id";
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

/**
 * Checks to see if two events overlap.
 *
 * @param string $time1 Time 1 in HHMMSS format
 * @param int    $duration1 Duration 1 in minutes
 * @param string $time2 Time 2 in HHMMSS format
 * @param int    $duration2 Duration 2 in minutes
 *
 * @return bool True if the two times overlap, false if they do not
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

/**
 * Checks for conflicts.
 *
 * Find overlaps between an array of dates and the other dates in the database.
 *
 * Limits on number of appointments: if enabled in System Settings
 * (<var>$limit_appts</var> global variable), too many appointments can also
 * generate a scheduling conflict.
 * 
 * @todo Update this to handle exceptions to repeating events
 *
 * @param array  $dates        Array of dates in YYYYMMDD format that is
 *                             checked for overlaps.
 * @param int    $duration     Event duration in minutes
 * @param int    $hour         Hour of event (0-23)
 * @param int    $minute       Minute of the event (0-59)
 * @param array  $participants Array of users whose calendars are to be checked
 * @param string $login        The current user name
 * @param int    $id           Current event id (this keeps overlaps from
 *                             wrongly checking an event against itself)
 *
 * @return Empty string for no conflicts or return the HTML of the
 *         conflicts when one or more are found.
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
              if ( ! empty ( $user ) && $user != $login )
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

/**
 * Converts a time format HHMMSS (like 130000 for 1PM) into number of minutes past midnight.
 *
 * @param string $time Input time in HHMMSS format
 *
 * @return int The number of minutes since midnight
 */
function time_to_minutes ( $time ) {
  $h = (int) ( $time / 10000 );
  $m = (int) ( $time / 100 ) % 100;
  $num = $h * 60 + $m;
  return $num;
}

/**
 * Calculates which row/slot this time represents.
 *
 * This is used in day and week views where hours of the time are separeted
 * into different cells in a table.
 *
 * <b>Note:</b> the global variable <var>$TIME_SLOTS</var> is used to determine
 * how many time slots there are and how many minutes each is.  This variable
 * is defined user preferences (or defaulted to admin system settings).
 *
 * @param string $time       Input time in HHMMSS format
 * @param bool   $round_down Should we change 1100 to 1059?
 *                           (This will make sure a 10AM-100AM appointment just
 *                           shows up in the 10AM slow and not in the 11AM slot
 *                           also.)
 *
 * @return int The time slot index
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

/**
 * Generates the HTML for an icon to add a new event.
 *
 * @param string $date   Date for new event in YYYYMMDD format
 * @param int    $hour   Hour of day (0-23)
 * @param int    $minute Minute of the hour (0-59)
 * @param string $user   Participant to initially select for new event
 *
 * @return string The HTML for the add event icon
 */
function html_for_add_icon ( $date=0,$hour="", $minute="", $user="" ) {
  global $TZ_OFFSET;
  global $login, $readonly, $cat_id;
  $u_url = '';

  if ( $readonly == 'Y' )
    return '';

  if ( $minute < 0 ) {
   $minute = abs($minute);
   $hour = $hour -1;
  }
  if ( ! empty ( $user ) && $user != $login )
    $u_url = "user=$user&amp;";
  if ( isset ( $hour ) && $hour != NULL )
    $hour += $TZ_OFFSET;
  return "<a title=\"" . 
 translate("New Entry") . "\" href=\"edit_entry.php?" . $u_url .
    "date=$date" . ( isset ( $hour ) && $hour != NULL && $hour >= 0 ? "&amp;hour=$hour" : ""  ) .
    ( $minute > 0 ? "&amp;minute=$minute" : "" ) .
    ( empty ( $user ) ? "" :  "&amp;defusers=$user" ) .
    ( empty ( $cat_id ) ? "" :  "&amp;cat_id=$cat_id" ) .
    "\"><img src=\"new.gif\" class=\"new\" alt=\"" . 
 translate("New Entry") . "\" /></a>\n";
}

/**
 * Generates the HTML for an event to be viewed in the week-at-glance (week.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param int    $id             Event id
 * @param string $date           Date of event in YYYYMMDD format
 * @param string $time           Time of event in HHMM format
 * @param string $name           Brief description of event
 * @param string $description    Full description of event
 * @param string $status         Status of event ('A', 'W')
 * @param int    $pri            Priority of event
 * @param string $access         Access to event by others ('P', 'R')
 * @param int    $duration       Duration of event in minutes
 * @param string $event_owner    User who created event
 * @param int    $event_category Category id for event
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
        $in_span = true;
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
    if ( ! empty ( $in_span ) )
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

/**
 * Generates the HTML for an event to be viewed in the day-at-glance (day.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param int    $id             Event id
 * @param string $date           Date of event in YYYYMMDD format
 * @param string $time           Time of event in HHMM format
 * @param string $name           Brief description of event
 * @param string $description    Full description of event
 * @param string $status         Status of event ('A', 'W')
 * @param int    $pri            Priority of event
 * @param string $access         Access to event by others ('P', 'R')
 * @param int    $duration       Duration of event in minutes
 * @param string $event_owner    User who created event
 * @param int    $event_category Category id for event
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
        $in_span = true;
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
    if ( ! empty ( $in_span ) )
      $hour_arr[$ind] .= "</span>"; //end color span
  }

  else
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  if ( $pri == 3 ) $hour_arr[$ind] .= "</strong>"; //end font-weight span

  $hour_arr[$ind] .= "</a>";
  if ( $GLOBALS["DISPLAY_DESC_PRINT_DAY"] == "Y" ) {
    $hour_arr[$ind] .= "\n<dl class=\"desc\">\n";
    $hour_arr[$ind] .= "<dt>" . translate("Description") . ":</dt>\n<dd>";
    if ( ! empty ( $GLOBALS['allow_html_description'] ) &&
      $GLOBALS['allow_html_description'] == 'Y' ) {
      $str = str_replace ( "&", "&amp;", $description );
      $str = str_replace ( "&amp;amp;", "&amp;", $str );
      // If there is no html found, then go ahead and replace
      // the line breaks ("\n") with the html break.
      if ( strstr ( $str, "<" ) && strstr ( $str, ">" ) ) {
        // found some html...
        $hour_arr[$ind] .= $str;
      } else {
        // no html, replace line breaks
        $hour_arr[$ind] .= nl2br ( $str );
      }
    } else {
      // html not allowed in description, escape everything
      $hour_arr[$ind] .= nl2br ( htmlspecialchars ( $description ) );
    }
    $hour_arr[$ind] .= "</dd>\n</dl>\n";
  }

  $hour_arr[$ind] .= "<br />\n";
}

/**
 * Prints all the calendar entries for the specified user for the specified date in day-at-a-glance format.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username of calendar
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
        $diff_start_time = $i - $last_row;
        if ( $rowspan_arr[$i] > 1 ) {
          if (  $rowspan_arr[$i] + ( $diff_start_time ) >  $rowspan_arr[$last_row]  ) {
            $rowspan_arr[$last_row] = ( $rowspan_arr[$i] + ( $diff_start_time ) );
          }
          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else {
          $rowspan_arr[$last_row] += $rowspan_arr[$i];
        }
        // this will move entries apart that appear in one field,
        // yet start on different hours
        for ( $u = $diff_start_time ; $u > 0 ; $u-- ) {
          $hour_arr[$last_row] .= "<br />\n"; 
        }
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
    echo "<tr><th class=\"empty\">&nbsp;</th>\n" .
      "<td class=\"hasevents\">$hour_arr[9999]</td></tr>\n";
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
        echo "<td class=\"hasevents\">";
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
          echo "<td rowspan=\"$rowspan\" class=\"hasevents\">";
          if ( $can_add )
            echo html_for_add_icon ( $date, $time_h, $time_m, $user );
          echo "$hour_arr[$i]</td></tr>\n";
        } else {
          echo "<td class=\"hasevents\">";
          if ( $can_add )
            echo html_for_add_icon ( $date, $time_h, $time_m, $user );
          echo "$hour_arr[$i]</td></tr>\n";
        }
      }
    }
  }
}

/**
 * Checks for any unnaproved events.
 *
 * If any are found, display a link to the unapproved events (where they can be
 * approved).
 *
 * If the user is an admin user, also count up any public events.
 * If the user is a nonuser admin, count up events on the nonuser calendar.
 *
 * @param string $user Current user login
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
      if ( $row[0] > 0 ) {
 $str = translate ("You have XXX unapproved events");
 $str = str_replace ( "XXX", $row[0], $str );
        echo "<a class=\"nav\" href=\"list_unapproved.php";
        if ( $user != $login )
          echo "?user=$user\"";
        echo "\">" . $str .  "</a><br />\n";
      }
    }
    dbi_free_result ( $res );
  }
}

/**
 * Looks for URLs in the given text, and makes them into links.
 *
 * @param string $text Input text
 *
 * @return string The text altered to have HTML links for any web links
 *                (http or https)
 */
function activate_urls ( $text ) {
  $str = eregi_replace ( "(http://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $text );
  $str = eregi_replace ( "(https://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $str );
  return $str;
}

/**
 * Displays a time in either 12 or 24 hour format.
 *
 * The global variable $TZ_OFFSET is used to adjust the time.  Note that this
 * is somewhat of a kludge for timezone support.  If an event is set for 11PM
 * server time and the user is 2 hours ahead, it will show up as 1AM, but the
 * date will not be adjusted to the next day.
 *
 * @param string $time          Input time in HHMMSS format
 * @param bool   $ignore_offset If true, then do not use the timezone offset
 *
 * @return string The time in the user's timezone and preferred format
 *
 * @global int The user's timezone offset from the server
 */
function display_time ( $time, $ignore_offset=0 ) {
  global $TZ_OFFSET;
  $hour = (int) ( $time / 10000 );
  if ( ! $ignore_offset )
    $hour += $TZ_OFFSET;
  $min = abs( ( $time / 100 ) % 100 );
  //Prevent goofy times like 8:00 9:30 9:00 10:30 10:00 
  if ( $time < 0 && $min > 0 ) $hour = $hour - 1;
  while ( $hour < 0 )
    $hour += 24;
  while ( $hour > 23 )
    $hour -= 24;
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

/**
 * Returns the full name of the specified month.
 *
 * Use {@link month_short_name()} to get the abbreviated name of the month.
 *
 * @param int $m Number of the month (0-11)
 *
 * @return string The full name of the specified month
 *
 * @see month_short_name
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

/**
 * Returns the abbreviated name of the specified month (such as "Jan").
 *
 * Use {@link month_name()} to get the full name of the month.
 *
 * @param int $m Number of the month (0-11)
 *
 * @return string The abbreviated name of the specified month (example: "Jan")
 *
 * @see month_name
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

/**
 * Returns the full weekday name.
 *
 * Use {@link weekday_short_name()} to get the abbreviated weekday name.
 *
 * @param int $w Number of the day in the week (0=Sunday,...,6=Saturday)
 *
 * @return string The full weekday name ("Sunday")
 *
 * @see weekday_short_name
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

/**
 * Returns the abbreviated weekday name.
 *
 * Use {@link weekday_name()} to get the full weekday name.
 *
 * @param int $w Number of the day in the week (0=Sunday,...,6=Saturday)
 *
 * @return string The abbreviated weekday name ("Sun")
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

/**
 * Converts a date in YYYYMMDD format into "Friday, December 31, 1999",
 * "Friday, 12-31-1999" or whatever format the user prefers.
 *
 * @param string $indate       Date in YYYYMMDD format
 * @param string $format       Format to use for date (default is "__month__
 *                             __dd__, __yyyy__")
 * @param bool   $show_weekday Should the day of week also be included?
 * @param bool   $short_months Should the abbreviated month names be used
 *                             instead of the full month names?
 * @param int    $server_time ???
 *
 * @return string Date in the specified format
 *
 * @global string Preferred date format
 * @global int    User's timezone offset from the server
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


/**
 * Converts a hexadecimal digit to an integer.
 *
 * @param string $val Hexadecimal digit
 *
 * @return int Equivalent integer in base-10
 *
 * @ignore
 */
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

/**
 * Extracts a user's name from a session id.
 *
 * This prevents users from begin able to edit their cookies.txt file and set
 * the username in plain text.
 *
 * @param string $instr A hex-encoded string. "Hello" would be "678ea786a5".
 * 
 * @return string The decoded string
 *
 * @global array Array of offsets
 *
 * @see encode_string
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

/**
 * Takes an input string and encode it into a slightly encoded hexval that we
 * can use as a session cookie.
 *
 * @param string $instr Text to encode
 *
 * @return string The encoded text
 *
 * @global array Array of offsets
 *
 * @see decode_string
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

/**
 * An implementatin of array_splice() for PHP3.
 *
 * @param array $input       Array to be spliced into
 * @param int   $offset      Where to begin the splice
 * @param int   $length      How long the splice should be
 * @param array $replacement What to splice in
 *
 * @ignore
 */
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

/**
 * Loads current user's category info and stuff it into category global
 * variable.
 *
 * @param string $ex_global Don't include global categories ('' or '1')
 */
function load_user_categories ($ex_global = '') {
  global $login, $user, $is_assistant;
  global $categories, $category_owners;
  global $categories_enabled, $is_admin;

  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
    $is_admin ) ) ? $user : $login;  
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

/**
 * Prints dropdown HTML for categories.
 *
 * @param string $form   The page to submit data to (without .php)
 * @param string $date   Date in YYYYMMDD format
 * @param int    $cat_id Category id that should be pre-selected
 */
function print_category_menu ( $form, $date = '', $cat_id = '' ) {
  global $categories, $category_owners, $user, $login;
  echo "<form action=\"{$form}.php\" method=\"get\" name=\"SelectCategory\" class=\"categories\">\n";
  if ( ! empty($date) ) echo "<input type=\"hidden\" name=\"date\" value=\"$date\" />\n";
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  echo translate ("Category") . ": <select name=\"cat_id\" onchange=\"document.SelectCategory.submit()\">\n";
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
  echo "<span id=\"cat\">" . translate ("Category") . ": ";
  echo ( strlen ( $cat_id ) ? $categories[$cat_id] : translate ('All') ) . "</span>\n";
}

/**
 * Converts HTML entities in 8bit.
 *
 * <b>Note:</b> Only supported for PHP4 (not PHP3).
 *
 * @param string $html HTML text
 *
 * @return string The converted text
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

/**
 * Gets a list of an assistant's boss from the webcal_asst table.
 *
 * @param string $assistant Login of assistant
 *
 * @return array Array of bosses, where each boss is an array with the following
 *               fields:
 * - <var>cal_login</var>
 * - <var>cal_fullname</var>
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

/**
 * Is this user an assistant of this boss?
 *
 * @param string $assistant Login of potential assistant
 * @param string $boss      Login of potential boss
 * 
 * @return bool True or false
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

/**
 * Is this user an assistant?
 *
 * @param string $assistant Login for user
 *
 * @return bool true if the user is an assistant to one or more bosses
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

/**
 * Checks the boss user preferences to see if the boss wants to be notified via
 * email on changes to their calendar.
 *
 * @param string $assistant Assistant login
 * @param string $boss      Boss login
 *
 * @return bool True if the boss wants email notifications
 */
function boss_must_be_notified ( $assistant, $boss ) {
  if (user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, "EMAIL_ASSISTANT_EVENTS" )=="Y" ? true : false );
  return true;
}

/**
 * Checks the boss user preferences to see if the boss must approve events
 * added to their calendar.
 *
 * @param string $assistant Assistant login
 * @param string $boss      Boss login
 *
 * @return bool True if the boss must approve new events
 */
function boss_must_approve_event ( $assistant, $boss ) {
  if (user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, "APPROVE_ASSISTANT_EVENT" )=="Y" ? true : false );
  return true;
}

/**
 * Fakes an email for testing purposes.
 *
 * @param string $mailto Email address to send mail to
 * @param string $subj   Subject of email
 * @param string $text   Email body
 * @param string $hdrs   Other email headers
 *
 * @ignore
 */
function fake_mail ( $mailto, $subj, $text, $hdrs ) { 
  echo "To: $mailto <br />\n" .
    "Subject: $subj <br />\n" .
    nl2br ( $hdrs ) . "<br />\n" .
    nl2br ( $text );
}

/**
 * Prints all the entries in a time bar format for the specified user for the
 * specified date.
 *
 * If we are displaying data from someone other than the logged in user, then
 * check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username
 * @param bool   $ssi  Should we not include links to add new events?
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

/**
 * Prints the HTML for an events with a timebar.
 *
 * @param int    $id             Event id
 * @param string $date           Date of event in YYYYMMDD format
 * @param string $time           Time of event in HHMM format
 * @param int    $duration       Duration of event in minutes
 * @param string $name           Brief description of event
 * @param string $description    Full description of event
 * @param string $status         Status of event ('A', 'W')
 * @param int    $pri            Priority of event
 * @param string $access         Access to event by others ('P', 'R')
 * @param string $event_owner    User who created event
 * @param int    $event_category Category id for event
 *
 * @staticvar int Used to ensure all event popups have a unique id
 */
function print_entry_timebar ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner, $event_category=-1 ) {
  global $eventinfo, $login, $user, $PHP_SELF, $prefarray;
  static $key = 0;
  $insidespan = false;
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
  if ($ev_duration > 20)   { $pos = 1; }
   elseif ($ev_padding > 20)   { $pos = 2; }
   else        { $pos = 0; }
 
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
            $insidespan = true;
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
    if ( $insidespan ) { echo ("</span>"); } //end color span
  }
  else
    echo htmlspecialchars ( $name );
  echo "</a>";
  if ( $pri == 3 ) echo "</strong>"; //end font-weight span
  echo "</td>\n";
  if ( $pos < 2 ) {
    if ( $pos < 1 ) {
      echo "<td style=\"width:$ev_duration%;\"><table  class=\"entrybar\">\n<tr>\n<td class=\"entry\">&nbsp;</td>\n";
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

/**
 * Prints the header for the timebar.
 *
 * @param int $start_hour Start hour
 * @param int $end_hour   End hour
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

/**
 * Gets a list of nonuser calendars and return info in an array.
 *
 * @param string $user Login of admin of the nonuser calendars
 *
 * @return array Array of nonuser cals, where each is an array with the
 *               following fields:
 * - <var>cal_login</var>
 * - <var>cal_lastname</var>
 * - <var>cal_firstname</var>
 * - <var>cal_admin</var>
 * - <var>cal_fullname</var>
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

/**
 * Loads nonuser variables (login, firstname, etc.).
 *
 * The following variables will be set:
 * - <var>login</var>
 * - <var>firstname</var>
 * - <var>lastname</var>
 * - <var>fullname</var>
 * - <var>admin</var>
 * - <var>email</var>
 *
 * @param string $login  Login name of nonuser calendar
 * @param string $prefix Prefix to use for variables that will be set.
 *                       For example, if prefix is "temp", then the login will
 *                       be stored in the <var>$templogin</var> global variable.
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

/**
  * Checks the webcal_nonuser_cals table to determine if the user is the
  * administrator for the nonuser calendar.
  *
  * @param string $login   Login of user that is the potential administrator
  * @param string $nonuser Login name for nonuser calendar
  *
  * @return bool True if the user is the administrator for the nonuser calendar
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

/**
 * Loads nonuser preferences from the webcal_user_pref table if on a nonuser
 * admin page.
 *
 * @param string $nonuser Login name for nonuser calendar
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

/**
 * Determines what the day is after the <var>$TZ_OFFSET</var> and sets it globally.
 *
 * The following global variables will be set:
 * - <var>$thisyear</var>
 * - <var>$thismonth</var>
 * - <var>$thisday</var>
 * - <var>$thisdate</var>
 * - <var>$today</var>
 *
 * @param string $date The date in YYYYMMDD format
 */
function set_today($date) {
  global $thisyear, $thisday, $thismonth, $thisdate, $today;
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

/**
 * Converts from Gregorian Year-Month-Day to ISO YearNumber-WeekNumber-WeekDay.
 *
 * @internal JGH borrowed gregorianToISO from PEAR Date_Calc Class and added
 * $GLOBALS["WEEK_START"] (change noted)
 *
 * @param int $day   Day of month
 * @param int $month Number of month
 * @param int $year  Year
 *
 * @return string Date in ISO YearNumber-WeekNumber-WeekDay format
 *
 * @ignore
 */
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

/**
 * Is this a leap year?
 *
 * @internal JGH Borrowed isLeapYear from PEAR Date_Calc Class
 *
 * @param int $year Year
 *
 * @return bool True for a leap year, else false
 *
 * @ignore
 */
function isLeapYear($year='') {
  if (empty($year)) $year = strftime("%Y",time());
  if (strlen($year) != 4) return false;
  if (preg_match('/\D/',$year)) return false;
  return (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0);
}

/**
 * Replaces unsafe characters with HTML encoded equivalents.
 *
 * @param string $value Input text
 *
 * @return string The cleaned text
 */
function clean_html($value){
  $value = htmlspecialchars($value, ENT_QUOTES);
  $value = strtr($value, array(
    '('   => '&#40;',
    ')'   => '&#41;'
  ));
  return $value;
}

/**
 * Removes non-word characters from the specified text.
 *
 * @param string $data Input text
 *
 * @return string The converted text
 */
function clean_word($data) { 
  return preg_replace("/\W/", '', $data);
}

/**
 * Removes non-digits from the specified text.
 *
 * @param string $data Input text
 *
 * @return string The converted text
 */
function clean_int($data) { 
  return preg_replace("/\D/", '', $data);
}

/**
 * Removes whitespace from the specified text.
 *
 * @param string $data Input text
 * 
 * @return string The converted text
 */
function clean_whitespace($data) { 
  return preg_replace("/\s/", '', $data);
}

/**
 * Converts language names to their abbreviation.
 *
 * @param string $name Name of the language (such as "French")
 *
 * @return string The abbreviation ("fr" for "French")
 */
function languageToAbbrev ( $name ) {
  global $browser_languages;
  foreach ( $browser_languages as $abbrev => $langname ) {
    if ( $langname == $name )
      return $abbrev;
  }
  return false;
}

/**
 * Creates the CSS for using gradient.php, if the appropriate GD functions are
 * available.
 *
 * A one-pixel wide image will be used for the background image.
 *
 * <b>Note:</b> The gd library module needs to be available to use gradient
 * images.  If it is not available, a single background color will be used
 * instead.
 *
 * @param string $color   Base color
 * @param int    $height  Height of gradient image
 * @param int    $percent How many percent lighter the top color should be
 *                        than the base color at the bottom of the image
 *
 * @return string The style sheet text to use
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

/**
 * Draws a daily outlook style availability grid showing events that are
 * approved and awaiting approval.
 *
 * @param string $date         Date to show the grid for
 * @param array  $participants Which users should be included in the grid
 * @param string $popup        Not used
 */
function daily_matrix ( $date, $participants, $popup = '' ) {
  global $CELLBG, $TODAYCELLBG, $THFG, $THBG, $TABLEBG;
  global $user_fullname, $repeated_events, $events;
  global $WORK_DAY_START_HOUR, $WORK_DAY_END_HOUR, $TZ_OFFSET,$ignore_offset;

  $increment = 15;
  $interval = 4;
  $participant_pct = '20%'; //use percentage

  $first_hour = $WORK_DAY_START_HOUR;
  $last_hour = $WORK_DAY_END_HOUR;
  $hours = $last_hour - $first_hour;
  $cols = (($hours * $interval) + 1);
  $total_pct = '80%';
  $cell_pct =  80 /($hours * $interval);
  $master = array();

  // Build a master array containing all events for $participants
  for ( $i = 0; $i < count ( $participants ); $i++ ) {

    /* Pre-Load the repeated events for quckier access */
    $repeated_events = read_repeated_events ( $participants[$i], "", $date );
    /* Pre-load the non-repeating events for quicker access */
    $events = read_events ( $participants[$i], $date, $date );

    // get all the repeating events for this date and store in array $rep
    $rep = get_repeating_entries ( $participants[$i], $date );
    // get all the non-repeating events for this date and store in $ev
    $ev = get_entries ( $participants[$i], $date );

    // combine into a single array for easy processing
    $ALL = array_merge ( $rep, $ev );

    foreach ( $ALL as $E ) {
      if ($E['cal_time'] == 0) {
        $E['cal_time'] = $first_hour."0000";
        $E['cal_duration'] = 60 * ( $last_hour - $first_hour );
      } else {
        $E['cal_time'] = sprintf ( "%06d", $E['cal_time']);
      }

      $hour = substr($E['cal_time'], 0, 2 );
      $mins = substr($E['cal_time'], 2, 2 );
       
      // Timezone Offset
      if ( ! $ignore_offset ) $hour += $TZ_OFFSET;
      while ( $hour < 0 ) $hour += 24;
      while ( $hour > 23 ) $hour -= 24;

      // Make sure hour is 2 digits
      $hour = sprintf ( "%02d",$hour);

      // convert cal_time to slot
      if ($mins < 15) {
        $slot = $hour.'';
      } elseif ($mins >= 15 && $mins < 30) {
        $slot = $hour.'.25';
      } elseif ($mins >= 30 && $mins < 45) {
        $slot = $hour.'.5';
      } elseif ($mins >= 45) {
        $slot = $hour.'.75';
      }

      // convert cal_duration to bars
      $bars = $E['cal_duration'] / $increment;

      // never replace 'A' with 'W'
      for ($q = 0; $bars > $q; $q++) {
        $slot = sprintf ("%02.2f",$slot);
        if (strlen($slot) == 4) $slot = '0'.$slot; // add leading zeros
        $slot = $slot.''; // convert to a string
        if ( empty ( $master['_all_'][$slot] ) ||
          $master['_all_'][$slot]['stat'] != 'A') {
          $master['_all_'][$slot]['stat'] = $E['cal_status'];
        }
        if ( empty ( $master[$participants[$i]][$slot] ) ||
          $master[$participants[$i]][$slot]['stat'] != 'A' ) {
          $master[$participants[$i]][$slot]['stat'] = $E['cal_status'];
          $master[$participants[$i]][$slot]['ID'] = $E['cal_id'];
        }
        $slot = $slot + '0.25';
      }

    }
  }
?>
  <br />
  <table  align="center" class="matrixd" style="width:<?php echo $total_pct;?>;" cellspacing="0" cellpadding="0">
  <tr><td class="matrix" colspan="<?php echo $cols;?>"></td></tr>
  <tr><th style="width:<?php echo $participant_pct;?>;">
    <?php etranslate("Participants");?></th>
<?php
  $str = '';
  $MouseOut = "onmouseout=\"window.status=''; this.style.backgroundColor='".$THBG."';\"";
  $CC = 1;
  for($i=$first_hour;$i<$last_hour;$i++) {
    $hour = $i;
    if ( $GLOBALS["TIME_FORMAT"] == "12" ) {
      $hour %= 12;
      if ( $hour == 0 ) $hour = 12;
    }

     for($j=0;$j<$interval;$j++) {
        $str .= ' <td  id="C'.$CC.'" class="dailymatrix" ';
        $MouseDown = 'onmousedown="schedule_event('.$i.','.sprintf ("%02d",($increment * $j)).');"';
        switch($j) {
          case 1:
                  if($interval == 4) { $k = ($hour<=9?'0':substr($hour,0,1)); }
    $str .= 'style="width:'.$cell_pct.'%; text-align:right;"  '.$MouseDown." onmouseover=\"window.status='Schedule a ".$hour.':'.($increment * $j<=9?'0':'').($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut." title=\"Schedule an appointment for ".$hour.':'.($increment * $j<=9?'0':'').($increment * $j).".\">";
                  $str .= $k."</td>\n";
                  break;
          case 2:
                  if($interval == 4) { $k = ($hour<=9?substr($hour,0,1):substr($hour,1,2)); }
    $str .= 'style="width:'.$cell_pct.'%; text-align:left;" '.$MouseDown." onmouseover=\"window.status='Schedule a ".$hour.':'.($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut." title=\"Schedule an appointment for ".$hour.':'.($increment * $j<=9?'0':'').($increment * $j).".\">";
                  $str .= $k."</td>\n";
                  break;
          default:
    $str .= 'style="width:'.$cell_pct.'%;" '.$MouseDown." onmouseover=\"window.status='Schedule a ".$hour.':'.($increment * $j<=9?'0':'').($increment * $j)." appointment.'; this.style.backgroundColor='#CCFFCC'; return true;\" ".$MouseOut." title=\"Schedule an appointment for ".$hour.':'.($increment * $j<=9?'0':'').($increment * $j).".\">";
                  $str .= "&nbsp;&nbsp;</td>\n";
                  break;
        }
       $CC++;
     }
  }
  echo $str .
    "</tr>\n<tr><td class=\"matrix\" colspan=\"$cols\"></td></tr>\n";

  // Add user _all_ to beginning of $participants array
  array_unshift($participants, '_all_');

  // Javascript for cells
  $MouseOver = "onmouseover=\"this.style.backgroundColor='#CCFFCC';\"";
  $MouseOut = "onmouseout=\"this.style.backgroundColor='".$CELLBG."';\"";

  // Display each participant
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    if ($participants[$i] != '_all_') {
      // Load full name of user
      user_load_variables ( $participants[$i], "user_" );
  
      // exchange space for &nbsp; to keep from breaking
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $user_fullname );
    } else {
      $user_nospace = translate("All Attendees");
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $user_nospace );
    }

    echo "<tr>\n<th class=\"row\" style=\"width:{$participant_pct};\">".$user_nospace."</th>\n";
    $col = 1;
    $viewMsg = translate ( "View this entry" );

    // check each timebar
    for ( $j = $first_hour; $j < $last_hour; $j++ ) {
       for ( $k = 0; $k < $interval; $k++ ) {
         $border = ($k == '0') ? ' border-left: 1px solid #000000;' : "";
         $MouseDown = 'onmousedown="schedule_event('.$j.','.sprintf ("%02d",($increment * $k)).');"';
        $RC = $CELLBG;
         //$space = '';
         $space = "&nbsp;";

         $r = sprintf ("%02d",$j) . '.' . sprintf ("%02d", (25 * $k)).'';
         if ( empty ( $master[$participants[$i]][$r] ) ) {
           // ignore this..
         } else if ( empty ( $master[$participants[$i]][$r]['ID'] ) ) {
           // This is the first line for 'all' users.  No event here.
           $space = "<span class=\"matrix\"><img src=\"pix.gif\" alt=\"\" style=\"height: 8px\" /></span>";
         } else if ($master[$participants[$i]][$r]['stat'] == "A") {
           $space = "<a class=\"matrix\" href=\"view_entry.php?id={$master[$participants[$i]][$r]['ID']}\"><img src=\"pix.gif\" title=\"$viewMsg\" alt=\"$viewMsg\" /></a>";
         } else if ($master[$participants[$i]][$r]['stat'] == "W") {
           $space = "<a class=\"matrix\" href=\"view_entry.php?id={$master[$participants[$i]][$r]['ID']}\"><img src=\"pixb.gif\" title=\"$viewMsg\" alt=\"$viewMsg\" /></a>";
         }

         echo "<td class=\"matrixappts\" style=\"width:{$cell_pct}%;$border\" ";
         if ($space == "&nbsp;") echo "$MouseDown $MouseOver $MouseOut";
         echo ">$space</td>\n";
         $col++;
      }
    }
    
    echo "</tr><tr>\n<td class=\"matrix\" colspan=\"$cols\">" .
      "<img src=\"pix.gif\" alt=\"-\" /></td></tr>\n";
  } // End foreach participant
  
  echo "</table><br />\n";
  $busy = translate ("Busy");
  $tentative = translate ("Tentative");
  echo "<table align=\"center\"><tr><td class=\"matrixlegend\" >\n";
  echo "<img src=\"pix.gif\" title=\"$busy\" alt=\"$busy\" /> $busy &nbsp; &nbsp; &nbsp;\n";
  echo "<img src=\"pixb.gif\" title=\"$tentative\" alt=\"$tentative\" /> $tentative\n";
  echo "</td></tr></table>\n";
} 

/**
 * Return the time in HHMMSS format of input time + duration
 *
 *
 * <b>Note:</b> The gd library module needs to be available to use gradient
 * images.  If it is not available, a single background color will be used
 * instead.
 *
 * @param string $time   format "235900"
 * @param int $duration  number of minutes
 *
 * @return string The time in HHMMSS format
 */
function add_duration ( $time, $duration ) {
  $hour = (int) ( $time / 10000 );
  $min = ( $time / 100 ) % 100;
  $minutes = $hour * 60 + $min + $duration;
  $h = $minutes / 60;
  $m = $minutes % 60;
  $ret = sprintf ( "%d%02d00", $h, $m );
  //echo "add_duration ( $time, $duration ) = $ret <br />\n";
  return $ret;
}
?>
