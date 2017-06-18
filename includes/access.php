<?php
/* All functions related to user/role access privileges.
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
 * @version $Id: access.php,v 1.46.2.8 2008/04/21 19:45:21 umcesrjones Exp $
 * @package WebCalendar
 */

/*
   The following define statements are based on this matrix
          PUBLIC   CONFIDENTIAL   PRIVATE
   EVENT     1           8           64     =  73
   TASK      2          16          128     = 146
   JOURNAL   4          32          256     = 292
   ----------------------------------------------
             7          56          448     = 511
*/
define ( 'EVENT_WT', 73 );
define ( 'TASK_WT', 146 );
define ( 'JOURNAL_WT', 292 );

define ( 'PUBLIC_WT', 7 );
define ( 'CONF_WT', 56 );
define ( 'PRIVATE_WT', 448 );

define ( 'CAN_DOALL', 511 ); // Can access all types and levels.

/*#@+
 * Constants for use with cal_permissions column in webcal_access_function table.
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
define ( 'ACCESS_ACCESS_MANAGEMENT', 17 );
define ( 'ACCESS_PREFERENCES', 18 );
define ( 'ACCESS_SYSTEM_SETTINGS', 19 );
define ( 'ACCESS_IMPORT', 20 );
define ( 'ACCESS_EXPORT', 21 );
define ( 'ACCESS_PUBLISH', 22 );
define ( 'ACCESS_ASSISTANTS', 23 );
define ( 'ACCESS_TRAILER', 24 );
define ( 'ACCESS_HELP', 25 );
define ( 'ACCESS_ANOTHER_CALENDAR', 26 );
define ( 'ACCESS_SECURITY_AUDIT', 27 );

// Note: If you modify ACCESS_NUMBER_FUNCTIONS, you must add the new function
// to the order[] array defined in ../access.php.
define ( 'ACCESS_NUMBER_FUNCTIONS', 28 ); // How many function did we define?

/*#@-*/

// The following pages will be handled differently than the others since they
// have different uses. For example, edit_user.php adds a user when the user is
// an admin. If the user is not an admin, it updates account info. Register is
// just for new users. Most of the pages have dual uses, so we will have access
// checks within these files.
$GLOBALS['page_lookup_ex'] = array (
  'about.php' => 1,
  'colors.php' => 1,
  'css_cacher.php' => 1,
  'edit_template.php' => 1,
  'edit_user.php' => 1,
  'edit_user_handler.php' => 1,
  'icons.php' => 1,
  'index.php' => 1,
  'js_cacher.php' => 1,
  'nulogin.php' => 1,
  'register.php' => 1
  );

/* The following array provides a way to convert a page filename into a numeric
 * $ACCESS_XXX number. The array key is a regular expression. If the page
 * matches the regular expression, then it will use the corresponding access id.
 * There are some pages that have more than one use (edit_template.php is used
 * for editing a report and editing the custom header). These pages will be
 * handled differently and are listed in the $page_lookup_ex[] array.
 * @global array $GLOBAL['page_lookup']
 * @name $page_lookup
 */
$GLOBALS['page_lookup'] = array (
  ACCESS_EVENT_VIEW =>'(view_entry.php|select_user.php|purge.php|category*php|doc.php)',
  ACCESS_EVENT_EDIT =>'(_entry|list_unapproved|usersel|availability|datesel|catsel|docadd|docdel)',
  ACCESS_DAY => 'day.php',
  ACCESS_WEEK => '(week.php|week_details.php)',
  ACCESS_MONTH => 'month.php',
  ACCESS_YEAR => 'year.php',
  ACCESS_ADMIN_HOME => '(adminhome.php)',
  ACCESS_REPORT => 'report',
  ACCESS_VIEW => 'view_..php',
  ACCESS_VIEW_MANAGEMENT => '(views.php|views_edit)',
  ACCESS_CATEGORY_MANAGEMENT => 'category.*php',
  ACCESS_LAYERS => 'layer',
  ACCESS_SEARCH => 'search',
  ACCESS_ADVANCED_SEARCH => 'search',
  ACCESS_ACTIVITY_LOG => 'activity_log.php',
  ACCESS_SECURITY_AUDIT => 'security_audit.php',
  ACCESS_USER_MANAGEMENT => '(edit.*user.*.php|nonusers.*php|group.*php|users.php)',
  ACCESS_ACCOUNT_INFO => '(users.php|XYZXYZ_special_case)',
  ACCESS_ACCESS_MANAGEMENT => '(access.*php)',
  ACCESS_PREFERENCES => 'pref.*php',
  ACCESS_SYSTEM_SETTINGS => '(admin.php|admin_handler.php|controlpanel.php)',
  ACCESS_IMPORT => '(import.*php|edit_remotes.php|edit_remotes_handler.php)',
  ACCESS_EXPORT => 'export.*php',
  ACCESS_PUBLISH =>'(publish.php|freebusy.php|icalclient.php|rss.php|minical.php|upcoming.php)',
  ACCESS_ASSISTANTS => 'assist.*php',
  ACCESS_TRAILER => 'trailer.*php',
  ACCESS_HELP => 'help_.*php',
  ACCESS_ANOTHER_CALENDAR => 'select_user_.*php',
  ACCESS_SECURITY_AUDIT => 'security_audit.*php',
  ACCESS_NUMBER_FUNCTIONS => ''
  );

/* Is user access control enabled?
 *
 * @return bool True if user access control is enabled
 */
function access_is_enabled () {
  global $UAC_ENABLED;

  return ( ! empty ( $UAC_ENABLED ) && $UAC_ENABLED == 'Y' );
}

/* Return the name of a specific function.
 *
 * @param  int   $function  The function (ACCESS_DAY, etc.).
 * @return string           The text description of the function.
 */
function access_get_function_description ( $function ) {

  switch ( $function ) {
    case ACCESS_ACCESS_MANAGEMENT:
      return translate ( 'User Access Control' );
    case ACCESS_ACCOUNT_INFO:
      return translate ( 'Account' );
    case ACCESS_ACTIVITY_LOG:
      return translate ( 'Activity Log' );
    case ACCESS_SECURITY_AUDIT:
      return translate ( 'Security Audit' );
    case ACCESS_ADMIN_HOME:
      return translate ( 'Administrative Tools' );
    case ACCESS_ADVANCED_SEARCH:
      return translate ( 'Advanced Search' );
    case ACCESS_ANOTHER_CALENDAR:
      return translate ( 'Another Users Calendar' );
    case ACCESS_ASSISTANTS:
      return translate ( 'Assistants' );
    case ACCESS_CATEGORY_MANAGEMENT:
      return translate ( 'Category Management' );
    case ACCESS_DAY:
      return translate ( 'Day View' );
    case ACCESS_EVENT_EDIT:
      return translate ( 'Edit Event' );
    case ACCESS_EVENT_VIEW:
      return translate ( 'View Event' );
    case ACCESS_EXPORT:
      return translate ( 'Export' );
    case ACCESS_HELP:
      return translate ( 'Help' );
    case ACCESS_IMPORT:
      return translate ( 'Import' );
    case ACCESS_LAYERS:
      return translate ( 'Layers' );
    case ACCESS_MONTH:
      return translate ( 'Month View' );
    case ACCESS_PREFERENCES:
      return translate ( 'Preferences' );
    case ACCESS_PUBLISH:
      return translate ( 'Subscribe/Publish' );
    case ACCESS_REPORT:
      return translate ( 'Reports' );
    case ACCESS_SEARCH:
      return translate ( 'Search' );
    case ACCESS_SYSTEM_SETTINGS:
      return translate ( 'System Settings' );
    case ACCESS_TRAILER:
      return translate ( 'Common Trailer' );
    case ACCESS_USER_MANAGEMENT:
      return translate ( 'User Management' );
    case ACCESS_VIEW:
      return translate ( 'Views' );
    case ACCESS_VIEW_MANAGEMENT:
      return translate ( 'Manage Views' );
    case ACCESS_WEEK:
      return translate ( 'Week View' );
    case ACCESS_YEAR:
      return translate ( 'Year View' );
    default:
      die_miserable_death ( translate ( 'Invalid function id' ) . ': '
         . $function );
  }
}

/* Load the permissions for all users.
 *
 * Settings will be stored in the global array $access_other_cals[].
 *
 * @return array Array of permissions for viewing other calendars.
 *
 * @global array Stores permissions for viewing calendars
 */
function access_load_user_permissions ( $useCache = true ) {
  global $access_other_cals, $ADMIN_OVERRIDE_UAC, $is_admin;

  // Don't run this query twice.
  if ( ! empty ( $access_other_cals ) && $useCache == true )
    return $access_other_cals;

  $admin_override = ( $is_admin && !
    empty ( $ADMIN_OVERRIDE_UAC ) && $ADMIN_OVERRIDE_UAC == 'Y' );
  $res = dbi_execute ( 'SELECT cal_login, cal_other_user, cal_can_view,
    cal_can_edit, cal_can_approve, cal_can_email, cal_can_invite,
    cal_see_time_only FROM webcal_access_user' );
  assert ( '$res' );
  while ( $row = dbi_fetch_row ( $res ) ) {
    // TODO should we set admin_override here to apply to
    // DEFAULT CONFIGURATION only?
    // $admin_override = ( $row[1] == '__default__' && $is_admin &&
    // ! empty ( $ADMIN_OVERRIDE_UAC ) && $ADMIN_OVERRIDE_UAC == 'Y' );
    $key = $row[0] . '.' . $row[1];
    $access_other_cals[$key] = array (
      'cal_login' => $row[0],
      'cal_other_user' => $row[1],
      'view' => ( $admin_override ? CAN_DOALL : $row[2] ),
      'edit' => ( $admin_override ? CAN_DOALL : $row[3] ),
      'approve' => ( $admin_override ? CAN_DOALL : $row[4] ),
      'email' => ( $admin_override ? 'Y' : $row[5] ),
      'invite' => ( $admin_override ? 'Y' : $row[6] ),
      'time' => ( $admin_override ? 'N' : $row[7] )
      );
  }
  dbi_free_result ( $res );
  return $access_other_cals;
}

/* Returns a list of calendar logins that the specified user
 * is able to view the calendar of.
 *
 * @param string $user User login
 *
 * @return array An array of logins
 */
function access_get_viewable_users ( $user ) {
  global $access_other_cals, $login;

  $ret = array ();

  if ( empty ( $user ) )
    $user = $login;

  if ( empty ( $access_other_cals ) )
    access_load_user_permissions ();

  for ( $i = 0, $cnt = count ( $access_other_cals ); $i < $cnt; $i++ ) {
    if ( preg_match ( "/" . $user . "\. (\S+)/", $access_other_cals[$i],
        $matches ) )
      $ret[] = $matches[1];
  }
  return $ret;
}

/* Returns the row of the webcal_access_function table for the the specified user.
 *
 * If no entry is found for the specified user, then look up the user
 * '__default__'. If still no info found, then return some default values.
 *
 * @param string $user User login
 *
 * @return bool True if successful
 *
 * @global bool Is the current user an administrator?
 */
function access_load_user_functions ( $user ) {
  global $is_admin;
  static $permissions;

  if ( ! empty ( $permissions[$user] ) )
    return $permissions[$user];

  $ret = '';
  $rets = array ();
  $users = array ( $user, '__default__' );

  for ( $i = 0, $cnt = count ( $users ); $i < $cnt && empty ( $ret ); $i++ ) {
    $res = dbi_execute ( 'SELECT cal_permissions FROM webcal_access_function
      WHERE cal_login = ?', array ( $users[$i] ) );
    assert ( '$res' );
    if ( $row = dbi_fetch_row ( $res ) )
      $rets[$users[$i]] = $row[0];

    dbi_free_result ( $res );
  }
  // If still no setting found, then assume access to everything
  // if an admin user, otherwise access to all non-admin functions.
  if ( ! empty ( $rets[$user] ) )
    $ret = $rets[$user];
  else
  if ( ! empty ( $rets['__default__'] ) )
    $ret = $rets['__default__'];
  else {
    for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ) {
      $ret .= get_default_function_access ( $i, $user );
    }
  }
  // do_debug ( $user . " " . $ret);
  $permissions[$user] = $ret;
  return $ret;
}

/* Load permissions for the specified user.
 *
 * @param string $user User login
 *
 * @return bool True if successful
 *
 * @global string
 * @global bool    Is the current user an administrator?
 */
function access_init ( $user = '' ) {
  global $access_user, $is_admin, $login;

  if ( empty ( $user ) && ! empty ( $login ) )
    $user = $login;

  assert ( '! empty ( $user )' );

  $access_user = access_load_user_functions ( $user );

  return true;
}

/* Check to see if a user can access the specified page
 * (or the current page if no page is specified).
 *
 * @param int     $function  Functionality to check access to
 * @param string  $user      User login
 *
 * @return bool True if user can access the function
 *
 * @global string Username of the currently logged-in user
 */
function access_can_access_function ( $function, $user = '' ) {
  global $login;

  if ( ! access_is_enabled () )
    return true;

  if ( empty ( $user ) && ! empty ( $login ) )
    $user = $login;

  assert ( '! empty ( $user )' );
  assert ( 'isset ( $function )' );

  $access = access_load_user_functions ( $user );
  // echo $function . ' ' . $access . '<br />';
  $yesno = substr ( $access, $function, 1 );

  if ( empty ( $yesno ) )
    $yesno = get_default_function_access ( $function, $user );
  // echo "yesno = $yesno<br />\n";
  assert ( '! empty ( $yesno )' );

  return ( $yesno == 'Y' );
}

/* Check to see if a user can access the specified page
 * (or the current page if no page is specified).
 *
 * @param string  $page  Page to check access to.
 * @param string  $user  User login.
 *
 * @return bool True if user can access the page.
 *
 * @global string  $access_user     The user we're trying to access.
 * @global string  $PHP_SELF        The page currently being viewed by the user.
 * @global string  $login           The username of the currently logged-in user.
 * @global array   $page_lookup     Rules for access.
 * @global array   $page_lookup_ex  Exceptions to our rules.
 * @global bool    $is_admin        Is the currently logged-in user an administrator?
 */
function access_can_view_page ( $page = '', $user = '' ) {
  global $access_user, $is_admin, $login,
  $page_lookup, $page_lookup_ex, $PHP_SELF;

  if ( ! access_is_enabled () )
    return true;

  if ( empty ( $user ) && ! empty ( $login ) )
    $user = $login;

  assert ( '! empty ( $user )' );

  if ( empty ( $page ) && ! empty ( $PHP_SELF ) )
    $page = $PHP_SELF;

  assert ( '! empty ( $page )' );

  $page = basename ( $page );
  // Handle special cases for publish.php and freebusy.php.
  if ( substr ( $page, -3 ) == 'ics' )
    $page = 'publish.php';
  if ( substr ( $page, -3 ) == 'ifb' )
    $page = 'freebusy.php';
  // First, check list of exceptions to our rules.
  if ( ! empty ( $page_lookup_ex[$page] ) )
    return true;
  for ( $i = 0; $i <= ACCESS_NUMBER_FUNCTIONS; $i++ ) {
    if ( ! empty ( $page_lookup[$i] ) &&
      preg_match ( "/$page_lookup[$i]/", $page ) )
      $page_id = $i;
  }

   //echo "page_id = $page_id<br />page = $page<br />\n";

  // If the specified user is the currently logged in user, then we have already
  // loaded this user's access, stored in the global variable $access_user.
  $access = ( ! empty ( $login ) && $user == $login && ! empty ( $access_user )
    ? $access_user
    : // User is not the user logged in. Need to load info from db now.
    access_load_user_functions ( $user ) );

  assert ( '! empty ( $access )' );

  // If we did not find a page id, then this is also a WebCalendar bug.
  // (Someone needs to add another entry in the $page_lookup[] array.)
  $yesno  = substr ( $access, $page_id, 1 );

  // No setting found. Use default values.
  if ( empty ( $yesno ) )
    $yesno = get_default_function_access ( $page_id, $user );

  //echo "yesno = $yesno<br />\n";
  assert ( '! empty ( $yesno )' );
  return ( $yesno == 'Y' );
}

function get_default_function_access ( $page_id, $user ) {
  global $user_is_admin;

  user_load_variables ( $user, 'user_' );

  switch ( $page_id ) {
    case ACCESS_ACTIVITY_LOG:
    case ACCESS_SECURITY_AUDIT:
    case ACCESS_ADMIN_HOME:
    case ACCESS_SYSTEM_SETTINGS:
    case ACCESS_USER_MANAGEMENT:
      return ( ! empty ( $user_is_admin ) && $user_is_admin == 'Y' ? 'Y' : 'N' );
      break;
    default:
      return 'Y';
      break;
  }
}

function access_user_calendar ( $cal_can_xxx = '', $other_user, $cur_user = '',
  $type = '', $access = '' ) {
  global $access_other_cals, $access_users, $login, $ADMIN_OVERRIDE_UAC, $is_admin;

  $admin_override = ( $is_admin && !
    empty ( $ADMIN_OVERRIDE_UAC ) && $ADMIN_OVERRIDE_UAC == 'Y' );
  if ( $admin_override )
    return ( $cal_can_xxx == 'email' || $cal_can_xxx == 'invite'
      ? 'Y' : CAN_DOALL );
    
  $access_wt = $ret = $type_wt = 0;
  if ( empty ( $cur_user ) && empty ( $login ) )
    $cur_user = '__public__';

  if ( empty ( $cur_user ) && ! empty ( $login ) )
    $cur_user = $login;

  if ( $cur_user == $other_user ) {
    if ( $login  == '__public__' && $cal_can_xxx == 'approve' )
      return 'N';
    return ( $cal_can_xxx == 'email' || $cal_can_xxx == 'invite'
      ? 'Y' : CAN_DOALL );
  }

  assert ( '! empty ( $other_user )' );
  assert ( '! empty ( $cur_user )' );

  if ( empty ( $access_other_cals ) )
    access_load_user_permissions ();

  $key1 = $cur_user . '.' . $other_user;
  $key2 = $cur_user . '.__default__';
  $key3 = '__default__.' . $other_user;
  $key4 = '__default__.__default__';

  if ( isset ( $access_other_cals[$key1][$cal_can_xxx] ) )
    $ret = $access_other_cals[$key1][$cal_can_xxx];
  else
  if ( isset ( $access_other_cals[$key2][$cal_can_xxx] ) )
    $ret = $access_other_cals[$key2][$cal_can_xxx];
  else
  if ( isset ( $access_other_cals[$key3][$cal_can_xxx] ) )
    $ret = $access_other_cals[$key3][$cal_can_xxx];
  else
  if ( isset ( $access_other_cals[$key4][$cal_can_xxx] ) )
    $ret = $access_other_cals[$key4][$cal_can_xxx];

  // Check type and access levels.
  if ( ! empty ( $access ) && ! empty ( $type ) ) {
    if ( $access == 'C' )
      $access_wt = CONF_WT;
    if ( $access == 'P' )
      $access_wt = PUBLIC_WT;
    if ( $access == 'R' )
      $access_wt = PRIVATE_WT;

    if ( $type == 'E' || $type == 'M' )
      $type_wt = EVENT_WT;
    if ( $type == 'J' || $type == 'O' )
      $type_wt = JOURNAL_WT;
    if ( $type == 'T' || $type == 'N' )
      $type_wt = TASK_WT;

    $total_wt = $type_wt & $access_wt;
    $ret = ( $ret &$total_wt ? $ret : 0 );
  }

  return $ret;
}

?>
