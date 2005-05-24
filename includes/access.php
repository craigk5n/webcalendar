<?php
/**
 * This file includes all functions related to user/role
 * access privileges.
 *
 * Access is restricted in two ways: by function and by a user's calendar.
 *
 * The webcal_access_user table keeps track of when one user can view
 * the calendar of another user.
 *
 * The webcal_access_function table grants access to specific
 * WebCalendar pages for specific users.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 * @version $Id$
 */


// make sure this file cannot be accessed directly
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
  die ( "You can't access this file directly!" );
}


/**#@+
 * Constants for use with cal_permissions column in
 * webcal_access_function table.
 */
define ( 'ACCESS_EVENT_VIEW', 0 );
define ( 'ACCESS_EVENT_EDIT', 1 );
define ( 'ACCESS_DAY', 2 );
define ( 'ACCESS_WEEK', 3 );
define ( 'ACCESS_MONTH', 4 );
define ( 'ACCESS_YEAR', 5 );
define ( 'ACCESS_ADMIN_HOME', 6 );
define ( 'ACCESS_REPORT', 7 );
define ( 'ACCESS_VIEW', 8 );
define ( 'ACCESS_VIEW_MANAGEMENT', 9 );
define ( 'ACCESS_CATEGORY_MANAGEMENT', 10 );
define ( 'ACCESS_LAYERS', 11 );
define ( 'ACCESS_SEARCH', 12 );
define ( 'ACCESS_ADVANCED_SEARCH', 13 );
define ( 'ACCESS_ACTIVITY_LOG', 14 );
define ( 'ACCESS_USER_MANAGEMENT', 15 );
define ( 'ACCESS_ACCOUNT_INFO', 16 );
define ( 'ACCESS_ACCESS_MANAGEMENT', 17);
define ( 'ACCESS_PREFERENCES', 18 );
define ( 'ACCESS_SYSTEM_SETTINGS', 19 );
define ( 'ACCESS_IMPORT', 20 );
define ( 'ACCESS_EXPORT', 21 );
define ( 'ACCESS_PUBLISH', 22 );
define ( 'ACCESS_ASSISTANTS', 23 );
define ( 'ACCESS_TRAILER', 24 );
define ( 'ACCESS_HELP', 25 );
define ( 'ACCESS_NUMBER_FUNCTIONS', 26 ); // how many function did we define?
/**#@-*/


// The following pages will be handled differently than the others
// since they have different uses.  For example, edit_user.php
// adds a user when the user is an admin.  If the user is not an
// admin, it updates account info.
// Most of the pages have dual uses, so we will have access checks within
// these files.
$GLOBALS['page_lookup_ex'] = array (
  "colors.php" => 1,
  "index.php" => 1,
  "edit_template.php" => 1,
  "edit_user.php" => 1,
  "edit_user_handler.php" => 1,
);

/**
 * The following array provides a way to convert a page filename into
 * a numeric $ACCESS_XXX number.
 * The array key is a regular expression.  If the page matches the
 * regular expression, then it will use the corresponding access id.
 * There are some pages that have more than one use (edit_template.php
 * is used for editing a report and editing the custom header).  These
 * pages will be handled differently and are listed in
 * the $page_lookup_ex[] array.
 * @global array $GLOBAL['page_lookup']
 * @name $page_lookup
 */
$GLOBALS['page_lookup'] = array (
  ACCESS_EVENT_VIEW =>
    "(view_entry.php|select_user.php|purge.php|category*php)",
  ACCESS_EVENT_EDIT => "(entry|list_unapproved)",
  ACCESS_DAY => "day.php",
  ACCESS_WEEK => "week.php",
  ACCESS_MONTH => "month.php",
  ACCESS_YEAR => "year.php",
  ACCESS_ADMIN_HOME => "(adminhome.php|users.php)",
  ACCESS_REPORT => "report",
  ACCESS_VIEW => "view_..php",
  ACCESS_VIEW_MANAGEMENT => "(views.php|views_edit)",
  ACCESS_CATEGORY_MANAGEMENT => "category*php",
  ACCESS_LAYERS => "layer",
  ACCESS_SEARCH => "search",
  ACCESS_ACTIVITY_LOG => "activity_log.php",
  ACCESS_USER_MANAGEMENT => "(edit.*user.*.php|nonusers.*php|group.*php)",
  ACCESS_ACCOUNT_INFO => "XYZXYZ_special_case",
  ACCESS_ACCESS_MANAGEMENT => "(access.*php)",
  ACCESS_PREFERENCES => "pref.*php",
  ACCESS_SYSTEM_SETTINGS => "(admin.php|admin_handler.php)",
  ACCESS_IMPORT => "import.*php",
  ACCESS_EXPORT => "export.*php",
  ACCESS_PUBLISH => "(publish.php|freebusy.php|rss.php)",
  ACCESS_ASSISTANTS => "assist.*php",
  ACCESS_HELP => "help_.*php",
);


//CREATE TABLE webcal_access_user (
//  cal_login VARCHAR(50) NOT NULL,
//  cal_other_user VARCHAR(50) NOT NULL,
//  cal_can_view CHAR(1) NOT NULL DEFAULT 'N',
//  cal_can_edit CHAR(1) NOT NULL DEFAULT 'N',
//  cal_can_delete CHAR(1) NOT NULL DEFAULT 'N',
//  cal_can_approve CHAR(1) NOT NULL DEFAULT 'N',
//  PRIMARY KEY ( cal_login, cal_other_user )
//);
//
//CREATE TABLE webcal_access_function (
//  cal_login VARCHAR(50) NOT NULL,
//  /* a string of 'Y' or 'N' for the various functions */
//  cal_permissions VARCHAR(64) NOT NULL,
//  PRIMARY KEY ( cal_login )
//);



// Global variable used to cache permissions
$access_other_cals = array ( );


/** Is user access control enabled?
  */
function access_is_enabled ()
{
  global $uac_enabled;

  return ( ! empty ( $uac_enabled ) && $uac_enabled == 'Y' );
}


/** Return the name of a specific function.
  * @param int function the function (ACCESS_DAY, etc.)
  * @return the text description of the function
  */
function access_get_function_description ( $function )
{
  switch ( $function ) {
    case ACCESS_EVENT_VIEW:
      return translate ( "View Event" );
    case ACCESS_EVENT_EDIT:
      return translate ( "Edit Event" );
    case ACCESS_DAY:
      return translate ( "Day View" );
    case ACCESS_WEEK:
      return translate ( "Week View" );
    case ACCESS_MONTH:
      return translate ( "Month View" );
    case ACCESS_YEAR:
      return translate ( "Year View" );
    case ACCESS_ADMIN_HOME:
      return translate ( "Administrative Tools" );
    case ACCESS_REPORT:
      return translate ( "Reports" );
    case ACCESS_VIEW:
      return translate ( "Views" );
    case ACCESS_VIEW_MANAGEMENT:
      return translate ( "Manage Views" );
    case ACCESS_CATEGORY_MANAGEMENT:
      return translate ( "Category Management" );
    case ACCESS_LAYERS:
      return translate ( "Layers" );
    case ACCESS_SEARCH:
      return translate ( "Search" );
    case ACCESS_ADVANCED_SEARCH:
      return translate ( "Advanced Search" );
    case ACCESS_ACTIVITY_LOG:
      return translate ( "Activity Log" );
    case ACCESS_USER_MANAGEMENT:
      return translate ( "User Management" );
    case ACCESS_ACCOUNT_INFO:
      return translate ( "Account" );
    case ACCESS_ACCESS_MANAGEMENT:
      return translate ( "User Access Control" );
    case ACCESS_PREFERENCES:
      return translate ( "Preferences" );
    case ACCESS_SYSTEM_SETTINGS:
      return translate ( "System Settings" );
    case ACCESS_IMPORT:
      return translate ( "Import" );
    case ACCESS_EXPORT:
      return translate ( "Export" );
    case ACCESS_PUBLISH:
      return translate ( "Subscribe/Publish" );
    case ACCESS_ASSISTANTS:
      return translate ( "Assistants" );
    case ACCESS_TRAILER:
      return translate ( "Common Trailer" );
    case ACCESS_HELP:
      return translate ( "Help" );
    default:
      die_miserable_death ( "Invalid function id: $function" );
  }
}


/** Load the permissions for a specific user.
  * Settings will be stored in the global array $access_other_cals[].
  * @param string $user user login
  * @global array $access_other_cals stores permissions for viewing calendars
  */
function access_load_user_permissions ( $user )
{
  global $access_other_cals;

  assert ( ! empty ( $user ) );

  // Don't run this query twice
  if ( ! empty ( $access_other_cals[$user] ) )
    return $access_other_cals;

  $sql = "SELECT cal_login, cal_other_user, " .
    "cal_can_view, cal_can_edit, cal_can_delete, cal_can_approve " .
    "FROM webcal_access_user WHERE cal_login = '$user'";
  $res = dbi_query ( $sql );
  assert ( $res );
  while ( $row = dbi_fetch_row ( $res ) ) {
    $key = $row[0] . "." . $row[1];
    $access_other_cals[$key] = array (
      "cal_login" => $row[0],
      "cal_other_user" => $row[1],
      "cal_can_view" => $row[2],
      "cal_can_edit" => $row[3],
      "cal_can_delete" => $row[4],
      "cal_can_approve" => $row[5]
    );
  }
  dbi_free_result ( $res );
  $access_other_cals[$user] = 1;

  return $access_other_cals;
}


/** Return a list of calendar logins that the specified user
  * is able to view the calendar of.
  *
  * @param string $user user login
  * @global bool $is_admin
  * @return an array of logins
  */
function access_get_viewable_users ( $user )
{
  global $access_other_cals, $login;
  $ret = array ( );

  if ( empty ( $user ) )
    $user = $login;

  if ( empty ( $access_other_cals[$user] ) )
    access_load_user_permissions ( $user );

  for ( $i = 0; $i < count ( $access_other_cals ); $i++ ) {
    if ( preg_match ( "/" . $user . "\.(\S+)/", $access_other_cals[$i],
      $matches ) ) {
      //echo "viewable: $matches[1]<br>\n";
      $ret[] = $matches[1];
    }
  }
  return $ret;
}

/** Return the row of the webcal_access_function table for the
  * the specified user.  If no entry is found for the specified user,
  * the the user '__default__' will be looked up.  If still no
  * info found, then some default values will be returned.
  * @param string $user user login
  * @global bool $is_admin
  * @return true if successful
  */
function access_load_user_functions ( $user )
{
  global $is_admin;

  $ret = '';
  $users = array ( $user, '__default__' );

  for ( $i = 0; $i < count ( $user ) && empty ( $ret ); $i++ )  {
    $res = dbi_query ( "SELECT cal_permissions FROM webcal_access_function " .
      "WHERE cal_login = '" . $users[$i] . "'" );
    assert ( $res );
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ret = $row[0];
    }
    dbi_free_result ( $res );
  }

  // If still no setting found, then assume access to everything if
  // an admin user, otherwise access to all non-admin functions.
  if ( empty ( $ret ) ) {
    for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ) {
      $ret .= get_default_function_access ( $i );
    }
  }

  return $ret;
}


/** Load permissions for the specified user.
  * @param string $user user login
  * @global string $access_user
  * @global bool $is_admin
  * @return true if successful
  */
function access_init ( $user="" )
{
  global $login, $access_user, $is_admin;

  if ( empty ( $user ) && ! empty ( $login ) )
    $user = $login;

  assert ( ! empty ( $user ) );

  $access_user = access_load_user_functions ( $user );

  return true;
}

/** Check to see if a user can access the specified page
  * (or the current page if no page is specified).
  * @param int $function functionality to check access to
  * @param string $user user login
  * @global string $login
  * @return true if user can access the function
  */
function access_can_access_function ( $function, $user="" )
{
  global $login;

  if ( ! access_is_enabled () )
    return true;

  if ( empty ( $user ) && ! empty ( $login ) )
    $user = $login;

  assert ( ! empty ( $user ) );
  assert ( isset ( $function ) );

  $access = access_load_user_functions ( $user );

  $yesno = substr ( $access, $function, 1 );
  if ( empty ( $yesno ) )
    $yesno = get_default_function_access ( $function );
  //echo "yesno = $yesno <br/>\n";
  assert ( ! empty ( $yesno ) );
  
  return ( $yesno == 'Y' );
}

/** Check to see if a user can view the calendar of another user.
  * @param string $other_user user login of calendar to view
  * @param string $cur_user user login of current user
  * @global array $access_users
  * @return true if user can access the other user's calendar
  */
function access_can_view_user_calendar ( $other_user, $cur_user='' )
{
  global $login, $access_users;
  $ret = false;

  if ( empty ( $cur_user ) && ! empty ( $login ) )
    $cur_user = $login;

  assert ( ! empty ( $other_user ) );
  assert ( ! empty ( $cur_user ) );

  // User can always access their own calendar, and we don't store
  // that in the database.
  if ( $cur_user == $other_user )
    return true;

  $access = access_load_user_permissions ( $cur_user );

  $key = $cur_user . "." . $other_user;

  if ( ! empty ( $access[$key] ) &&
    ! empty ( $access[$key]['cal_can_view'] ) &&
    $access[$key]['cal_can_view'] == 'Y' )
    $ret = true;
    
  //echo "can access $other_user = " . ( $ret ? "true" : "false" ) , "<br/>\n";
  
  return ( $ret );
}


/** Check to see if a user can approve an event on another user's calendar.
  * @param string $other_user user login of calendar to view
  * @param string $cur_user user login of current user
  * @global array $access_users
  * @return true if user can access the other user's calendar
  */
function access_can_approve_user_calendar ( $other_user, $cur_user='' )
{
  global $login, $access_users;
  $ret = false;

  if ( empty ( $cur_user ) && ! empty ( $login ) )
    $cur_user = $login;

  assert ( ! empty ( $other_user ) );
  assert ( ! empty ( $cur_user ) );

  // User can always access their own calendar, and we don't store
  // that in the database.
  if ( $cur_user == $other_user )
    return true;

  $access = access_load_user_permissions ( $cur_user );

  $key = $cur_user . "." . $other_user;

  if ( ! empty ( $access[$key] ) &&
    ! empty ( $access[$key]['cal_can_approve'] ) &&
    $access[$key]['cal_can_approve'] == 'Y' )
    $ret = true;
    
  //echo "can approve $other_user = " . ( $ret ? "true" : "false" ) , "<br/>\n";
  
  return ( $ret );
}


/** Check to see if a user can delete an event on another user's calendar.
  * @param string $other_user user login of calendar to delete from
  * @param string $cur_user user login of current user
  * @global array $access_users
  * @return true if user can delete from the other user's calendar
  */
function access_can_delete_user_calendar ( $other_user, $cur_user='' )
{
  global $login, $access_users;
  $ret = false;

  if ( empty ( $cur_user ) && ! empty ( $login ) )
    $cur_user = $login;

  assert ( ! empty ( $other_user ) );
  assert ( ! empty ( $cur_user ) );

  // User can always access their own calendar, and we don't store
  // that in the database.
  if ( $cur_user == $other_user )
    return true;

  $access = access_load_user_permissions ( $cur_user );

  $key = $cur_user . "." . $other_user;

  if ( ! empty ( $access[$key] ) &&
    ! empty ( $access[$key]['cal_can_delete'] ) &&
    $access[$key]['cal_can_delete'] == 'Y' )
    $ret = true;
    
  //echo "can delete $other_user = " . ( $ret ? "true" : "false" ) , "<br/>\n";
  
  return ( $ret );
}


/** Check to see if a user can access the specified page
  * (or the current page if no page is specified).
  * @param string $page page to check access to
  * @param string $user user login
  * @global string $page_lookup
  * @global string $page_lookup_ex
  * @global string $PHP_SELF
  * @global string $login
  * @return true if user can access the page
  */
function access_can_view_page ( $page="", $user="" )
{
  global $PHP_SELF, $login, $page_lookup, $page_lookup_ex, $is_admin;
  $page_id = -1;

  if ( ! access_is_enabled () )
    return true;

  if ( empty ( $user ) && ! empty ( $login ) )
    $user = $login;

  assert ( ! empty ( $user ) );

  if ( empty ( $page ) && ! empty ( $PHP_SELF ) )
    $page = $PHP_SELF;

  assert ( ! empty ( $page ) );

  $page = basename ( $page );
  // First, check list of exceptions to our rules
  if ( ! empty ( $page_lookup_ex[$page] ) )
    return true;

  for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS && $page_id < 0; $i++ ) {
    if ( ! empty ( $page_lookup[$i] ) &&
      preg_match ( "/$page_lookup[$i]/", $page ) ) {
      $page_id = $i;
    } else {
      //echo "Does not match '$page_lookup[$i]'<br>\n";
    }
  }

  //echo "page_id = $page_id <br/>page = $page<br/>\n";

  // If the specified user is the currently logged in user, then we have
  // already loaded this user's access, and it is stored in the global
  // variable $access_user.
  $access = '';
  if ( ! empty ( $login ) && $user == $login && ! empty ( $access_user ) ) {
    $access = $access_user;
  } else {
    // User is not the user logged in.  Need to load info from db now.
    $access = access_load_user_functions ( $user );
  }

  assert ( ! empty ( $access ) );

  // If we did not find a page id, then this is also a WebCalendar bug.
  // (Someone needs to add another entry in the $page_lookup[] array.)
  assert ( $page_id >= 0 );

  // Now that we know which function (page_id), see if the user can
  // access it.
  $access = access_load_user_functions ( $user );

  $yesno = substr ( $access, $page_id, 1 );

  // No setting found.  Use default values.
  if ( empty ( $yesno ) ) {
    $yesno = get_default_function_access ( $page_id );
  }

  //echo "yesno = $yesno <br/>\n";
  assert ( ! empty ( $yesno ) );
  
  return ( $yesno == 'Y' );
}


function get_default_function_access ( $page_id )
{
  global $is_admin;

  switch ( $page_id ) {
    case ACCESS_ADMIN_HOME:
    case ACCESS_ACTIVITY_LOG:
    case ACCESS_USER_MANAGEMENT:
    case ACCESS_SYSTEM_SETTINGS:
      return $is_admin ? 'Y' : 'N';
      break;
    default:
      return 'Y';
      break;
  }
}



?>
