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
    if ( empty ( $noSet[$key] ) ) {
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


function getPostValue ( $name ) {
  if ( ! empty ( $_POST[$name] ) )
    return $_POST[$name];
  if ( ! isset ( $HTTP_POST_VARS ) )
    return null;
  if ( ! isset ( $HTTP_POST_VARS[$name] ) )
    return null;
  return ( $HTTP_POST_VARS[$name] );
}

function getGetValue ( $name ) {
  if ( ! empty ( $_GET[$name] ) )
    return $_GET[$name];
  if ( ! isset ( $HTTP_GET_VARS ) )
    return null;
  if ( ! isset ( $HTTP_GET_VARS[$name] ) )
    return null;
  return ( $HTTP_GET_VARS[$name] );
}

// Get value from HTTP GET or POST
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
      echo "Fatal Error: Invalid data format for $name\n"; exit;
    }
    // ignore value
    return "";
  }
  return $val;
}


// Get an integer value
function getIntValue ( $name, $fatal=false ) {
  $val = getValue ( $name, "-?[0-9]+", $fatal );
  return $val;
}


// Load default system settings (which can be updated via admin.php)
// System settings are stored in webcal_config.
// In addition to WebCalendar settings, plugin settings are also stored.
// The convention for plugin settings is to prefix all settings with
// the short name of the plugin.  For example, for a plugin
// called "Package Tracking" and a short name of "pt", all settings
// would be prefixed with "pt." (as in "pt.somesetting").
// (Some can also be overridden with user settings.
// User settings are stored in webcal_pref.)
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
  if ( empty ( $GLOBALS["fontS"] ) ) {
    if ( $GLOBALS["LANGUAGE"] == "Japanese" )
      $GLOBALS["fontS"] = "Osaka, Arial, Helvetica, sans-serif";
    else
      $GLOBALS["fontS"] = "Arial, Helvetica, sans-serif";
  }
}

// Return a list of active plugins.
// Should be called after load_global_settings() and
// load_user_preferences().
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

// log a debug message
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  3, "/tmp/webcal-debug.log" );
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  2, "sockieman:2000" );
}

// send a redirect to the specified page
// MS IIS/PWS has a bug in which it does not allow us to send a cookie
// and a redirect in the same HTTP header.
// See the following for more info on the IIS bug:
//   http://www.faqts.com/knowledge_base/view.phtml/aid/9316/fid/4
function do_redirect ( $url ) {
  global $SERVER_SOFTWARE, $_SERVER, $c;
  if ( empty ( $SERVER_SOFTWARE ) )
    $SERVER_SOFTWARE = $_SERVER["SERVER_SOFTWARE"];
  //echo "SERVER_SOFTWARE = $SERVER_SOFTWARE <br />\n"; exit;
  if ( substr ( $SERVER_SOFTWARE, 0, 5 ) == "Micro" ) {
    echo "<?xml version=\"1.0\" encoding=\"utf8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">\n
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<title>Redirect</title>\n" .
      "<meta http-equiv=\"refresh\" content=\"0; url=$url\" />\n</head>\n<body>\n" .
      "Redirecting to ... <a href=\"" . $url . "\">here</a>.</body>\n</html>";
  } else {
    Header ( "Location: $url" );
    echo "<?xml version=\"1.0\" encoding=\"utf8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">\n
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n
<head>\n<title>Redirect</title>\n</head>\n<body>\n" .
      "Redirecting to ... <a href=\"" . $url . "\">here</a>.</body>\n</html>";
  }
  dbi_close ( $c );
  exit;
}

// send an HTTP login request
function send_http_login () {
  global $lang_file, $application_name;

  if ( strlen ( $lang_file ) ) {
    Header ( "WWW-Authenticate: Basic realm=\"" . translate("Title") . "\"");
    Header ( "HTTP/1.0 401 Unauthorized" );
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">\n
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n
<head>\n<title>Unauthorized</title>\n</head>\n<body>\n" .
      "<h2>" . translate("Title") . "</h2>\n" .
      translate("You are not authorized") .
      "\n</body>\n</html>";
  } else {
    Header ( "WWW-Authenticate: Basic realm=\"WebCalendar\"");
    Header ( "HTTP/1.0 401 Unauthorized" );
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">\n
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n
<head>\n<title>Unauthorized</title>\n</head>\n<body>\n" .
      "<h2>WebCalendar</h2>\n" .
      "You are not authorized" .
      "\n</body>\n</html>";
  }
  exit;
}

// Generate a cookie that saves the last calendar view (month, week, day)
// so we can return to this same page after a user edits/deletes/etc an
// event
function remember_this_view () {
  global $server_url, $REQUEST_URI;
  if ( empty ( $REQUEST_URI ) )
    $REQUEST_URI = $_SERVER["REQUEST_URI"];

  SetCookie ( "webcalendar_last_view", $REQUEST_URI );
}

// Get the last page stored using above function.
// Return empty string if we don't know.
function get_last_view () {
  global $HTTP_COOKIE_VARS;
  $val = '';

  if ( isset ( $HTTP_COOKIE_VARS["webcalendar_last_view"] ) )
    $val = $HTTP_COOKIE_VARS["webcalendar_last_view"];
  else if ( isset ( $_COOKIE["webcalendar_last_view"] ) )
    $val = $_COOKIE["webcalendar_last_view"];
  return $val;
}

// Send header stuff that tells the browser not to cache this page.
function send_no_cache_header () {
  header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
  header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s" ) . " GMT" );
  header ( "Cache-Control: no-store, no-cache, must-revalidate" );
  header ( "Cache-Control: post-check=0, pre-check=0", false );
  header ( "Pragma: no-cache" );
}

// Load the current user's preferences as global variables.
// Also load the list of views for this user (not really a preference,
// but this is a convenient place to put this...)
// Note: If the $allow_color_customization is set to 'N', then we ignore any
// color preferences.
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
  $is_assistant = user_is_assistant ( $login, $user );
  $has_boss = user_has_boss ( $login );
  $is_nonuser_admin = ($user) ? user_is_nonuser_admin ( $login, $user ) : false;
  if ( $is_nonuser_admin ) load_nonuser_preferences ($user);
}

// Get the list of external users for an event
// $use_mailto - when set to 1, email address will contain an href
//   link with a mailto URL.
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

// Add something to the activity log for an event
// $user - user doing this
// $user_cal - user who's calendar is affected
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

// Get a list of users.  We used to just call user_get_users() directly.
// Now, we use this instead.  If groups are enabled, this can restrict
// the list of users to only those users who are in the same group(s)
// as the user.
// We allow admin users to see all users because they can also edit
// someone else's events (so they may need access to users who are not
// in the same groups that they are in).
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
    $sql = "SELECT DISTINCT(webcal_group_user.cal_login) from webcal_group_user " .
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

// Get a preference setting for the specified user.  If no value is
// found in the db, then the system default setting will be returned.
// params:
//   $user - user login we are getting preference for
//   $setting - the name of the setting
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

// Load current user's layer info and stuff it into layer global variable.
function load_user_layers ($user="",$force=0) {
  global $login;
  global $layers;
  global $LAYERS_STATUS;

  if ( $user == "" )
    $user = $login;

  $layers = array ();

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

function site_extras_for_popup ( $id ) {
  global $site_extras_in_popup, $site_extras;
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

// Build the HTML for the event popup (but don't print it yet since we
// don't want this HTML to go inside the table for the month).
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
    $ret .= str_replace ( "&amp;amp;", "&amp;", $str );
  } else {
    $ret .= nl2br ( htmlspecialchars ( $description ) );
  }
  $ret .= "</dd>\n";
  if ( ! empty ( $site_extras ) )
    $ret .= $site_extras;
  $ret .= "</dl>\n";
  return $ret;
}

// Print out a date selection for use in a form.
// params:
//   $prefix - prefix to use in front of form element names
//   $date - currently selected date (in YYYYMMDD) format
function print_date_selection ( $prefix, $date ) {
  print date_selection_html ( $prefix, $date );
}

// Generate a date selection for use in a form and return in.
// params:
//   $prefix - prefix to use in front of form element names
//   $date - currently selected date (in YYYYMMDD) format
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

// Prints out a minicalendar for a month
// params:
//   $thismonth - number of the month to print
//   $thisyear - number of the year
//   $showyear - boolean whether to show the year in the calendar's title
//   $show_weeknums - boolean whether to show week numbers to the left of each row
//   $minical_id - id attribute for the minical table
//   $month_link - URL and query string for month link that should come before the date specification (i.e. month.php?  or  view_l.php?id=7&amp;)
//                 defaults to 'month.php?'
//
function display_small_month ( $thismonth, $thisyear, $showyear,
  $show_weeknums=false, $minical_id='', $month_link='month.php?' )
{
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved;
  global $DISPLAY_WEEKNUMBER;
  global $SCRIPT, $thisday; // Needed for day.php

  // TODO: Make day.php NOT be a special case

  if ( $user != $login && ! empty ( $user ) ) {
    $u_url = "&amp;user=$user";
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
    echo "<a title=\"" . translate("Previous") .
      "\" class=\"prev\" href=\"day.php?{$u_url}date=$month_ago$caturl\">";
    echo "<img src=\"leftarrowsmall.gif\" alt=\"" .
      translate("Previous") . "\" /></a>\n";
    echo "<a title=\"" . translate("Next") .
      "\" class=\"next\" href=\"day.php?{$u_url}date=$month_ahead$caturl\">";
    echo "<img src=\"rightarrowsmall.gif\" alt=\"" .
      translate("Next") . "\" /></a>\n";
    echo month_name ( $thismonth - 1 );
    if ( $showyear != '' ) {
      echo " $thisyear";
    }
    echo "</th></tr>\n";
  } else {
    //print the month name
    echo "<caption>";

    echo "<a href=\"{$month_link}year=$thisyear&amp;month=$thismonth$u_url\">";
    echo month_name ( $thismonth - 1 ) .
      ( $showyear ? " $thisyear" : "" ) .
      "</a></caption>\n";

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
      echo "<td class=\"weeknumber\">" .
        "<a href=\"week.php?$u_url&amp;date=" .
        date("Ymd", $i)."\">(" . week_number($i) . ")</a></td>\n";
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
        echo '<td';
        $wday = date ( 'w', $date );
        $class = '';

        if ( $wday == 0 || $wday == 6 ) {
          $class = 'weekend';
        }

        if ( $dateYmd == $thisyear . $thismonth . $thisday ) {
          if ( $class != '' ) {
            $class .= ' ';
          }
          $class .= 'selectedday';
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

        echo '>';
        echo "<a href=\"day.php?date=" . $dateYmd . $u_url . "\">";
        echo date ( "d", $date );
        echo "</a>";
        echo "</td>\n";
        } else {
          echo "<td class=\"empty\">&nbsp;</td>\n";
        }
      }                 // end for $j
      echo "</tr>\n";
    }                         // end for $i
  echo "</tbody>\n</table>\n";
}

// Print the HTML for one day's events in the month view.
// params:
//   $id - event id
//   $date - date (not used)
//   $time - time (in HHMMSS format)
//   $duration - event duration (in minutes)
//   $name - event name
//   $description - long description of event
//   $status - event status
//   $pri - event priority
//   $access - event access
//   $event_owner - user associated with this event
function print_entry ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner ) {
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

  if ( $pri == 3 ) echo "<span style=\"font-weight:bold;\">";
	$popupid = "eventinfo-$id-$key";
	$key++;
	echo "<a title=\"" . 
		translate("View this entry") . "\" class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
	if ( strlen ( $user ) > 0 )
		echo "&amp;user=" . $user;
	echo "\" onmouseover=\"window.status='" . translate("View this entry") .
		"'; show(event, '$popupid'); return true;\" onmouseout=\"window.status=''; hide('$popupid'); return true;\">";
	echo "<img src=\"circle.gif\" class=\"bullet\" alt=\"" . translate("View this entry") . "\" />";


  if ( $login != $event_owner && strlen ( $event_owner ) )
  {
    if ($layers) foreach ($layers as $layer)
    {
        if($layer['cal_layeruser'] == $event_owner)
        {
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
      if ( $duration == ( 24 * 60 ) ) {
        $timestr = translate("All day event");
      } else {
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
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) )
    echo "(" . translate("Private") . ")";

  else
  if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
    echo "(" . translate("Private") . ")";
  else
  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    echo htmlspecialchars ( $name );
//    echo ("</span>"); //end color span
  }

  else
    echo htmlspecialchars ( $name );

  echo "</a>";
  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
        if($layer['cal_layeruser'] == $event_owner) {
            echo "</span>\n";
        }
    }
  }

  if ( $pri == 3 ) echo "</span>\n"; //end font-weight span
//  echo "</span><br />\n"; //end font-size span
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

// Get any site-specific fields for an entry that are stored in the database.
// Return an array.
// params:
//   $eventid - unique event id
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

// Read all the events for a user for the specified range of dates.
// This is only called once per page request to improve performance.
// All the events get loaded into the array $events sorted by
// time of day (not date).
// params:
//   $user - username
//   $startdate - start date range, inclusive (in YYYYMMDD format)
//   $enddate - end date range, inclusive (in YYYYMMDD format)
//   $cat_id - category ID to filter on
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

// Get all the events for a specific date from the array of pre-loaded
// events (which was loaded all at once to improve performance).
// The returned events will be sorted by time of day.
// params:
//   $user - username
//   $date - date to get events for in YYYYMMDD format
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

// Read events visible to a user (including layers and possibly public access
// if enabled); return results
// in an array sorted by time of day.
// params:
//   $user - username
//   $want_repeated - true to get repeating events; false to get
//     non-repeating.
//   $date_filter - SQL phrase starting with AND, to be appended to
//     the WHERE clause.  May be empty string.
//   $cat_id - category ID to filter on.  May be empty.
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

  //echo "<span style=\"font-weight:bold;\">SQL:</span> $sql<br />\n";
  
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
        "cal_login" => $row[10],
	"cal_exceptions" => array()
        );
      if ( $want_repeated && ! empty ( $row[11] ) ) {
        $item['cal_type'] = empty ( $row[11] ) ? "" : $row[11];
        $item['cal_end'] = empty ( $row[12] ) ? "" : $row[12];
        $item['cal_frequency'] = empty ( $row[13] ) ? "" : $row[13];
        $item['cal_days'] = empty ( $row[14] ) ? "" : $row[14];
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
      }
      else {
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

// Read all the repeated events for a user.  This is only called once
// per page request to improve performance.  All the events get loaded
// into the array $repeated_events sorted by time of day (not date).
// params:
//   $user   - username
//   $cat_id - Category ID to filter on.  May be empty.
//   $date   - Cutoff date for repeating event endtimes. May be empty.
function read_repeated_events ( $user, $cat_id = '', $date = ''  ) {
  global $login;
  global $layers;

  $filter = ($date != '') ? "AND (webcal_entry_repeats.cal_end >= $date OR webcal_entry_repeats.cal_end IS NULL) " : '';
  return query_events ( $user, true, $filter, $cat_id );
}
//Returns all the dates a specific event will fall on accounting for
//the repeating.  Any event with no end will be assigned one.
//params:
//  $date - initial date in raw format
//  $rpt_type - repeating type as stored in the database
//  $end  - end date
//  $days - days events occurs on (for weekly)
//  $ex_dates - array of exception dates for this event in YYYYMMDD format
//  $freq - frequency of repetition
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
// Get all the repeating events for the specified data and return them
// in an array (which is sorted by time of day).
// params:
//   $user - username
//   $date - date to get events for in YYYYMMDD format
//   $get_unapproved - include unapproved events in results
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
//Returns a boolean stating whether or not the event passed
//in will fall on the date passed.
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

// Get the Sunday of the week that the specified date is in.
// (If the date specified is a Sunday, then that date is returned.)
function get_sunday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 3, 0, 0, $month, $day, $year ) );
  $newdate = mktime ( 3, 0, 0, $month, $day - $weekday, $year );
  return $newdate;
}

// Get the Monday of the week that the specified date is in.
// (If the date specified is a Monday, then that date is returned.)
function get_monday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 3, 0, 0, $month, $day, $year ) );
  if ( $weekday == 0 )
    return mktime ( 3, 0, 0, $month, $day - 6, $year );
  if ( $weekday == 1 )
    return mktime ( 3, 0, 0, $month, $day, $year );
  return mktime ( 3, 0, 0, $month, $day - ( $weekday - 1 ), $year );
}

// Returns week number for specified date
// depending from week numbering settings.
// params:
//   $date - date in UNIX time format
function week_number ( $date ) {
  $tmp = getdate($date);
  $iso    = gregorianToISO($tmp['mday'], $tmp['mon'], $tmp['year']);
  $parts  = explode('-',$iso);
  $week_number = intval($parts[1]);
  return sprintf("%02d",$week_number);
}

// This function is not yet used.  Some of the places that will call it
// have to be updated to also get the event owner so we know if the current
// user has access to edit and delete.
function icon_text ( $id, $can_edit, $can_delete ) {
  global $readonly, $is_admin;
  $ret = "<a title=\"" . translate("View this entry") . "\" href=\"view_entry.php?id=$id\">" .
    "<img src=\"view.gif\" alt=\"" . translate("View this entry") .
    "\" style=\"border-width:0px; width:10px; height:10px;\" />" .
    "</a>";
  if ( $can_edit && $readonly == "N" )
    $ret .= "<a title=\"" . translate("Edit entry") . "\" href=\"edit_entry.php?id=$id\">" .
      "<img src=\"edit.gif\" alt=\"" . translate("Edit entry") .
      "\" style=\"border-width:0px; width:10px; height:10px;\" />" .
      "</a>";
  if ( $can_delete && ( $readonly == "N" || $is_admin ) )
    $ret .= "<a title=\"" . translate("Delete entry") . "\" href=\"del_entry.php?id=$id\" " .
      "onclick=\"return confirm('" .
      translate("Are you sure you want to delete this entry?") .
      "\\n\\n" . translate("This will delete this entry for all users.") .
      "');\">" .
      "<img src=\"delete.gif\" alt=\"" . translate("Delete entry") .
      "\" style=\"border-width:0px; width:10px; height:10px;\" />" .
      "</a>";
  return $ret;
}

// DATE AND WEEK NUMBER
// Print all the calendar entries for the specified user for the
// specified date.  If we are displaying data from someone other than
// the logged in user, then check the access permission of the entry.
// params:
//   $date - date in YYYYMMDD format
//   $user - username
//   $is_ssi - is this being called from week_ssi.php?
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
  if ( ! $ssi && $can_add ) {
    print "<a title=\"" .
      translate("New Entry") . "\" href=\"edit_entry.php?";
    if ( strcmp ( $user, $GLOBALS["login"] ) )
      print "user=$user&amp;";
    print "date=$date\">" .
      "<img src=\"new.gif\" alt=\"" .
      translate("New Entry") . "\" class=\"new\" />" .
      "</a>";
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
        translate("Week") . " " . week_number ( $dateu ) . "\" href=\"week.php?date=$date";
      if ( strcmp ( $user, $GLOBALS["login"] ) )
        echo "&amp;user=$user";
       echo "\" class=\"weeknumber\">";
      echo "(" .
        translate("Week") . "&nbsp;" . week_number ( $dateu ) . ")</a>";
    }
    print "<br />";
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
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
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
        $ev[$i]['cal_access'], $ev[$i]['cal_login'] );
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
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
      $cnt++;
    }
    $cur_rep++;
  }
  if ( $cnt == 0 )
    echo "&nbsp;"; // so the table cell has at least something
}

// check to see if two events overlap
// time1 and time2 should be an integer like 235900
// duration1 and duration2 are integers in minutes
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

// Check for conflicts.
// Find overlaps between an array of dates and the other dates in the database.
// $date is an array of dates in Ymd format that is check for overlaps.
// the $duration, $hour, and $minute are integers that show the time of
// the event which is shared among the dates.
// $particpants are those whose calendars are to be checked.
// $login is the current user name.
// $id is the current calendar entry being checked if it has been stored before
// (this keeps overlaps from wrongly checking an event against itself.
// TODO: Update this to handle exceptions to repeating events
// 
// Appt limits: if enabled we will store each event in an array using
// the key $user-$date, so for testuser on 12/31/95
// we would use $evtcnt["testuser-19951231"]
//
// Return empty string for no conflicts or return the HTML of the
// conflicts when one or more are found.
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

// Convert a time format HHMMSS (like 131000 for 1PM) into number of
// minutes past midnight.
function time_to_minutes ( $time ) {
  $h = (int) ( $time / 10000 );
  $m = (int) ( $time / 100 ) % 100;
  $num = $h * 60 + $m;
  return $num;
}

// Calculate which row/slot this time represents.
// $time is input time in YYMMDD format
// $round_down indicates if we should change 1100 to 1059 so a
// 10AM-11AM appt just shows up in the 10AM slot, not the 11AM slot also.
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

// Generate the HTML for the add icon.
// date = date in YYYYMMDD format
// hour = hour of day (eg. 1,13,23)
function html_for_add_icon ( $date=0,$hour="", $minute="", $user="" ) {
  global $TZ_OFFSET;
  global $login;
  $u_url = '';

  if ( $user != $login )
    $u_url = "user=$user&amp;";
  if ( ! empty ( $hour ) )
    $hour += $TZ_OFFSET;
  return "<a title=\"" . translate("New Entry") . "\" href=\"edit_entry.php?" . $u_url .
    "date=$date" . ( $hour > 0 ? "&amp;hour=$hour" : "" ) .
    ( $minute > 0 ? "&amp;minute=$minute" : "" ) .
    ( empty ( $user ) ? "" :  "&amp;defusers=$user&amp;user=$user" ) .
    "\"><img src=\"new.gif\" class=\"new\" " .
    "alt=\"" . translate("New Entry") . "\" />" .
    "</a>";
}

// Generate the HTML for an event to be viewed in the week-at-glance.
// The HTML will be stored in an array ($hour_arr) indexed on the event's
// starting hour.
function html_for_event_week_at_a_glance ( $id, $date, $time,
  $name, $description, $status, $pri, $access, $duration, $event_owner ) {
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

  $hour_arr[$ind] .=
    "<a title=\"" .
    translate("View this entry") .
    "\" class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $GLOBALS["user"] ) > 0 )
    $hour_arr[$ind] .= "&amp;user=" . $GLOBALS["user"];
  $hour_arr[$ind] .= "\" onmouseover=\"window.status='" .
    translate("View this entry") .
    "'; show(event, '$popupid'); return true;\" onmouseout=\"hide('$popupid'); return true;\">";
  if ( $pri == 3 )
    $hour_arr[$ind] .= "<span style=\"font-weight:bold;\">";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event_owner ) {
        $hour_arr[$ind] .= "<span style=\"color:" .
          $layer['cal_color'] . ";\">";
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

  if ( $pri == 3 ) $hour_arr[$ind] .= "</span>"; //end font-weight span
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

// Generate the HTML for an event to be viewed in the day-at-glance.
// The HTML will be stored in an array ($hour_arr) indexed on the event's
// starting hour.
function html_for_event_day_at_a_glance ( $id, $date, $time,
  $name, $description, $status, $pri, $access, $duration, $event_owner ) {
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

// TODO: The following section has several nested spans that need to be extracted & then combined separate from the PHP code.
	$hour_arr[$ind] .=
		"<a title=\"" .
		translate("View this entry") .
		"\" class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
	if ( strlen ( $GLOBALS["user"] ) > 0 )
		$hour_arr[$ind] .= "&amp;user=" . $GLOBALS["user"];
	$hour_arr[$ind] .= "\" onmouseover=\"window.status='" .
		translate("View this entry") .
		"'; show(event, '$popupid'); return true;\" onmouseout=\"hide('$popupid'); return true;\">";
  if ( $pri == 3 ) $hour_arr[$ind] .= "<span style=\"font-weight:bold;\">";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
      if ( $layer['cal_layeruser'] == $event_owner) {
        $hour_arr[$ind] .= "<span style=\"color:" .
          $layer['cal_color'] . ";\">";
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
  if ( $pri == 3 ) $hour_arr[$ind] .= "</span>"; //end font-weight span

  $hour_arr[$ind] .= "</a>";
  if ( $GLOBALS["DISPLAY_DESC_PRINT_DAY"] == "Y" ) {
    $hour_arr[$ind] .= "\n<dl class=\"desc\">\n";
    $hour_arr[$ind] .= "<dt>Description:</dt>\n<dd>";
    $hour_arr[$ind] .= htmlspecialchars ( $description );
		$hour_arr[$ind] .= "</dd>\n</dl>\n";
  }

  $hour_arr[$ind] .= "<br />\n";
}

// Print all the calendar entries for the specified user for the
// specified date in day-at-a-glance format.
// If we are displaying data from someone other than
// the logged in user, then check the access permission of the entry.
// We output a two column format like:   time: event
// params:
//   $date - date in YYYYMMDD format
//   $user - username
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
          $rep[$cur_rep]['cal_login'] );
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
        $ev[$i]['cal_login'] );
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
        $rep[$cur_rep]['cal_login'] );
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
    echo "<tr><th class=\"empty\">&nbsp;</th>\n<td style=\"background-color:$TODAYCELLBG;\">$hour_arr[9999]</td></tr>\n";
  }
  $rowspan = 0;
  //echo "first_slot = $first_slot<br />\nlast_slot = $last_slot<br />\ninterval = $interval<br />\n";
  for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
    $time_h = (int) ( ( $i * $interval ) / 60 );
    $time_m = ( $i * $interval ) % 60;
    $time = display_time ( ( $time_h * 100 + $time_m ) * 100 );
    echo "<tr>\n<th class=\"row\">" .
      $time . "</th>\n";
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
      $color = $all_day ? $TODAYCELLBG : $CELLBG;
      if ( empty ( $hour_arr[$i] ) ) {
        echo "<td style=\"background-color:$color;\">";
        if ( $can_add )
          echo html_for_add_icon ( $date, $time_h, $time_m, $user );
        echo "&nbsp;</td></tr>\n";
      } else {
        if ( empty ( $rowspan_arr[$i] ) )
          $rowspan = '';
        else
          $rowspan = $rowspan_arr[$i];
        if ( $rowspan > 1 ) {
          echo "<td style=\"background-color:$TODAYCELLBG;\" rowspan=\"$rowspan\">";
          if ( $can_add )
            echo html_for_add_icon ( $date, $time_h, $time_m, $user );
          echo "$hour_arr[$i]</td></tr>\n";
        } else {
          echo "<td style=\"background-color:$TODAYCELLBG;\">";
          if ( $can_add )
            echo html_for_add_icon ( $date, $time_h, $time_m, $user );
          echo "$hour_arr[$i]</td></tr>\n";
        }
      }
    }
  }
}

// display a link to any unapproved events
// If the user is an admin user, also count up any public events.
// If the user is a nonuser admin, count up events on the nonuser calendar.
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

// Look for URLs in the given text, and make them into links.
// params:
//   $text - input text
function activate_urls ( $text ) {
  $str = eregi_replace ( "(http://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $text );
  $str = eregi_replace ( "(https://[^[:space:]$]+)",
    "<a href=\"\\1\">\\1</a>", $str );
  return $str;
}

// Display a time in either 12 or 24 hour format
// params:
//   $time - an interger like 235900
// The global variable $TZ_OFFSET to adjust the time.
// Note that this is somewhat of a kludge for timezone support.  If an
// event is set for 11PM server time and the user is 2 hours ahead, it
// will show up as 1AM, but the date will not be adjusted to the next day.
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

// Return the full month name
// params:
//   $m - month (0-11)
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

// Return the abbreviated month name
// params:
//   $m - month (0-11)
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

// Return the full weekday name
// params:
//   $w - weekday (0=Sunday,...,6=Saturday)
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

// Return the abbreviated weekday name
// params:
//   $w - weekday (0=Sun,...,6=Sat)
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

// convert a date from an int format "19991231" into
// "Friday, December 31, 1999", "Friday, 12-31-1999" or whatever format
// the user prefers.
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

// Define an array to use to jumble up the key: $offsets
// We define a unique key to scramble the cookie we generate.
// We use the remote server address as part of it, which should tie the
// cookie to the user's machine (or the proxy they connect through).
// We also use the server name so that cannot use their own server to
// generate a cookie for a different server.
/* cek - the following code is buggy...  */
if ( empty ( $REMOTE_ADDR ) )
  $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
if ( empty ( $REMOTE_PORT ) )
  $SERVER_PORT = $_SERVER['SERVER_PORT'];
if ( empty ( $SERVER_NAME ) )
  $SERVER_NAME = $_SERVER['SERVER_NAME'];
$unique_id = "";
$len1 = strlen ( $REMOTE_ADDR );
$len2 = strlen ( $SERVER_PORT );
$len3 = strlen ( $SERVER_NAME );
$offsets = array ();
for ( $i = 0; $i < $len1 || $i < $len2 || $i < $len3; $i++ ) {
  $offsets[$i] = 0;
  if ( $i < $len1 )
    $offsets[$i] += ord ( substr ( $REMOTE_ADDR, $i, 1 ) );
  if ( $i < $len2 )
    $offsets[$i] += ord ( substr ( $SERVER_PORT, $i, 1 ) );
  if ( $i < $len3 )
    $offsets[$i] += ord ( substr ( $SERVER_NAME, $i, 1 ) );
  $offsets[$i] %= 128;
}
/* debugging code...
for ( $i = 0; $i < count ( $offsets ); $i++ ) {
  echo "offset $i: $offsets[$i] <br />\n";
}
echo "REMOTE_ADDR = $REMOTE_ADDR<br />\nSERVER_PORT = $SERVER_PORT<br />\nSERVER_NAME = $SERVER_NAME<br />\n";
*/

// old code for offsets -- use this if we find bugs in
// code above.
//$offsets = array ( 24, 34, 12, 45, 88, 19, 33 );

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

// Extract a user's name from a session id
// This is a lame attempt at security.  Otherwise, users would be
// able to edit their cookies.txt file and set the username in plain
// text.
// $instr is a hex-encoded string. "Hello" would be "678ea786a5".
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
  return $orig;
}

// Take an input string and encoded it into a slightly encoded hexval
// that we can use as a session cookie.
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

// Load current user's category info and stuff it into category global variable.
//   $ex_global - Don't include global categories.
function load_user_categories ($ex_global = '') {
  global $login;
  global $categories, $category_owners;
  global $categories_enabled;

  $categories = array ();
  $category_owners = array ();

  if ( $categories_enabled == "Y" ) {
    $sql = "SELECT cat_id, cat_name, cat_owner FROM webcal_categories WHERE ";
    $sql .=  ($ex_global == '') ? " (cat_owner = '$login') OR  (cat_owner IS NULL) ORDER BY cat_owner, cat_name" : " cat_owner = '$login' ORDER BY cat_name";
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

// Build dropdown of categories
//   $form - the page to submit data to (without .php)
//   $date - YYYYMMDD
//   $ID - category that should be pre-selected
function print_category_menu ( $form, $date = '', $cat_id = '' ) {
  global $categories, $category_owners, $user, $login;

  echo "<form action=\"{$form}.php\" method=\"get\" name=\"SelectCategory\" class=\"categories\">\n";
  if ( ! empty($date) ) echo "<input type=\"hidden\" name=\"date\" value=\"$date\" />\n";
  if ( ! empty ( $user ) && $user != $login )
    echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  echo translate ('Category').": <select name=\"cat_id\" onchange=\"document.SelectCategory.submit()\">\n";
  echo "<option value=\"\"";
  if ( $cat_id == '' ) echo " selected=\"selected\"";
  echo ">" . translate("All") . "</option>\n";
  if ( is_array ( $categories ) ) {
    foreach ( $categories as $K => $V ){
      if ( empty ( $user ) || $user == $login ||
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

// Convert HTML entities in 8bit
// Only supported for PHP4 (not PHP3)
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
// Get a list of an assistant's boss
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

// Return true if $user is $boss assistant
function user_is_assistant ( $assistant, $boss ) {
  $ret = false;

  $res = dbi_query ( "SELECT * FROM webcal_asst " . 
     "WHERE cal_assistant = '$assistant' AND cal_boss = '$boss'" );
  if ( $res ) {
    if ( dbi_fetch_row ( $res ) )
      $ret = true;
    dbi_free_result ( $res );
  }
  return $ret;
}

// return true if assistant has boss
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

// Return false if boss don't want to be notified, true otherwise
function boss_must_be_notified ( $assistant, $boss ) {
  if (user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, "EMAIL_ASSISTANT_EVENTS" )=="Y" ? true : false );
  return true;
}

// Return false if boss don't want to approve events, true otherwise
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

//
// Print all the entries in a time bar format for the specified user
// for the
// specified date.  If we are displaying data from someone other than
// the logged in user, then check the access permission of the entry.
// params:
//   $date - date in YYYYMMDD format
//   $user - username
//   $is_ssi - is this being called from week_ssi.php?
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
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
        $cnt++;
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]['cal_status'] == 'A' ) {
      print_entry_timebar ( $ev[$i]['cal_id'],
        $date, $ev[$i]['cal_time'], $ev[$i]['cal_duration'],
        $ev[$i]['cal_name'], $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_login'] );
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
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
      $cnt++;
    }
    $cur_rep++;
  }
  if ( $cnt == 0 )
    echo "&nbsp;"; // so the table cell has at least something
}

// Print the HTML for an events with a timebar.
// params:
//   $id - event id
//   $date - date (not used)
//   $time - time (in HHMMSS format)
//   $duration - duration (in minutes)
//   $name - event name
//   $description - long description of event
//   $status - event status
//   $pri - event priority
//   $access - event access
//   $event_owner - user associated with this event
function print_entry_timebar ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner ) {
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
    $ev_start=$time/10000 * 60 + (($time/100)%100);
    $ev_start = round(100 * ($ev_start - $day_start) / ($day_end - $day_start));
  } else {
    $ev_start= 0;
  }
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
  if ($ev_duration > 20) {    $pos = 1; }
  elseif ($ev_padding > 20) { $pos = 2; }
  else                     { $pos = 0; }

  echo "\n<!-- begin entry bar -->\n<table class=\"entrycont\" style=\"width:100%; border-width:0px;\" cellpadding=\"0\" cellspacing=\"0\">\n";
  echo "<tr>\n";
  echo "<td style=\"text-align:right; width:$ev_start%;\">";
  if ( $pos > 0 ) {
    echo "&nbsp;</td>\n";
    echo "<td style=\"width:$ev_duration%;\">\n<table class=\"entrybar\" style=\"width:100%; border-width:0px; background-color:#000000;\" cellspacing=\"1\">\n<tr>\n<td style=\"text-align:center; background-color:#F5DEB3;\">";
    if ( $pos > 1 ) {
      echo "&nbsp;</td>\n</tr>\n</table></td>\n";
      echo "<td style=\"text-align:left; width:$ev_padding%;\">";
    }
  };

//TODO: The following section has several nested spans.  They need to be extracted & then combined separately from the PHP code.
//  echo "<span style=\"font-size:13px;\">";
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

  if ( $pri == 3 ) echo "<span style=\"font-weight:bold;\">";
  $popupid = "eventinfo-$id-$key";
  $key++;
  echo "<a class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $user ) > 0 )
    echo "&amp;user=" . $user;
  echo "\" onmouseover=\"window.status='" . translate("View this entry") .
    "'; show(event, '$popupid'); return true;\" onmouseout=\"hide('$popupid'); return true;\">";

  if ( $login != $event_owner && strlen ( $event_owner ) )
  {
    if ($layers) foreach ($layers as $layer)
    {
        if($layer['cal_layeruser'] == $event_owner)
        {
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
  if ( $pri == 3 ) echo "</span>"; //end font-weight span
//  echo "</span>"; //end font-size span
  echo "</td>\n";
  if ( $pos < 2 ) {
    if ( $pos < 1 ) {
      echo "<td style=\"width:$ev_duration%;\"><table style=\"width:100%; border-width:0px; background-color:#000000;\" cellpadding=\"0\" cellspacing=\"1\">\n<tr>\n<td style=\"text-align:center; background-color:#F5DEB3;\">&nbsp;</td>\n";
    }
    echo "</tr>\n</table></td>\n";
    echo "<td style=\"text-align:left; width:$ev_padding%;\">&nbsp;</td>\n";
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

function print_header_timebar($start_hour, $end_hour) {
  //      sh+1   ...   eh-1
  // +------+----....----+------+
  // |      |            |      |

  // print hours
  if ( ($end_hour - $start_hour) == 0 )
    $offset = 0;
  else
    $offset = round(100/($end_hour - $start_hour)/2);
    echo "\n<!-- begin timebar -->\n<table class=\"timebar\" style=\"width:100%; background-color:#FFFFFF; border:0px;\" cellspacing=\"0\" cellpadding=\"0\">\n<tr>\n<td style=\"width:$offset%;\">&nbsp;</td>\n";
  for ($i = $start_hour+1; $i < $end_hour; $i++) {
    $prev_offset = $offset;
    $offset = round(100/($end_hour - $start_hour)*($i - $start_hour + .5));
    $width = $offset - $prev_offset;
    echo "<td style=\"background-color:#FFFFFF; width:$width%; text-align:center; color:#C0C0C0; font-size:10px;\">$i</td>\n";
  }
  $width = 100 - $offset;
  echo "<td style=\"width:$width%;\">&nbsp;</td>\n";
  echo "</tr>\n</table>\n<!-- end timebar -->\n";

  // print yardstick
  echo "\n<!-- begin yardstick -->\n<table class=\"yardstick\" style=\"width:100%; background-color:#C0C0C0; border-width:0px;\" cellspacing=\"1\" cellpadding=\"0\">\n<tr>\n";
  for ($i = $start_hour, $offset = 0; $i < $end_hour; $i++) {
    $prev_offset = $offset;
    $offset = round(100/($end_hour - $start_hour)*($i - $start_hour));
    $width = $offset - $prev_offset;
    echo "<td style=\"width:$width%; background-color:#FFFFFF;\">&nbsp;</td>\n";
  }
  echo "</tr>\n</table>\n<!-- end yardstick -->\n";
}

// Get a list of nonuser calendars and return info in an array.
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

// Loads nonuser variables
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

// Return true if $login is $nonuser administrator
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

// Loads nonuser preferences if on a nonuser admin page
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

// Determines what today is after the $TZ_OFFSET and sets it globally
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

// Replace unsafe characters with HTML encoded equivalents
function clean_html($value){
  $value = htmlspecialchars($value, ENT_QUOTES);
  $value = strtr($value, array(
    '('   => '&#40;',
    ')'   => '&#41;'
  ));
  return $value;
}

// Remove non-word characters
function clean_word($data) { 
  return preg_replace("/\W/", '', $data);
}

// Remove non-digits
function clean_int($data) { 
  return preg_replace("/\D/", '', $data);
}

// Remove whitespace
function clean_whitespace($data) { 
  return preg_replace("/\s/", '', $data);
}

//converts language names to their abbreviation
function languageToAbbrev ( $name ) {
  global $browser_languages;
  foreach ( $browser_languages as $abbrev => $langname ) {
    if ( $langname == $name )
      return $abbrev;
  }
  return false;
}
?>
