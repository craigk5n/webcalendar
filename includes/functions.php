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
function display_small_month ( $thismonth, $thisyear, $showyear, $show_weeknums=false, $minical_id='', $month_link='month.php?' ) {
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved;
	global $DISPLAY_WEEKNUMBER;
	global $SCRIPT, $thisday; // Needed for day.php

	// TODO: Make day.php NOT be a special case

  if ( $user != $login && ! empty ( $user ) ) {
    $u_url = "user=$user&amp;";
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
		$month_ago = date ( "Ymd", mktime ( 3, 0, 0, $thismonth - 1, $thisday, $thisyear ) );
		$month_ahead = date ( "Ymd", mktime ( 3, 0, 0, $thismonth + 1, $thisday, $thisyear ) );

		echo "<caption>$thisday</caption>\n";
		echo "<thead>\n";
		echo "<tr class=\"monthnav\"><th colspan=\"7\">\n";
		echo "<a title=\"" . translate("Previous") . "\" class=\"prev\" href=\"day.php?{$u_url}date=$month_ago$caturl\">";
		echo "<img src=\"leftarrowsmall.gif\" alt=\"" . translate("Previous") . "\" /></a>\n";
		echo "<a title=\"" . translate("Next") . "\" class=\"next\" href=\"day.php?{$u_url}date=$month_ahead$caturl\">";
		echo "<img src=\"rightarrowsmall.gif\" alt=\"" . translate("Next") . "\" /></a>\n";
		echo month_name ( $thismonth - 1 );
		if ( $showyear != '' ) {
			echo " $thisyear";
		}
		echo "</th></tr>\n";
	} else {
		//print the month name
		echo "<caption>";

		echo "<a href=\"{$month_link}{$u_url}year=$thisyear&amp;month=$thismonth\">";
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
	if ( $show_weeknums ) echo "<th class=\"empty\">&nbsp;</th>\n";
	//if the week doesn't start on monday, print the day
	if ( $WEEK_START == 0 ) echo "<th>" .
		weekday_short_name ( 0 ) . "</th>\n";
	//cycle through each day of the week until gone
	for ( $i = 1; $i < 7; $i++ ) {
		echo "<th>" .
			weekday_short_name ( $i ) . 
		"</th>\n";
	}
	//if the week DOES start on monday, print sunday
	if ( $WEEK_START == 1 ) echo "<th>" .
		weekday_short_name ( 0 ) . 
	"</th>\n";
	//end the header row
	echo "</tr>\n</thead>\n<tbody>\n";
	for ($i = $wkstart; date("Ymd",$i) <= date ("Ymd",$monthend);
			 $i += (24 * 3600 * 7) ) {
		echo "<tr>\n";
		if ( $show_weeknums && $DISPLAY_WEEKNUMBER == 'Y' ) {
			echo "<td class=\"weeknumber\"><a href=\"week.php?{$u_url}date=".date("Ymd", $i)."\">(" . week_number($i) . ")</a></td>\n";
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
?>