<?php
/**
 * All of WebCalendar's functions
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
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
  global $SERVER_TIMEZONE;
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
 
  // Set SERVER TIMEZONE 
  if ( empty ( $GLOBALS["SERVER_TIMEZONE"] ) )
    $GLOBALS["SERVER_TIMEZONE"] = $GLOBALS["TIMEZONE"];  
  
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
  //$fd = fopen ( "/tmp/webcaldebug.log", "a+", true );
  //fwrite ( $fd, date ( "Y-m-d H:i:s" ) .  "> $msg\n" );
  //fclose ( $fd );
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

  if ( ! access_can_view_page ( $url ) ) {
    if ( access_can_access_function ( ACCESS_WEEK ) )
      $url = "week.php";
    else if ( access_can_access_function ( ACCESS_MONTH ) )
      $url = "month.php";
    else if ( access_can_access_function ( ACCESS_DAY ) )
      $url = "day.php";
    // At this point, this user cannot view the preferred view in their
    // preferences (and they cannot update their preferences), and they
    // cannot view any of the standard day/week/month/year pages.
    // All that's left is a custom view that is either created by them
    // or a global view.
    if ( count ( $views ) > 0 )
      $url = $views[0]['url'];
  }

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
function load_user_preferences ( $guest='') {
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
 
 //allow __public__ pref to be used if logging in or user not validated
  $tmp_login = ( ! empty ( $guest )? "__public__" : $login );
 
  $browser = get_web_browser ();
  $browser_lang = get_browser_language ();
  $prefarray = array ();

  // Note: default values are set in config.php
  $res = dbi_query (
    "SELECT cal_setting, cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '$tmp_login'" );
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
    "WHERE cal_owner = '$tmp_login' OR cal_is_global = 'Y' " .
    "ORDER BY cal_name" );
  if ( $res ) {
    $views = array ();
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[2] == 'S' )
        $url = "view_t.php?timeb=1&amp;id=$row[0]";
      else if ( $row[2] == 'T' )
        $url = "view_t.php?timeb=0&amp;id=$row[0]";
      else if ( $row[2] == 'E' )
        $url = "view_r.php?id=$row[0]";
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

  // If user has not set a language preference or admin has not specified a
 // langiuage, then use their browser
  // settings to figure it out, and save it in the database for future
  // use (email reminders).
  if ( ! $lang_found && strlen ( $tmp_login ) && $tmp_login != "__public__" ) {
   if ( $GLOBALS["LANGUAGE"] == "none" ) {
      $LANGUAGE = $browser_lang;
  } else {
      $LANGUAGE = $GLOBALS["LANGUAGE"];  
  }
    dbi_query ( "INSERT INTO webcal_user_pref " .
      "( cal_login, cal_setting, cal_value ) VALUES " .
      "( '$tmp_login', 'LANGUAGE', '$LANGUAGE' )" );
  }

  if ( empty ( $GLOBALS["DATE_FORMAT_MY"] ) )
    $GLOBALS["DATE_FORMAT_MY"] = "__month__ __yyyy__";
  if ( empty ( $GLOBALS["DATE_FORMAT_MD"] ) )
    $GLOBALS["DATE_FORMAT_MD"] = "__month__ __dd__";
  $is_assistant = empty ( $user ) ? false :
    user_is_assistant ( $tmp_login, $user );
  $has_boss = user_has_boss ( $tmp_login );
  $is_nonuser_admin = ($user) ? user_is_nonuser_admin ( $tmp_login, $user ) : false;
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
 *   - LOG_CREATE
 *   - LOG_APPROVE
 *   - LOG_REJECT
 *   - LOG_UPDATE
 *   - LOG_DELETE
 *   - LOG_NOTIFICATION
 *   - LOG_REMINDER
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
 * If user access control is enabled, then we also check to see if this
 * user is allowed to view each user's calendar.  If not, then that use
 * is not included in the list.
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
  global $my_user_array, $is_nonuser;

  // Return the global variable (cached)
  if ( ! empty ( $my_user_array ) && is_array ( $my_user_array ) )
    return $my_user_array;

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
    // Nonuser (public) can only see themself (unless access control is on)
    if ( $is_nonuser && ! access_is_enabled () ) {
      return array ( $login );
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
      $my_user_array = $ret;
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
  } else {
    // groups not enabled... return all users
    //echo "No groups. ";
    $ret = user_get_users ();
  }

  // If user access control enabled, remove any users that this user
  // does not have 'view' access to.
  if ( access_is_enabled () && ! $is_admin ) {
    $newlist = array ();
    for ( $i = 0; $i < count ( $ret ); $i++ ) {
      if ( access_can_view_user_calendar ( $ret[$i]['cal_login'] ) )
        $newlist[] = $ret[$i];
    }
    $ret = $newlist;
    //echo "<pre>"; print_r ( $ret ); echo "</pre>";
  }

  $my_user_array = $ret;
  return $ret;
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
 * Formats site_extras for display according to their type.
 *
 * This will return an array containing formatted extras indexed on their
 * unique names. Each formatted extra is another array containing two
 * indices: 'name' and 'data', which hold the name of the site_extra and the
 * formatted data, respectively. So, to access the name and data of an extra
 * uniquely name 'Reminder', you would access
 * <var>$array['Reminder']['name']</var> and
 * <var>$array['Reminder']['data']</var>
 *
 * @param array $extras Array of site_extras for an event as returned by
 *                      {@link get_site_extra_fields()}
 *
 * @return array Array of formatted extras.
 */
function format_site_extras ( $extras ) {
  global $site_extras_in_popup, $site_extras;

  $ret = array();

  foreach ( $site_extras as $site_extra ) {
    $data = '';
    $extra_name = $site_extra[0];
    $extra_type = $site_extra[2];
    $extra_arg1 = $site_extra[3];
    $extra_arg2 = $site_extra[4];

    if ( ! empty ( $extras[$extra_name] )
         && ! empty ( $extras[$extra_name]['cal_name'] ) ) {

      $name = translate ( $site_extra[1] );

      if ( $extra_type == EXTRA_DATE ) {

        if ( $extras[$extra_name]['cal_date'] > 0 ) {
          $data = date_to_str ( $extras[$extra_name]['cal_date'] );
        }

      } else if ( $extra_type == EXTRA_TEXT
                  || $extra_type == EXTRA_MULTILINETEXT ) {

        $data = nl2br ( $extras[$extra_name]['cal_data'] );

      } else if ( $extra_type == EXTRA_REMINDER ) {

        if ( $extras[$extra_name]['cal_remind'] <= 0 ) {
          $data = translate ( 'No' );
        } else {
          $data = translate ( 'Yes' );

          if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_DATE ) > 0 ) {
            $data .= '&nbsp;&nbsp;-&nbsp;&nbsp;';
            $data .= date_to_str ( $extras[$extra_name]['cal_date'] );
          } else if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
            $data .= '&nbsp;&nbsp;-&nbsp;&nbsp;';

            $minutes = $extras[$extra_name]['cal_data'];
            $d = (int) ( $minutes / ( 24 * 60 ) );
            $minutes -= ( $d * 24 * 60 );
            $h = (int) ( $minutes / 60 );
            $minutes -= ( $h * 60 );

            if ( $d > 0 ) {
              $data .= $d . '&nbsp;' . translate ( 'days' ) . '&nbsp;';
            }

            if ( $h > 0 ) {
              $data .= $h . '&nbsp;' . translate ( 'hours' ) . '&nbsp;';
            }

            if ( $minutes > 0 ) {
              $data .= $minutes . '&nbsp;' . translate ( 'minutes' );
            }

            $data .= '&nbsp;' . translate ( 'before event' );
          }
        }
      } else {
        $data .= $extras[$extra_name]['cal_data'];
      }

      $ret[$extra_name] = array ( 'name' => $name, 'data' => $data );
    }
  }

  return $ret;
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
  global $site_extras_in_popup;

  if ( $site_extras_in_popup != 'Y' ) {
    return '';
  }

  $extras = format_site_extras ( get_site_extra_fields ( $id ) );

  $ret = '';

  foreach ( $extras as $extra ) {
    $ret .= '<dt>' . $extra['name'] . ":</dt>\n<dd>" . $extra['data'] . "</dd>\n";
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
 * @param string $date   Currently selected date (in YYYYMMDD format)
 * @param bool $trigger   Add onchange event trigger that
 *  calls javascript function $prefix_datechanged()
  *
 * @uses date_selection_html
 */
function print_date_selection ( $prefix, $date, $trigger=false ) {
  print date_selection_html ( $prefix, $date, $trigger );
}

/**
 * Generate HTML for a date selection for use in a form.
 *
 * @param string $prefix Prefix to use in front of form element names
 * @param string $date   Currently selected date (in YYYYMMDD format)
 * @param bool $trigger   Add onchange event trigger that
 *  calls javascript function $prefix_datechanged()
 *
 * @return string HTML for the selection box
 */
function date_selection_html ( $prefix, $date, $trigger=false ) {
  $ret = "";
  $num_years = 20;
 $trigger_str = ( ! empty ( $trigger )? $prefix . "datechanged()" : "");
  if ( strlen ( $date ) != 8 )
    $date = date ( "Ymd" );
  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
  if ( $thisyear - date ( "Y" ) >= ( $num_years - 1 ) )
    $num_years = $thisyear - date ( "Y" ) + 2;
  $ret .= "<select name=\"" . $prefix . "day\" onchange=\"$trigger_str\">\n";
  for ( $i = 1; $i <= 31; $i++ )
    $ret .= "<option value=\"$i\"" .
      ( $i == $thisday ? " selected=\"selected\"" : "" ) . ">$i</option>\n";
  $ret .= "</select>\n<select name=\"" . $prefix . "month\" onchange=\"$trigger_str\">\n";
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    $ret .= "<option value=\"$i\"" .
      ( $i == $thismonth ? " selected=\"selected\"" : "" ) . ">$m</option>\n";
  }
  $ret .= "</select>\n<select name=\"" . $prefix . "year\" onchange=\"$trigger_str\">\n";
  for ( $i = -10; $i < $num_years; $i++ ) {
    $y = $thisyear + $i;
    $ret .= "<option value=\"$y\"" .
      ( $y == $thisyear ? " selected=\"selected\"" : "" ) . ">$y</option>\n";
  }
  $ret .= "</select>\n";
  $ret .= "<input type=\"button\" onclick=\"$trigger_str;selectDate( '" .
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

  $monthstart = mktime( 0,0,0,$thismonth,1,$thisyear);
  $monthend = mktime( 0,0,0,$thismonth + 1,0,$thisyear);

  if ( $SCRIPT == 'day.php' ) {
    $month_ago = date ( "Ymd",
      mktime ( 0, 0, 0, $thismonth - 1, $thisday, $thisyear ) );
    $month_ahead = date ( "Ymd",
      mktime ( 0, 0, 0, $thismonth + 1, $thisday, $thisyear ) );

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
        $ev = get_entries ( $user, $dateYmd, $get_unapproved, 0 );
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
 * Prints the HTML for one event in the month view.
 *
 * @param Event  $event The event
 * @param string $date  The data for which we're printing (YYYYMMDD)
 *
 * @staticvar int Used to ensure all event popups have a unique id
 *
 * @uses build_event_popup
 */
function print_entry ( $event, $date ) {
  global $eventinfo, $login, $user, $PHP_SELF;

  static $key = 0;
  
  global $layers;

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $event->getStatus() == "W" ) $class = "unapprovedentry";
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" ) ||
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  if ( $event->getPriority() == 3 ) echo "<strong>";

  if ( $event->getExtForID() != '' ) {
    $id = $event->getExtForID();
    $name = $event->getName() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $id = $event->getID();
    $name = $event->getName();
  }

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;

  echo "<a title=\"" . 
    translate("View this entry") . "\" class=\"$class\" id=\"$linkid\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $user ) > 0 )
    echo "&amp;user=" . $user;
  echo "\">";

  $icon = "circle.gif";
  $catIcon = '';
  if ( $event->getCategory() > 0 ) {
    $catIcon = "icons/cat-" . $event->getCategory() . ".gif";
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

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ($layer['cal_layeruser'] == $event->getLogin() ) {
        echo("<span style=\"color:" . $layer['cal_color'] . ";\">");
      }
    }
  }


  $timestr = "";
  if ( $event->isAllDay() ) {
    $timestr = translate("All day event");
  } else if ( ! $event->isUntimed() ) {
    $timestr = display_time ( $event->getDatetime() );
    $time_short = preg_replace ("/(:00)/", '', $timestr);
    echo $time_short . "&raquo;&nbsp;";
    if ( $event->getDuration() > 0 ) {
      $timestr .= " - " . display_time ( $event->getEndDateTime() );
    }
  }
  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) ) {
    echo "(" . translate("Private") . ")";
  } else if ( $login != $event->getLogin() && $event->getAccess() == 'R' &&
    strlen ( $event->getLogin() ) ) {
    echo "(" . translate("Private") . ")";
  } else {
    echo htmlspecialchars ( $name );
  }

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event->getLogin() ) {
            echo "</span>";
        }
    }
  }
  echo "</a>\n";
  if ( $event->getPriority() == 3 ) echo "</strong>\n"; //end font-weight span
  echo "<br />";
  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) )
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  else
  if ( $login != $event->getLogin() && $event->getAccess() == 'R' && strlen ( $event->getLogin() ) )
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  else
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      $event->getDescription(), $timestr, site_extras_for_popup ( $id ) );
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
 * @return array Array of Events
 *
 * @uses query_events
 */
function read_events ( $user, $startdate, $enddate, $cat_id = ''  ) {
  global $login;
  global $layers;
  static $user_TIMEZONE;

  $sy = substr ( $startdate, 0, 4 );
  $sm = substr ( $startdate, 4, 2 );
  $sd = substr ( $startdate, 6, 2 );
  $ey = substr ( $enddate, 0, 4 );
  $em = substr ( $enddate, 4, 2 );
  $ed = substr ( $enddate, 6, 2 );
  
  //if called from send_reminders, $user will be empty
  if ( ! empty ($user ) ) {
    //Get TZ_offset of start day
    if ( empty ( $user_TIMEZONE ) ){
      $user_TIMEZONE = get_pref_setting ( $user, "TIMEZONE" );
    } 
    $tz_offset = get_tz_offset ( $user_TIMEZONE, mktime ( 0, 0, 0, $sm, $sd, $sy ) );
  } else {
    // We will just use GMT time
    $tz_offset[0] = 0;
  }
  
  if ( $startdate == $enddate ) {
    if ( $tz_offset[0] == 0 ) {
      $date_filter = " AND webcal_entry.cal_date = $startdate";
    } else if ( $tz_offset[0] > 0 ) {
      $prev_day = mktime ( 3, 0, 0, $sm, $sd - 1, $sy );
      $cutoff = get_time_add_tz ( 240000,  - $tz_offset[0] );
      $date_filter = " AND ( ( webcal_entry.cal_date = $startdate AND " .
        "( webcal_entry.cal_time <= $cutoff OR " .
        "webcal_entry.cal_time = -1 ) ) OR " .
        "( webcal_entry.cal_date = " . date("Ymd", $prev_day ) .
        " AND webcal_entry.cal_time >= $cutoff ) )";
    } else {
      $next_day = mktime ( 0, 0, 0, $sm, $sd + 1, $sy );
      $cutoff = get_time_add_tz ( 000000,  -$tz_offset[0] );
      $date_filter = " AND ( ( webcal_entry.cal_date = $startdate AND " .
        "( webcal_entry.cal_time > $cutoff OR " .
        "webcal_entry.cal_time = -1 ) ) OR " .
        "( webcal_entry.cal_date = " . date("Ymd", $next_day ) .
        " AND webcal_entry.cal_time <= $cutoff ) )";
    }
  } else {
    if ( $tz_offset[0] == 0 ) {
      $date_filter = " AND webcal_entry.cal_date >= $startdate " .
        "AND webcal_entry.cal_date <= $enddate";
    } else if ( $tz_offset[0] > 0 ) {
      $prev_day = date ( ( "Ymd" ), mktime ( 0, 0, 0, $sm, $sd - 1, $sy ) );
      $enddate_minus1 = date ( ( "Ymd" ), mktime ( 0, 0, 0, $em, $ed - 1, $ey ) );
      $cutoff = get_time_add_tz ( 240000, - $tz_offset[0] );
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
      $next_day = date ( ( "Ymd" ), mktime ( 0, 0, 0, $sm, $sd + 1, $sy ) );
      $enddate_plus1 =
        date ( ( "Ymd" ), mktime ( 0, 0, 0, $em, $ed + 1, $ey ) );
      $cutoff = get_time_add_tz ( 000000,  -$tz_offset[0] );
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
 * @return array Array of Events
 */
function get_entries ( $user, $date, $get_unapproved=true, $use_dst=1, $use_my_tz=0 ) {
  global $events, $login;
  $n = 0;
  $ret = array ();
  if ( $use_dst ) {
    $this_user =  ( $use_my_tz == 1 ? $login : $user );
    $user_TIMEZONE = get_pref_setting ( $this_user, "TIMEZONE" );
    $tz_offset = get_tz_offset ( $user_TIMEZONE, '', $date );
  }
// echo "count events" . count ( $events );
  for ( $i = 0; $i < count ( $events ); $i++ ) {
    // In case of data corruption (or some other bug...)
    if ( empty ( $events[$i] ) || $events[$i]->getID() == '' )
      continue;
    if ( ( ! $get_unapproved ) && $events[$i]->getStatus() == 'W' ) {
      // ignore this event
    //don't adjust anything  if  no TZ offset or ALL Day Event or Untimed
    } else if ( empty (  $tz_offset[0]) ||  
    $events[$i]->isAllDay() || $events[$i]->isUntimed() ) {
      if ( $events[$i]->getDate() == $date )
        $ret[$n++] = $events[$i];

    } else if ( $tz_offset[0] > 0 ) {
      $cutoff =  get_time_add_tz ( 240000, - $tz_offset[0] );
//      echo "<br /> cal_time " . $events[$i]['cal_time'] .  " " . $cutoff . "<br />\n";
      $sy = substr ( $date, 0, 4 );
      $sm = substr ( $date, 4, 2 );
      $sd = substr ( $date, 6, 2 );
      $prev_day = date ( ( "Ymd" ), mktime ( 0, 0, 0, $sm, $sd - 1, $sy ) );
        //echo "prev_date = $prev_day <br />\n";
      if ( $events[$i]->getDate() == $date &&
        $events[$i]->isUntimed() ) {
        $ret[$n++] = $events[$i];
        //echo "added event " . $events[$i]->getID() . "<br />\n";
      } else if ( $events[$i]->getDate() == $date &&
        $events[$i]->getTime() < $cutoff ) {
        $ret[$n++] = $events[$i];
        //echo "added event " . $events[$i]->getID() . "<br />\n";
      } else if ( $events[$i]->getDate() == $prev_day &&
        $events[$i]->getTime() >= $cutoff ) {
        $ret[$n++] = $events[$i];
        //echo "added event " . $events[$i]->getID() . "<br />\n";
      }
    } else {
      //TZ < 0
      $cutoff = get_time_add_tz ( 000000, -$tz_offset[0] );
      //echo $events[$i]->getDate() .$events[$i]->getTime() . " "  .$date . $cutoff . "<br>";;
      $sy = substr ( $date, 0, 4 );
      $sm = substr ( $date, 4, 2 );
      $sd = substr ( $date, 6, 2 );
      $next_day = date ( ( "Ymd" ), mktime ( 0, 0, 0, $sm, $sd + 1, $sy ) );
      //echo "next_date = $next_day <br />\n";
      if ( $events[$i]->isUntimed() ) {
        if ( $events[$i]->getDate() == $date ) {
          $ret[$n++] = $events[$i];
          //echo "added event " . $events[$i]->getID() . "<br />\n";
        }
      } else {
   if ( $events[$i]->getDate() == $date &&
          $events[$i]->getTime() > $cutoff ) {
          $ret[$n++] = $events[$i];
          //echo "added event $events[$i][cal_id] <br />\n";
        } else if ( $events[$i]->getDate() == $next_day &&
          $events[$i]->getTime() <= $cutoff ) {
          $ret[$n++] = $events[$i];
          //echo "added event " . $events[$i]->getID() . "<br />\n";
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
 * @return array Array of Events sorted by time of day
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

      if ( $want_repeated && ! empty ( $row[12] ) ) {
        $item =& new RepeatingEvent ( $row[0], $row[1], $row[2], $row[3], $row[4], $row[5],
                                $row[6], $row[7], $row[8], $row[9], $row[10], $row[11],
                                $row[12], $row[13], $row[14], $row[15], array() );
      } else {
        $item =& new Event ( $row[0], $row[1], $row[2], $row[3], $row[4], $row[5],
                             $row[6], $row[7], $row[8], $row[9], $row[10], $row[11] );
      }

      if ( $item->getID() != $checkdup_id ) {
        $checkdup_id = $item->getID();
        $first_i_this_id = $i;
      }

      if ( $item->getLogin() == $user ) {
        // Insert this one before all other ones with this ID.
        my_array_splice ( $result, $first_i_this_id, 0, array($item) );
        $i++;

        if ($first_i_this_id + 1 < $i) {
          // There's another one with the same ID as the one we inserted.
          // Check for dup and if so, delete it.
          $other_item = $result[$first_i_this_id + 1];
          if ($layers_byuser[$other_item->getLogin()] == 'N') {
            // NOTE: array_splice requires PHP4
            my_array_splice ( $result, $first_i_this_id + 1, 1, "" );
            $i--;
          }
        }
      } else {
        if ($i == $first_i_this_id
          || ( ! empty ( $layers_byuser[$item->getLogin()] ) &&
          $layers_byuser[$item->getLogin()] != 'N' ) ) {
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
      if ( $result[$i]->getID() != '' ) {
        $res = dbi_query ( "SELECT cal_date FROM webcal_entry_repeats_not " .
          "WHERE cal_id = " . $result[$i]->getID() );
        while ( $row = dbi_fetch_row ( $res ) ) {
          $result[$i]->addRepeatException($row[0]);
        }
        dbi_free_result ( $res );
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
 * @return array Array of RepeatingEvents sorted by time of day
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
  //echo "get_all_dates ( $date, '$rpt_type', $end, '$days', [array], $freq ) <br>\n";
  $currentdate = floor($date/ONE_DAY)*ONE_DAY;
  $realend = floor($end/ONE_DAY)*ONE_DAY;
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
    $realend = mktime( 0, 0, 0, $thismonth, $thisday, $thisyear );
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
      $cdate += ONE_DAY * $freq;
      while ($cdate <= $realend+ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++]=$cdate;
        $cdate += ONE_DAY * $freq;
      }
    } else if ($rpt_type == 'weekly') {
      $daysarray = array();
      $r=0;
      $dow = date("w",$date);
      $cdate = $date - ($dow * ONE_DAY);
      for ($i = 0; $i < 7; $i++) {
        $isDay = substr($days, $i, 1);
        if (strcmp($isDay,"y")==0) {
          $daysarray[$r++]=$i * ONE_DAY;
        }
      }
      //we do inclusive counting on end dates.
      while ($cdate <= $realend+ONE_DAY) {
        //add all of the days of the week.
        for ($j=0; $j<$r;$j++) {
          $td = $cdate + $daysarray[$j];
          if ($td >= $date) {
            if ( ! is_exception ( $cdate, $ex_days ) )
              $ret[$n++] = $td;
          }
        }
        //skip to the next week in question.
        $cdate += ( ONE_DAY * 7 ) * $freq;
      }
    } else if ($rpt_type == 'monthlyByDay') {
      $dow  = date('w', $date);
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $week  = floor(date("d", $date)/7);
      $thismonth+=$freq;
      //dow1 is the weekday that the 1st of the month falls on
      $dow1 = date('w',mktime ( 0, 0, 0, $thismonth, 1, $thisyear ));
      $t = $dow - $dow1;
      if ($t < 0) $t += 7;
      $day = 7*$week + $t + 1;
      $cdate = mktime ( 0, 0, 0, $thismonth, $day, $thisyear );
      while ($cdate <= $realend+ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thismonth+=$freq;
        //dow1 is the weekday that the 1st of the month falls on
        $dow1time = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
        $dow1 = date ( 'w', $dow1time );
        $t = $dow - $dow1;
        if ($t < 0) $t += 7;
        $day = 7*$week + $t + 1;
        $cdate = mktime ( 0, 0, 0, $thismonth, $day, $thisyear );
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
      $dowLast += date('w',mktime ( 0, 0, 0, $thismonth + 1, -1, $thisyear ));
      if ( $dowLast >= $dow ) {
        // last weekday is in last week of this month
        $day = $daysinmonth - ( $dowLast - $dow ) -
          ( 7 * $whichWeek );
      } else {
        // last weekday is NOT in last week of this month
        $day = $daysinmonth - ( $dowLast - $dow ) -
          ( 7 * ( $whichWeek + 1 ) );
      }
      $cdate = mktime ( 0, 0, 0, $thismonth, $day, $thisyear );
      while ($cdate <= $realend+ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thismonth += $freq;
        if ( $thismonth > 12 ) {
          $thisyear++;
          $thismonth -= 12;
        }
        // get weekday for last day of month
        $dowLast += date('w',mktime ( 0, 0, 0, $thismonth + 1, -1 ,$thisyear ));
        if ( $dowLast >= $dow ) {
          // last weekday is in last week of this month
          $day = $daysinmonth - ( $dowLast - $dow ) -
            ( 7 * $whichWeek );
        } else {
          // last weekday is NOT in last week of this month
          $day = $daysinmonth - ( $dowLast - $dow ) -
            ( 7 * ( $whichWeek + 1 ) );
        }
        $cdate = mktime ( 0, 0, 0, $thismonth, $day, $thisyear );
      }
    } else if ($rpt_type == 'monthlyByDate') {
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
      $hour      = date('H',$date);
      $minute    = date('i',$date);

      $thismonth += $freq;
      $cdate = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
      while ($cdate <= $realend+ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thismonth += $freq;
        $cdate = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
      }
    } else if ($rpt_type == 'yearly') {
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
      $hour      = date('H',$date);
      $minute    = date('i',$date);

      $thisyear += $freq;
      $cdate = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
      while ($cdate <= $realend+ONE_DAY) {
        if ( ! is_exception ( $cdate, $ex_days ) )
          $ret[$n++] = $cdate;
        $thisyear += $freq;
        $cdate = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
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
 * @global array Array of {@link RepeatingEvent}s retreived using {@link read_repeated_events()}
 */
function get_repeating_entries ( $user, $dateYmd, $get_unapproved=true ) {
  global $repeated_events, $tz_offset;
  $n = 0;
  $ret = array ();
  //echo count($repeated_events)." - checking date $dateYmd <br />\n";
  for ( $i = 0; $i < count ( $repeated_events ); $i++ ) {
    //echo "  ($i) " . $repeated_events[$i]['cal_name'] . " <br/>";
    if ( $repeated_events[$i]->getStatus() == 'A' || $get_unapproved ) {
      if ( repeated_event_matches_date ( $repeated_events[$i], $dateYmd ) ) {
        // make sure this is not an exception date...
        $unixtime = date_to_epoch ( $dateYmd );
        if ( ! is_exception ( $unixtime, $repeated_events[$i]->getRepeatExceptions() ) )
          $ret[$n++] = $repeated_events[$i];
      }
    }
  }
  return $ret;
}

/**
 * Determines whether the event passed in will fall on the date passed.
 *
 * @param Event  $event   The event
 * @param string $dateYmd Date to check in YYYYMMDD format
 *
 * @return bool Does <var>$event</var> occur on <var>$dateYmd</var>?
 */
function repeated_event_matches_date($event,$dateYmd) {
  global $days_per_month, $ldays_per_month;
  // only repeat after the beginning, and if there is an end
  // before the end
  $date = date_to_epoch ( $dateYmd );
  $thisyear = substr($dateYmd, 0, 4);
  $start = date_to_epoch ( $event->getDate() );
  $end   = date_to_epoch ( $event->getRepeatEnd() );
  $freq = $event->getRepeatFrequency();
  $thismonth = substr($dateYmd, 4, 2);
  if ($event->getRepeatEnd() && $dateYmd > date("Ymd",$end) )
    return false;
  if ( $dateYmd <= date("Ymd",$start) )
    return false;
  $id = $event->getID();

  if ($event->getRepeatType() == 'daily') {
    if ( (floor(($date - $start)/ONE_DAY)%$freq) )
      return false;
    return true;
  } else if ($event->getRepeatType() == 'weekly') {
    $dow  = date("w", $date);
    $dow1 = date("w", $start);
    $isDay = substr($event->getRepeatDays(), $dow, 1);
    $wstart = $start - ($dow1 * ONE_DAY);
    if (floor(($date - $wstart)/604800)%$freq)
      return false;
    return (strcmp($isDay,"y") == 0);
  } else if ($event->getRepeatType() == 'monthlyByDay') {
    $dowS = date("w", $start);
    $dow  = date("w", $date);
    // do this comparison first in hopes of best performance
    if ( $dowS != $dow )
      return false;
    $mthS = date("m", $start);
    $yrS  = date("Y", $start);
    $dayS  = floor(date("d", $start));
    $dowS1 = ( date ( "w", $start - ( ONE_DAY * ( $dayS - 1 ) ) ) + 35 ) % 7;
    $days_in_first_weekS = ( 7 - $dowS1 ) % 7;
    $whichWeekS = floor ( ( $dayS - $days_in_first_weekS ) / 7 );
    if ( $dowS >= $dowS1 && $days_in_first_weekS )
      $whichWeekS++;
    //echo "dayS=$dayS;dowS=$dowS;dowS1=$dowS1;wWS=$whichWeekS<br />\n";
    $mth  = date("m", $date);
    $yr   = date("Y", $date);
    $day  = date("d", $date);
    $dow1 = ( date ( "w", $date - ( ONE_DAY * ( $day - 1 ) ) ) + 35 ) % 7;
    $days_in_first_week = ( 7 - $dow1 ) % 7;
    $whichWeek = floor ( ( $day - $days_in_first_week ) / 7 );
    if ( $dow >= $dow1 && $days_in_first_week )
      $whichWeek++;
    //echo "day=$day;dow=$dow;dow1=$dow1;wW=$whichWeek<br />\n";

    if ((($yr - $yrS)*12 + $mth - $mthS) % $freq)
      return false;

    return ( $whichWeek == $whichWeekS );
  } else if ($event->getRepeatType() == 'monthlyByDayR') {
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
  } else if ($event->getRepeatType() == 'monthlyByDate') {
    $mthS = date("m", $start);
    $yrS  = date("Y", $start);

    $mth  = date("m", $date);
    $yr   = date("Y", $date);

    if ((($yr - $yrS)*12 + $mth - $mthS) % $freq)
      return false;

    return (date("d", $date) == date("d", $start));
  }
  else if ($event->getRepeatType() == 'yearly') {
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
  $weekday = date ( "w", mktime ( 12, 0, 0, $month, $day, $year ) );
  $newdate = mktime ( 12, 0, 0, $month, $day - $weekday, $year );
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
  $weekday = date ( "w", mktime ( 12, 0, 0, $month, $day, $year ) );
  if ( $weekday == 0 )
    return mktime ( 12, 0, 0, $month, $day - 6, $year );
  if ( $weekday == 1 )
    return mktime ( 12, 0, 0, $month, $day, $year );
  return mktime ( 12, 0, 0, $month, $day - ( $weekday - 1 ), $year );
}

/**
 * Returns the week number for specified date.
 *   date ("W") is not available in PHP < 4.1.0
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
    $public_access, $public_access_can_add, $cat_id, $is_nonuser;
  $cnt = 0;
  $get_unapproved = ( $GLOBALS["DISPLAY_UNAPPROVED"] == "Y" );
  // public access events always must be approved before being displayed
  if ( $user == "__public__" )
    $get_unapproved = false;

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );
  $dateu = mktime ( 0, 0, 0, $month, $day, $year );
  $can_add = ( $readonly == "N" || $is_admin );
  if ( $public_access == "Y" && $public_access_can_add != "Y" &&
    $login == "__public__" )
    $can_add = false;
  if ( $readonly == 'Y' )
    $can_add = false;
  if ( $is_nonuser )
  if ( $is_nonuser )
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
//echo $date . "<br>";
//print_r ($ev);
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]->getTime() < $ev[$i]->getTime() ) {
      if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
        print_entry ( $rep[$cur_rep], $date );
        $cnt++;
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      print_entry ( $ev[$i], $date );
      $cnt++;
    }
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
      print_entry ( $rep[$cur_rep], $date );
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
 * @param int    $eventstart   GMT starttime timestamp
 * @param array  $participants Array of users whose calendars are to be checked
 * @param string $login        The current user name
 * @param int    $id           Current event id (this keeps overlaps from
 *                             wrongly checking an event against itself)
 *
 * @return Empty string for no conflicts or return the HTML of the
 *         conflicts when one or more are found.
 */
function check_for_conflicts ( $dates, $duration, $eventstart,
  $participants, $login, $id ) {
  global $single_user_login, $single_user;
  global $repeated_events, $limit_appts, $limit_appts_number;
  if (!count($dates)) return false;
  $hour = date ( "H", $eventstart );
  $minute = date ( "i", $eventstart ); 
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
    //Need to add user's timezone offset
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    while ( $row = dbi_fetch_row ( $res ) ) {
      //Add to an array to see if it has been found already for the next part.
      $found[$count++] = $row[4];
      // see if either event overlaps one another
      if ( $row[4] != $id && ( empty ( $row[5] ) || $row[5] != $id ) ) {
        $time2 = sprintf ( "%06d", $row[1] );
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
            $conflicts .= " (" . display_time ( $row[8] . $time2 );
            if ( $duration2 > 0 )
              $conflicts .= "-" .
                display_time ( $row[8] . add_duration ( $time2, $duration2 ) );
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
      //  echo $repeated_events[$dd]->getID() . "<br />";
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
        if ( $row->getID() != $id && ( $row->getExtForID() == '' || 
          $row->getExtForID() != $id ) ) {
          $time2 = $row->getTime();
          $duration2 = $row->getDuration();
          if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
            $conflicts .= "<li>";
            if ( $single_user != "Y" )
              $conflicts .= $row->getLogin() . ": ";
            if ( $row->getAccess() == 'R' && $row->getLogin() != $login )
              $conflicts .=  "(" . translate("Private") . ")";
            else {
              $conflicts .=  "<a href=\"view_entry.php?id=" . $row->getID();
              if ( ! empty ( $user ) && $user != $login )
                $conflicts .= "&amp;user=$user";
              $conflicts .= "\">" . $row->getName() . "</a>";
            }
            $conflicts .= " (" . display_time ( $dateYmd . $time2 );
            if ( $duration2 > 0 )
              $conflicts .= "-" .
                display_time ( $dateYmd . add_duration ( $time2, $duration2 ) );
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
function calc_time_slot ( $time, $round_down = false, $date = '' ) {
  global $TIME_SLOTS, $tz_offset;

  $interval = ( 24 * 60 ) / $TIME_SLOTS;
  $mins_since_midnight = time_to_minutes ( $time ); 
  $ret = (int) ( $mins_since_midnight / $interval );
  if ( $round_down ) {
    if ( $ret * $interval == $mins_since_midnight )
      $ret--;
  }
  if ( $ret > $TIME_SLOTS )
    $ret = $TIME_SLOTS;

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
  return "<a title=\"" . 
 translate("New Entry") . "\" href=\"edit_entry.php?" . $u_url .
    "date=$date" . ( strlen ( $hour ) > 0 ? "&amp;hour=$hour" : "" ) .
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
 * @param Event  $event          The event
 * @param string $date           Date for which we're printing (in YYYYMMDD format)
 * @param string $override_class If set, then this is the class to use
 * @param bool   $show_time      If enabled, then event time is displayed
 */
function html_for_event_week_at_a_glance ( $event, $date, $override_class='', $show_time=true ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user;
  global $DISPLAY_ICONS, $PHP_SELF, $TIME_SLOTS, $WORK_DAY_START_HOUR,
    $WORK_DAY_END_HOUR;
  global $layers, $DISPLAY_TZ, $tz_offset;
  static $key = 0;

  if ( $event->getExtForID() != '' ) {
    $id = $event->getExtForID();
    $name = $event->getName() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $id = $event->getID();
    $name = $event->getName();
  }
  // Figure out which time slot it goes in.
  if ( ! $event->isUntimed() ) {
    $tz_time = get_time_add_tz( $event->getTime(), $tz_offset[0] );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;
    if ( $ind > $last_slot )
      $last_slot = $ind;
    } else if ( $event->isAllDay() ) {
    // all-day event
    $ind = $first_slot;
  } else {
    // untimed event
    $ind = 9999;
  }

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $event->getStatus() == "W" ) $class = "unapprovedentry";
  }

  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" ) ||
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_r.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  if ( ! empty ( $override_class ) )
    $class .= " " . $override_class;

  // avoid php warning for undefined array index
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  $catIcon = "icons/cat-" . $event->getCategory() . ".gif";
  if ( $event->getCategory() > 0 && file_exists ( $catIcon ) ) {
    $hour_arr[$ind] .= "<img src=\"$catIcon\" alt=\"$catIcon\" />";
  }

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;

  if ( $event->isAllDay()  || $event->isUntimed()) {
    $hour_arr[$ind] .= "<img src=\"circle.gif\" class=\"bullet\" alt=\"*\" /> ";
  }

  $hour_arr[$ind] .= "<a title=\"" . 
  translate("View this entry") . "\" class=\"$class\" id=\"$linkid\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $GLOBALS["user"] ) > 0 )
    $hour_arr[$ind] .= "&amp;user=" . $GLOBALS["user"];
  $hour_arr[$ind] .= "\">";
  if ( $event->getPriority() == 3 )
    $hour_arr[$ind] .= "<strong>";

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event->getLogin() ) {
       $in_span = true;
        $hour_arr[$ind] .= "<span style=\"color:" . $layer['cal_color'] . ";\">";
      }
    }
  }
  if ( $event->isAllDay() ) {
    $timestr = translate("All day event");
    // Set start cell of all-day event to beginning of work hours
    if ( empty ( $rowspan_arr[$first_slot] ) )
      $rowspan_arr[$first_slot] = 0; // avoid warning below
    // which slot is end time in? take one off so we don't
    // show 11:00-12:00 as taking up both 11 and 12 slots.
    $rowspan = $last_slot - $first_slot + 1;
    if ( $rowspan > $rowspan_arr[$first_slot] && $rowspan > 1 )
      $rowspan_arr[$first_slot] = $rowspan;
  } else if ( $event->getTime() >= 0 ) {
    if ( $show_time )
      $hour_arr[$ind] .= display_time ( $event->getDatetime() ) . "&raquo;&nbsp;";
    $timestr = display_time ( $event->getDatetime() );
    if ( $event->getDuration() > 0 ) {
      $timestr .= "-" . display_time ( $event->getEndDateTime() , $DISPLAY_TZ );
      $end_time = get_time_add_tz($event->getEndTime(), $tz_offset[0]);
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

  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) ) {
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  } else if ( $login != $event->getLogin() && $event->getAccess() == 'R' &&
    strlen ( $event->getLogin() ) ) {
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  } else if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
    if ( ! empty ( $in_span ) )
      $hour_arr[$ind] .= "</span>"; //end color span
  } else {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  }

  if ( $event->getPriority() == 3 ) $hour_arr[$ind] .= "</strong>"; //end font-weight span
    $hour_arr[$ind] .= "</a>";
  //if ( $DISPLAY_ICONS == "Y" ) {
  //  $hour_arr[$ind] .= icon_text ( $id, true, true );
  //}
  $hour_arr[$ind] .= "<br />\n";
  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) ) {
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  } else if ( $login != $event->getLogin() && $event->getAccess() == 'R' &&
    strlen ( $event->getLogin() ) ) {
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  } else {
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      $event->getDescription(), $timestr, site_extras_for_popup ( $id ) );
  }
}

/**
 * Generates the HTML for an event to be viewed in the day-at-glance (day.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param Event  $event The event
 * @param string $date  Date of event in YYYYMMDD format
 */
function html_for_event_day_at_a_glance ( $event, $date ) {
  global $first_slot, $last_slot, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user, $tz_offset;
  static $key = 0;
  global $layers, $PHP_SELF, $TIME_SLOTS;

  if ( $event->getExtForID() != '' ) {
    $id = $event->getExtForID();
    $name = $event->getName() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $id = $event->getID();
    $name = $event->getName();
  }

  // calculate slot length in minutes
  $interval = ( 60 * 24 ) / $TIME_SLOTS;

  $time = $event->getTime();

  // If TZ_OFFSET make this event before the start of the day or
  // after the end of the day, adjust the time slot accordingly.
  if ( ! $event->isUntimed() ) {
 $tz_time = get_time_add_tz( $event->getTime(), $tz_offset[0] );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;
    if ( $ind > $last_slot )
      $last_slot = $ind;
  } else
    $ind = 9999;
  //echo "time = $time <br />\nind = $ind <br />\nfirst_slot = $first_slot<br />\n";

  //echo "Using slot $ind<br/>";

  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $event->getStatus() == "W" )
      $class = "unapprovedentry";
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" )  || 
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  $catIcon = "icons/cat-" . $event->getCategory() . ".gif";
  if ( $event->getCategory() > 0 && file_exists ( $catIcon ) ) {
    $hour_arr[$ind] .= "<img src=\"$catIcon\" alt=\"$catIcon\" />";
  }

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;

  $hour_arr[$ind] .= "<a title=\"" .
    translate("View this entry") . "\" class=\"$class\" id=\"$linkid\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $GLOBALS["user"] ) > 0 )
    $hour_arr[$ind] .= "&amp;user=" . $GLOBALS["user"];
  $hour_arr[$ind] .= "\">";
  if ( $event->getPriority() == 3 ) $hour_arr[$ind] .= "<strong>";

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event->getLogin() ) {
     $in_span = true;
        $hour_arr[$ind] .= "<span style=\"color:" . $layer['cal_color'] . ";\">";
      }
    }
  }

  if ( $event->isAllDay() ) {
    $hour_arr[$ind] .= "[" . translate("All day event") . "] ";
  } else if ( $time >= 0 ) {
    $hour_arr[$ind] .= "[" . display_time ( $event->getDatetime() );
    if ( $event->getDuration() > 0 ) {
      $hour_arr[$ind] .= "-" . display_time ( $event->getEndDateTime() );
      // which slot is end time in? take one off so we don't
      // show 11:00-12:00 as taking up both 11 and 12 slots.
      $end_time = get_time_add_tz($event->getEndTime(), $tz_offset[0]);
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
  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) )
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  else
  if ( $login != $event->getLogin() && $event->getAccess() == 'R' && strlen ( $event->getLogin() ) )
    $hour_arr[$ind] .= "(" . translate("Private") . ")";
  else
  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) )
  {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  if ( ! empty ( $in_span ) )
      $hour_arr[$ind] .= "</span>"; //end color span
  }

  else
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  if ( $event->getPriority() == 3 ) $hour_arr[$ind] .= "</strong>"; //end font-weight span

  $hour_arr[$ind] .= "</a>";
  if ( $GLOBALS["DISPLAY_DESC_PRINT_DAY"] == "Y" ) {
    $hour_arr[$ind] .= "\n<dl class=\"desc\">\n";
    $hour_arr[$ind] .= "<dt>Description:</dt>\n<dd>";
    $hour_arr[$ind] .= htmlspecialchars ( $event->getDescription() );
    $hour_arr[$ind] .= "</dd>\n</dl>\n";
  }

  $hour_arr[$ind] .= "<br />\n";

  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) )
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  else if ( $login != $event->getLogin() && $event->getAccess() == 'R' &&
    strlen ( $event->getLogin() ) )
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  else
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(), $event->getDescription(),
      "", site_extras_for_popup ( $id ) );
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
  global $TABLEBG, $CELLBG, $TODAYCELLBG, $THFG, $THBG, $TIME_SLOTS, $TIMEZONE;
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
  $ev = get_entries ( $user, $date, $get_unapproved, true, true );
  $hour_arr = array ();
  $interval = ( 24 * 60 ) / $TIME_SLOTS;
  $first_slot = (int) ( ( ( $WORK_DAY_START_HOUR ) * 60 ) / $interval );
  $last_slot = (int) ( ( ( $WORK_DAY_END_HOUR  ) * 60 ) / $interval);
  //echo "first_slot = $first_slot<br />\nlast_slot = $last_slot<br />\ninterval = $interval<br />\nTIME_SLOTS = $TIME_SLOTS<br />\n";
  $rowspan_arr = array ();
  $all_day = 0;
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]->getTime() < $ev[$i]->getTime() ) {
      if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
        if ( $rep[$cur_rep]->isAllDay() )
          $all_day = 1;
        html_for_event_day_at_a_glance ( $rep[$cur_rep], $date );
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      if ( $ev[$i]->isAllDay() )
        $all_day = 1;
      html_for_event_day_at_a_glance ( $ev[$i], $date );
    }
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
      if ( $rep[$cur_rep]->isAllDay() )
        $all_day = 1;
      html_for_event_day_at_a_glance ( $rep[$cur_rep], $date );
    }
    $cur_rep++;
  }

  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  //echo "First Slot: $first_slot; Last Slot: $last_slot<br />\n";
  $i = 0;
  if ( $first_slot < 0 )
    $i = $first_slot;
  for ( ; $i < $TIME_SLOTS; $i++ ) {
    if ( $rowspan > 1 ) {
      if ( ! empty ( $hour_arr[$i] ) ) {
        $diff_start_time = $i - $last_row;
        if ( ! empty ( $rowspan_arr[$i] ) && $rowspan_arr[$i] > 1 ) {
          if (  $rowspan_arr[$i] + ( $diff_start_time ) >  $rowspan_arr[$last_row]  ) {
            $rowspan_arr[$last_row] = ( $rowspan_arr[$i] + ( $diff_start_time ) );
          }
          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else {
          if ( ! empty ( $rowspan_arr[$i] ) )
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
 * Timezones can be used to adjust the time.  Note that this
 * date adjustment, if needed, will have to be done external to this function
 *
 * @param string $time          Input time in HHMMSS format
 *   Optionally, the format can be YYYYMMDDHHMMSS and the date will be
 *   extracted for use in timezone offset calculations
 * @param int   $control bitwise command value 
 *   0 default 
 *   1 ignore_offset Do not use the timezone offset
 *   2 show_tzid Show abbrev TZ id ie EST after time
 * @param timestamp $timestamp  Allows for proper DST calculation
 * @param user_timezone $user_timezone  user's timezone for non-logged in user
 *
 * @return string The time in the user's timezone and preferred format
 *
 * @global string $TIMEZONE The logged in user's timezone
 */
function display_time ( $time, $control=0, $timestamp = '', $user_timezone='' ) {
  global $TIMEZONE; 
  
  $tz = ( empty ( $user_timezone )? $TIMEZONE : $user_timezone );

  // if $time < 100000, it is sometimes passed as 5 digits
  // so the length could be 13 or 14 when appended to YYYYMMDD 
  if (  strlen ( $time ) == 13 || strlen ( $time ) == 14 ) {
    //date was passed also as YYYYMMDDHHMMSS
    //just a hack way to avoid another variable
    $sy = substr ( $time, 0, 4 );
    $sm = substr ( $time, 4, 2 );
    $sd = substr ( $time, 6, 2 );
    $timestamp = mktime ( 0, 0, 0, $sm, $sd, $sy );
    $time = substr ( $time, 8, 6 );
  }  
 // $control & 1 = do not do timezone calculations
  if ( ! empty ( $timestamp ) && ! ( $control & 1 ) ) {
    $tz_offset = get_tz_offset ( $tz, $timestamp );
    $tzid = " " . $tz_offset[1];
  } else {
    $tz_offset[0] = 0;
    $tzid = ' GMT';
  }
  if ( ! ( $control & 1 ) ) {
    $time = get_time_add_tz ( $time, $tz_offset[0] );
  } else {
    $tzid = ' GMT'; 
  }
  $hour = (int) ( $time / 10000 );
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
  if ( $control & 2 ) $ret .= $tzid;
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
  global $DATE_FORMAT, $TIMEZONE;

  if ( strlen ( $indate ) == 0 ) {
    $indate = date ( "Ymd" );
  }
  $newdate = $indate;
  if ( $server_time != "" && $server_time >= 0 ) {
    $tz_offset = get_tz_offset ( $TIMEZONE, '', $indate );
    $y = substr ( $indate, 0, 4 );
    $m = substr ( $indate, 4, 2 );
    $d = substr ( $indate, 6, 2 );
    if ( $server_time + $tz_offset[0] * 10000 > 240000 ) {
       $newdate = date ( "Ymd", mktime ( 0, 0, 0, $m, $d + 1, $y ) );
    } else if ( $server_time + $tz_offset[0] * 10000 < 0 ) {
       $newdate = date ( "Ymd", mktime ( 0, 0, 0, $m, $d - 1, $y ) );
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
  $date = mktime ( 0, 0, 0, $m, $d, $y );
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
 
  $dateu = mktime ( 0, 0, 0, $month, $day, $year );

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
      $rep[$cur_rep]->getTime() < $ev[$i]->getTime() ) {
      if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
        print_entry_timebar ( $rep[$cur_rep], $date );
        $cnt++;
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      print_entry_timebar ( $ev[$i], $date );
      $cnt++;
    }
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
      print_entry_timebar ( $rep[$cur_rep], $date );
      $cnt++;
    }
    $cur_rep++;
  }
  if ( $cnt == 0 )
    echo "&nbsp;"; // so the table cell has at least something
}

/**
 * Prints the HTML for an event with a timebar.
 *
 * @param Event  $event The event
 * @param string $date  Date for which we're printing in YYYYMMDD format
 *
 * @staticvar int Used to ensure all event popups have a unique id
 */
function print_entry_timebar ( $event, $date ) {
  global $eventinfo, $login, $user, $PHP_SELF, $prefarray;
  static $key = 0;
  $insidespan = false;
  global $layers, $TIMEZONE;

  // Adjust for TimeZone
  $tz_offset = get_tz_offset ( $TIMEZONE, '', $date );
 $time = $event->getTime();
  $hours = substr ( $time , 0, 2 );
  $mins = substr ( $time , 2, 2 );
//  $hours += (int) $tz_offset[0];
//  $mins  += ( ( $tz_offset[0] - (int) $tz_offset[0] ) * 60 );
  $time = $hours . $mins . "00"; 
  $time = get_time_add_tz ( $time, $tz_offset[0] );

  // compute time offsets in % of total table width
  $day_start=$prefarray["WORK_DAY_START_HOUR"] * 60;
  if ( $day_start == 0 ) $day_start = 1*60;
  $day_end=$prefarray["WORK_DAY_END_HOUR"] * 60;
  if ( $day_end == 0 ) $day_end = 23*60;
  if ( $day_end <= $day_start ) $day_end = $day_start + 60; //avoid exceptions

  if ($event->getTime() >= 0) {
  $bar_units= 100/(($day_end - $day_start)/60) ; // Percentage each hour occupies
  $ev_start = round((floor(($time/10000) - ($day_start/60)) + (($time/100)%100)/60) * $bar_units);
  }else{
    $ev_start= 0;
  }
  if ($ev_start < 0) $ev_start = 0;
  if ( $event->isAllDay() ) {
  // All day event
   $ev_start = 0;
   $ev_duration = 100;
  } else  if ($event->getDuration() > 0) {
    $ev_duration = round(100 * $event->getDuration() / ($day_end - $day_start)) ;
    if ($ev_start + $ev_duration > 100 ) {
      $ev_duration = 100 - $ev_start;
    }
  } else {
    if ($event->getTime() >= 0) {
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

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $event->getStatus() == "W" ) $class = "unapprovedentry";
  }
  // if we are looking at a view, then always use "entry"
  if ( strstr ( $PHP_SELF, "view_m.php" ) ||
    strstr ( $PHP_SELF, "view_w.php" ) ||
    strstr ( $PHP_SELF, "view_v.php" ) ||
    strstr ( $PHP_SELF, "view_t.php" ) )
    $class = "entry";

  if ( $event->getPriority() == 3 ) echo "<strong>";

  if ( $event->getExtForID() != '' ) {
    $id = $event->getExtForID();
    $name = $event->getName() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $id = $event->getID();
    $name = $event->getName();
  }

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";
  $key++;

  echo "<a class=\"$class\" id=\"$linkid\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $user ) > 0 )
    echo "&amp;user=" . $user;
  echo "\">";

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event->getLogin() ) {
            $insidespan = true;
            echo("<span style=\"color:" . $layer['cal_color'] . ";\">");
        }
    }
  }

  echo "[" . $event->getLogin() . "]&nbsp;";
  $timestr = "";
  if ( $event->isAllDay() ) {
    $timestr = translate("All day event");
  } else if ( $event->getTime() >= 0 ) {
    $timestr = display_time ( $event->getDatetime() );
    if ( $event->getDuration() > 0 ) {
      $timestr .= " - " . display_time ( $event->getEndDateTime() ) . " " .  $tz_offset[1];
    }
  }

  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) )
    echo "(" . translate("Private") . ")";
  else
  if ( $login != $event->getLogin() && $event->getAccess() == 'R' && strlen ( $event->getLogin() ) )
    echo "(" . translate("Private") . ")";
  else
  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) )
  {
    echo htmlspecialchars ( $name );
    if ( $insidespan ) { echo ("</span>"); } //end color span
  }
  else
    echo htmlspecialchars ( $name );
  echo "</a>";
  if ( $event->getPriority() == 3 ) echo "</strong>"; //end font-weight span
  echo "</td>\n";
  if ( $pos < 2 ) {
    if ( $pos < 1 ) {
      echo "<td style=\"width:n%;\"><table  class=\"entrybar\">\n<tr>\n<td class=\"entry\">&nbsp;</td>\n";
    }
    echo "</tr>\n</table></td>\n";
    echo ($ev_padding > 0 ? "<td style=\"text-align:left; width:$ev_padding%;\">&nbsp;</td>\n" : "" );
  }
  echo "</tr>\n</table>\n";
  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) )
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  else
  if ( $login != $event->getLogin() && $event->getAccess() == 'R' && strlen ( $event->getLogin() ) )
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      translate("This event is confidential"), "" );
  else
    $eventinfo .= build_event_popup ( $popupid, $event->getLogin(),
      $event->getDescription(), $timestr, site_extras_for_popup ( $id ) );
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
    $offset = (100/($end_hour - $start_hour)/2);
  //  if ( $offset < 3 ) $offset = 0;
    echo "\n<!-- TIMEBAR -->\n<table class=\"timebar\">\n<tr><td style=\"width:$offset%;\">&nbsp;</td>\n";
   for ($i = $start_hour+1; $i < $end_hour; $i++) {
    $prev_offset = $offset;
    $offset = round(100/($end_hour - $start_hour)*($i - $start_hour + .5));
    $width = $offset - $prev_offset;
    if ( $i > 10 ) $width += .1;
    echo "<td style=\"width:$width%;text-align:center;\">$i</td>\n";
   }
   $width = 100 - ( $offset * 2 );
   echo "<td width=\"$width%\">&nbsp;</td>\n";
   echo "</tr></tablen";
 
   // print yardstick
  echo "\n<!-- YARDSTICK -->\n<table class=\"yardstick\">\n<tr>\n";
  $offset = 0;
  for ($i = $start_hour; $i < $end_hour; $i++) {
    $prev_offset = $offset;

    $width = $offset - $prev_offset;
    echo "<td style=\"width:$width%;\">&nbsp;</td>\n";
    $offset = round(100/($end_hour - $start_hour)*($i - $start_hour));
   }
   echo "</tr>\n</table>\n<!-- /YARDSTICK -->\n";
 }


/**
 * Determine if the specified user is a participant in the event.
 * User must have status 'A' or 'W'.
 *
 * @param int $id event id
 * @param string $user user login
 */
function user_is_participant ( $id, $user )
{
  $ret = false;

  $sql = "SELECT COUNT(cal_id) FROM webcal_entry_user " .
    "WHERE cal_id = $id AND cal_login = '$user' AND " .
    "cal_status IN ('A','W')";
  $res = dbi_query ( $sql );
  if ( ! $res )
    die_miserable_death ( translate ( "Database error") . ": " .
      dbi_error () );

  if ( $row = dbi_fetch_row ( $res ) )
    $ret = ( $row[0] > 0 );

  dbi_free_result ( $res );

  return $ret;
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
 * - <var>cal_is_public</var>
 */
function get_nonuser_cals ($user = '') {
  $count = 0;
  $ret = array ();
  $sql = "SELECT cal_login, cal_lastname, cal_firstname, " .
    "cal_admin, cal_is_public FROM webcal_nonuser_cals ";
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
        "cal_is_public" => $row[4],
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
    "cal_admin, cal_is_public FROM " .
    "webcal_nonuser_cals WHERE cal_login = '$login'" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( strlen ( $row[1] ) || strlen ( $row[2] ) )
        $fullname = "$row[2] $row[1]";
      else
        $fullname = $row[0];

        $GLOBALS[$prefix . "login"] = $row[0];
        $GLOBALS[$prefix . "firstname"] = $row[2];
        $GLOBALS[$prefix . "lastname"] = $row[1];
        $GLOBALS[$prefix . "fullname"] = $fullname;
        $GLOBALS[$prefix . "admin"] = $row[3];
        $GLOBALS[$prefix . "is_public"] = $row[4];
        $GLOBALS[$prefix . "is_admin"] = false;
        $GLOBALS[$prefix . "is_nonuser"] = true;
        // We need the email address for the admin
        user_load_variables ( $row[3], 'nuloadtmp_' );
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
 * Determines what the day is  and sets it globally.
 *
 * The following global variables will be set:
 * - <var>$thisyear</var>
 * - <var>$thismonth</var>
 * - <var>$thisday</var>
 * - <var>$thisdate</var>
 * - <var>$today</var>
 * - <var>$tz_offset</var>
 *
 * @param string $date The date in YYYYMMDD format
 */
function set_today($date) {
  global $thisyear, $thisday, $thismonth, $thisdate, $today;
  global $month, $day, $year, $thisday, $TIMEZONE, $tz_offset;

  $today = time() ;
  //Get  Timezone info used to highlight today
  $tz_offset = get_tz_offset ( $TIMEZONE, $today );
  $today_offset = $tz_offset[0] * 3600;
  $today += $today_offset;

  if ( ! empty ( $date ) ) {
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
  global $WORK_DAY_START_HOUR, $WORK_DAY_END_HOUR, $TIMEZONE,$ignore_offset;

  $tz_offset = get_tz_offset ( $TIMEZONE, '', $date );

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
      if ($E->getTime() == 0) {
        $time = $first_hour."0000";
        $duration = 60 * ( $last_hour - $first_hour );
      } else {
        $time = sprintf ( "%06d", $E->getTime());
        $duration = $E->getDuration();
      }

      $hour = substr($time, 0, 2 );
      $mins = substr($time, 2, 2 );
       
      // Timezone Offset
      if ( ! $ignore_offset ) {
        $hour += (int) $tz_offset[0];
        $mins  += ( ( $tz_offset[0] - (int) $tz_offset[0] ) * 60 );
      }
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
      $bars = $duration / $increment;

      // never replace 'A' with 'W'
      for ($q = 0; $bars > $q; $q++) {
        $slot = sprintf ("%02.2f",$slot);
        if (strlen($slot) == 4) $slot = '0'.$slot; // add leading zeros
        $slot = $slot.''; // convert to a string
        if ( empty ( $master['_all_'][$slot] ) ||
          $master['_all_'][$slot]['stat'] != 'A') {
          $master['_all_'][$slot]['stat'] = $E->getStatus();
        }
        if ( empty ( $master[$participants[$i]][$slot] ) ||
          $master[$participants[$i]][$slot]['stat'] != 'A' ) {
          $master[$participants[$i]][$slot]['stat'] = $E->getStatus();
          $master[$participants[$i]][$slot]['ID'] = $E->getID();
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

/**
 * Extract the names of all site_extras
 *
 * @return array Array of site_extras names
 */
function get_site_extras_names () {
  global $site_extras;

  $ret = array();

  foreach ( $site_extras as $extra ) {
    $ret[] = $extra[0];
  }

  return $ret;
}

/**
 * Return the timezone rules for a given zone rule
 * Called only from function get_tz_time()
 *
 * @param string $zone_rule   
 * @param int $timestamp  UNIX timestamp of requested rule
 *
 * $global array $days_of_week Sun => 0...Sat => 6
 * @return array   
 *   dst_rules[0]['rule_date']   = first time change timestamp
 *   dst_rules[0]['rule_save']   = first time savings in seconds
 *   dst_rules[0]['rule_letter'] = first letter to apply to TZ abbreviation
 *   dst_rules[1]['rule_date']   = second time change timestamp
 *   dst_rules[1]['rule_save']   = second time savings in seconds
 *   dst_rules[1]['rule_letter'] = second letter to apply to TZ abbreviation
 *   dst_rules['lastyear']       = last year time savings in seconds
 *   dst_rules['lastletter']     - last year letter to apply to TZ abbreviation 
 */
function get_rules ( $zone_rule, $timestamp  ) {
 global $days_of_week;

 $year = date ("Y", $timestamp );

 $sql = "SELECT rule_from, rule_to, rule_in, rule_on, rule_at, rule_save, rule_letter, rule_at_suffix  " . 
   "FROM webcal_tz_rules WHERE rule_name  = '" . $zone_rule  ."'"  . 
   " AND rule_from <= $year  AND rule_to >= $year  ORDER BY rule_in";
  
  $res = dbi_query ( $sql );

  $dst_rules = array();
  $i = 0;
  if ( $res ) {

    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( substr ( $row[3], 0, 4 ) == "last" ) {
          $lastday = date ( "w", mktime ( 0, 0, 0, $row[2] + 1 , 0, $year ) );
          $offset = -( ( $lastday +7 - $days_of_week[substr($row[3], 4, 3)]) % 7);
          $changeday = mktime ( 0, 0, 0, $row[2] + 1, $offset, $year ) + $row[4];
      } else if ( substr ( $row[3], 3, 2 ) == "<="  OR substr ( $row[3], 3, 2 ) == ">=") {
          $rule_day = substr( $row[3], 5, strlen( $row[3]) -5);
          $givenday = date ( "w", mktime ( 0, 0, 0, $row[2] , $rule_day, $year ) );
          if ( substr ( $row[3], 3, 2) == "<=" ) {
            $offset = -( ( $givenday  + 7  - $days_of_week[ substr( $row[3], 0, 3)]) % 7);
          } else {
            $offset = ( ( $days_of_week[ substr( $row[3], 0, 3)] + 7 - $givenday   ) % 7);
          }
          $changeday = mktime (  0, 0, 0, $row[2] , $rule_day + $offset, $year ) + $row[4];
      } else {
        $changeday = mktime (  0, 0, 0, $row[2] , $row[3], $year ) + $row[4];
      }
      
      $dst_rules[$i] = array (
       "rule_date" => $changeday,
       "rule_save" => $row[5],
       "rule_letter" => $row[6]
      );
      $i++;
    }
    dbi_free_result ( $res );
  }
  // Need data from previous year in case requested date is prior to 
  // first change date in the current year
  if ( isset (  $dst_rules[0] ) && $timestamp < $dst_rules[0]['rule_date'] ) {
    $year = $year -1 ; 
    //We may get more than one row back. We want the first row
    $sql = "SELECT rule_save, rule_letter  " . 
      "FROM webcal_tz_rules WHERE rule_name  = '" . $zone_rule  ."'"  . 
      " AND rule_to >= $year ORDER BY rule_in DESC";
    $res = dbi_query ( $sql );
    $i = 0;
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      
      $dst_rules['lastyear'] = $row[0];
      $dst_rules['lastletter'] = ( isset ( $row[1] )? $row[1]: "");
      dbi_free_result ( $res );
    }
  }
  return $dst_rules;
}

/**
 * Return UNIX timestamp adjusted for timezone and DST
 *
 * @param timestamp  $timestamp   UNIX format
 * @param string $tz_name  name of requested Time Zone
 *  based on Olsen TZ data
 * @param bool is_gmt 1 if input time is GMT value 
 * @param bool use_dst 1 if DST calculations required
 *
 * @return array
 *  dst_results['name']      = abbreviated name of TZ
 *  dst_results['timestamp'] = UNIX timestamp of converted time/date
 */ 
function get_tz_time ( $timestamp, $tz_name, $is_gmt = 1, $use_dst = 1 ) {

  $sql = "SELECT  zone_rules, zone_gmtoff, zone_format " . 
    " FROM webcal_tz_zones WHERE zone_name  = '" . trim( $tz_name ) . "' " .
    " AND zone_from <= $timestamp AND zone_until >= $timestamp";
  $res = dbi_query (  $sql );
  $dst_rules = array ();
  $dst_results = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
     //Assign default value for TZ abbrev.
     $dst_results['name'] = str_replace ( "%s", "", $row[2] );
     // adjust by gmtoff value
     if ( $is_gmt == true ) {
       $dst_results['timestamp'] = $timestamp + $row[1];
     } else {
       $dst_results['timestamp'] = $timestamp - $row[1];
     }
      if ( ! empty ($row[0] )  && $use_dst == 1 ) { // Zone rules apply
        $dst_rules = get_rules ( $row[0], $timestamp );
        if ( count ( $dst_rules ) >= 2) {
            if ( $timestamp < $dst_rules[0]["rule_date"] ) {
              $dst_results['name'] = str_replace ( "%s", $dst_rules['lastletter'], $row[2] );
     if ( $is_gmt == true ) {  
                $dst_results['timestamp'] = $dst_results['timestamp'] + $dst_rules['lastyear'];
     } else {
       $dst_results['timestamp'] = $dst_results['timestamp'] - $dst_rules['lastyear'];
     }
     dbi_free_result ( $res );
     return $dst_results;
   } else if ( $timestamp >= $dst_rules[0]["rule_date"] && $timestamp < $dst_rules[1]["rule_date"] ) {
     $dst_results['name'] = str_replace ( "%s", $dst_rules[0]['rule_letter'], $row[2] );
     if ( $is_gmt == true ) {  
                $dst_results['timestamp'] = $dst_results['timestamp'] + $dst_rules[0]['rule_save'];
     } else {
                $dst_results['timestamp'] = $dst_results['timestamp'] - $dst_rules[0]['rule_save'];     
     }
     dbi_free_result ( $res );
     return $dst_results;
   } else {
     $dst_results['name'] = str_replace ( "%s", $dst_rules[1]['rule_letter'], $row[2] );  
     if ( $is_gmt == true ) {  
                $dst_results['timestamp'] = $dst_results['timestamp'] + $dst_rules[1]['rule_save'];
     } else {
                $dst_results['timestamp'] = $dst_results['timestamp'] - $dst_rules[1]['rule_save'];     
     }
     dbi_free_result ( $res );
     return $dst_results;
   }
        }
      }
    }
    dbi_free_result ( $res );
    //No Rules requested or returned
    return $dst_results;
  }
}

/*
 * Return cal_time type value adjusted by GMT offset
 *
 * @param string $time HHMMSS format
 * @param float $tz_offset GMT offset to be applied to $time
 *
 * @return string $ret_time TZ adjusted time HHMMSS
*/
function get_time_add_tz ( $time, $tz_offset ) {
  
  if ( $time > 0 ) {
      $hour = (int) ( $time / 10000 );
      $min = abs ( ( $time / 100 ) % 100 );
  } else {
   $hour = $min = 0;
  }
  $min_offset = ($tz_offset - floor ( $tz_offset )) * 60;
  if ( $tz_offset < 0 ) $min_offset = - $min_offset;
  $ret_time = date ( "His", mktime ( $hour + (int) $tz_offset , 
    $min + $min_offset , 0 ) );
    
  return $ret_time;
}

/*
 * Return cal_date+cal_time type value adjusted by GMT offset
 *
 * @param string $date YYYYMMDD format
 * @param string $time HHMMSS format
 * @param float $tz_offset GMT offset to be applied to $time
 *
 * @return string $ret_datetime TZ adjusted time YYYYMMDDHHMMSS
*/
function get_datetime_add_tz ( $date, $time, $tz_offset ) {
 
  $sy = substr ( $date, 0, 4 );
  $sm = substr ( $date, 4, 2 );
  $sd = substr ( $date, 6, 2 ); 
  if ( $time > 0 ) {
      $hour = (int) ( $time / 10000 );
      $min = abs ( ( $time / 100 ) % 100 );
  } else {
   $hour = $min = 0;
  }
  $min_offset = ($tz_offset - floor ( $tz_offset )) * 60;
  if ( $tz_offset < 0 ) $min_offset = - $min_offset;
  $ret_datetime = date ( "YmdHis", mktime ( $hour + (int) $tz_offset , 
    $min + $min_offset , 0, $sm, $sd, $sy ) );
    
  return $ret_datetime;
}

/*
 * Return the GMT offset for the given day and timezone
 *
 * @param string  $tz  name of timezone requested
 * @param timestamp  $timestamp   UNIX format
 * @param string  $dateYmd   Format YYYYMMDD Alternative to timestamp
 *
 * @staticvar array $tz_array Used to avoid duplicate lookups
 *
 * @global string $SERVER_TIMEZONE Server's timezone as set by admin
 *
 * @return array $tz_data
 *   $tz_data[0]    =  GMT offset in hours ( can be a float )
 *   $tz_data[1]    =  Name of this time ( i.e. EST, EDT, GMT )
 *
*/
function get_tz_offset ( $tz, $timestamp = '', $dateYmd = '' ) {
  global $SERVER_TIMEZONE;
  static $tz_array = array();
  
  $tz = ( empty ( $tz )? $SERVER_TIMEZONE : $tz );
  if ( empty ( $timestamp ) && ! empty ( $dateYmd ) ){
    //May need to expand dateYmd to dateYmdHis for accuracy
   // echo $dateYmd;
    $sy = substr ( $dateYmd, 0, 4 );
    $sm = substr ( $dateYmd, 4, 2 );
    $sd = substr ( $dateYmd, 6, 2 );
    $timestamp = mktime ( 0, 0, 0, $sm, $sd, $sy );
  }

  //Check if this lookup has already been done
  if ( ! empty ( $tz_array[$tz][$timestamp] ) ){
      $tz_data[0] = $tz_array[$tz][$timestamp];
      $tz_data[1] = $tz_array[$tz]['name'];
      
      return $tz_data;
  }

  $temp_time = array(); 
  $tz_data = array(); 
  $temp_time =  get_tz_time ( $timestamp, $tz, false, true ) ;
  $tz_array[$tz][$timestamp] = $tz_data[0] = ( $timestamp - $temp_time['timestamp'] ) / 3600 ;
  $tz_array[$tz]['name'] = $tz_data[1] = $temp_time['name']; 
  return $tz_data;
}

/*
 * Prints Timezone select for use on forms
 *
 * @param string  $prefix   Prefix for select control's name
 * @param string  $tz  Current timezone of logged in user
 *
 * @return string  $ret
 *    html for select control
*/
function print_timezone_select_html ( $prefix, $tz ) {
global $TZ_COMPLETE_LIST;
  
 if ( ! empty ( $TZ_COMPLETE_LIST ) && $TZ_COMPLETE_LIST == "Y" ) {
    $res = dbi_query ( "SELECT  DISTINCT zone_name, zone_country " .
                       "FROM webcal_tz_zones ORDER BY zone_country" );
  } else {
   $res = dbi_query ( "SELECT  tz_list_name, tz_list_text " .
                       "FROM webcal_tz_list ORDER BY tz_list_id" );
 }
  
   if ( $res ) {
    $ret =  "<select name=\"" . $prefix . "TIMEZONE\" id=\"" . $prefix . "TIMEZONE\">\n";
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $TZ_COMPLETE_LIST ) && $TZ_COMPLETE_LIST == "Y" ) {
     if  ( strpos ( $row[0], "/", 1) ){
          $tz_label = substr ( $row[0], strpos ( $row[0], "/", 1) +1);
          $tz_label = $row[1] . " - " . $tz_label;
        } else {
          $tz_label = $row[0];
        }
   } else { // We're using the short list
        $tz_label = $row[1];   
   } 
      $ret .= "<option value=\"$row[0]\"" . ( $row[0] == $tz ? " selected=\"selected\"" : "" ) .  "\">" . $tz_label . "</option>\n";
    }
    $ret .= "</select><br />\n";
    dbi_free_result ( $res );
  }
  return $ret;
}

/*
* Checks to see if user's IP in in the IP Domain
* specified by the /icludes/blacklist.php file
*
* @return bool <b>Is user's IP in required domain?</b>
*/
function validate_domain ( ) {

  $ip_authorized = false;
  $deny_found = array();
  $deny_true = false;
  $allow_found = array();
  $allow_true = false;
  $rmt_ip = explode( ".",  $_SERVER['REMOTE_ADDR'] );
  $fd = @fopen ( 'includes/blacklist.php', "rb", false );
  if ( ! empty ( $fd ) ) {
    // We don't use fgets() since it seems to have problems with Mac-formatted
    // text files.  Instead, we read in the entire file, then split the lines
    // manually.
    $data = '';
    while ( ! feof ( $fd ) ) {
      $data .= fgets ( $fd, 4096 );
    }
    fclose ( $fd );

    // Replace any combination of carriage return (\r) and new line (\n)
    // with a single new line.
    $data = preg_replace ( "/[\r\n]+/", "\n", $data );

    // Split the data into lines.
    $blacklistLines = explode ( "\n", $data );

    for ( $n = 0; $n < count ( $blacklistLines ); $n++ ) {
      $buffer = $blacklistLines[$n];
      $buffer = trim ( $buffer, "\r\n " );
      if ( preg_match ( "/^#/", $buffer ) )
        continue; 
      if ( preg_match ( "/(\S+):\s*(\S+):\s*(\S+)/", $buffer, $matches ) ) {
        $permission = $matches[1];
        $blacklist_ip = explode( ".",  $matches[2] );
        $blacklist_nm = explode( ".",  $matches[3] );
        if ( $permission == "deny" ) {
          for ( $i = 0; $i < 4; $i++ ) {
            // Do bitwise AND on IP and Netmask
            if ( (abs($rmt_ip[$i]) & abs($blacklist_nm[$i])) == 
              (abs($blacklist_ip[$i]) & abs($blacklist_nm[$i])) ) {
              $deny_found[$i] = 1;          
            } else {
              $deny_found[$i] = 0;      
            }    
          }
          //This value will be true if rmt_ip is any deny network
          // Once set, it can not be reset be other deny statements 
          if ( ! array_search ( 0, $deny_found ) ) {
            $deny_true = true;   
          } 
        } else if ( $permission == "allow" ) {
          for ( $i = 0; $i < 4; $i++ ) {
            // Do bitwise AND on IP and Netmask
            if ( (abs($rmt_ip[$i]) & abs($blacklist_nm[$i])) == 
              (abs($blacklist_ip[$i]) & abs($blacklist_nm[$i])) ) {
              $allow_found[$i] = 1;           
            } else {
              $allow_found[$i] = 0;     
            }    
          }
          //This value will be true if rmt_ip is any allow network
          // Once set, it can not be reset be other allow statements 
          if ( ! array_search ( 0, $allow_found ) ) {
            $allow_true = true;    
          }
        }
      }
    } //end for loop
    $ip_authorized = ( $deny_true == true && $allow_true == false? false : true ); 
  } // if fd not empty
  return $ip_authorized;
}


/**
 * Returns a custom header, stylesheet or tailer.
 * The data will be loaded from the webcal_user_template table.
 * If the global variable $allow_external_header is set to 'Y', then
 * we load an external file using include.
 * This can have serious security issues since a
 * malicous user could open up /etc/passwd.
 *
 * @param string  $login Current user login
 * @param string  $type  type of template ('H' = header,
 *    'S' = stylesheet, 'T' = trailer)
 */
function load_template ( $login, $type )
{
  global $allow_user_header;
  $found = false;
  $ret = '';

  // First, check for a user-specific template
  if ( ! empty ( $allow_user_header ) && $allow_user_header == 'Y' ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_user_template " .
      "WHERE cal_type = '$type' and cal_login = '$login'" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        $ret = $row[0];
        $found = true;
      }
      dbi_free_result ( $res );
    }
  }

  // If no user-specific template, check for the system template
  if ( ! $found ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_user_template " .
      "WHERE cal_type = '$type' and cal_login = '__system__'" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        $ret = $row[0];
        $found = true;
      }
      dbi_free_result ( $res );
    }
  }

  // If still not found, the check the old location (WebCalendar 1.0 and
  // before)
  if ( ! $found ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_report_template " .
      "WHERE cal_template_type = '$type' and cal_report_id = 0" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        echo $row[0];
        $found = true;
      }
      dbi_free_result ( $res );
    }
  }

  if ( $found ) {
    if ( ! empty ( $GLOBALS['allow_external_header'] ) &&
      $GLOBALS['allow_external_header'] == 'Y' ) {
      if ( file_exists ( $ret ) ) {
        ob_start ();
        include "$ret";
        $ret = ob_get_contents ();
        ob_end_clean ();
      }
    }
  }
  
  return $ret;
}


function error_check ( $nextURL ) {
  if ( ! empty ($error) ) {
    print_header( '', '', '', true );
    echo "<h2>" . translate("Error") . "</h2>";
    echo "<blockquote>";
    echo $error;
    //if ( $sql != "" )
      //  echo \"<br /><br /><strong>SQL:</strong> $sql\";
    echo "</blockquote>\n</body></html>";
  } else if ( empty ($error) ) {
    print "<html><head></head><body onload=\"alert('" . translate("Changes successfully saved") . "'); window.parent.location.href='$nextURL';\"></body></html>";
  }
}
?>
