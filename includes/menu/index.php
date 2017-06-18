<?php
/* $Id: index.php,v 1.60.2.15 2008/04/23 20:22:55 umcesrjones Exp $
 *
 * This menu was created using some fantastic free tools out on the internet:
 *  - Most icons by everaldo at http://en.crystalxp.net/ (with his permission )
 *  - Javascript & CSS by JSCookMenu at http://www.cs.ucla.edu/~heng/JSCookMenu/
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

// Configure your menu using this file.
include_once 'includes/menu/menu_config.php';

global $ALLOW_VIEW_OTHER, $BodyX, $CATEGORIES_ENABLED, $DISPLAY_TASKS,
$DISPLAY_TASKS_IN_GRID, $fullname, $has_boss, $HOME_LINK, $is_admin,
$is_assistant, $is_nonuser, $is_nonuser_admin, $login, $login_return_path,
$MENU_DATE_TOP, $menuHtml, $menuScript, $NONUSER_ENABLED, $PUBLIC_ACCESS,
$PUBLIC_ACCESS_ADD_NEEDS_APPROVAL, $PUBLIC_ACCESS_CAN_ADD,
$PUBLIC_ACCESS_OTHERS, $readonly, $REMOTES_ENABLED, $REPORTS_ENABLED,
$REQUIRE_APPROVALS, $show_printer, $single_user, $START_VIEW, $thisday,
$thismonth, $thisyear, $use_http_auth, $user, $views, $OVERRIDE_PUBLIC;

/* -----------------------------------------------------------------------------
         First figure out what options are on and privileges we have
----------------------------------------------------------------------------- */
$can_add = ( ! empty ( $readonly ) && $readonly != 'Y' );
if ( access_is_enabled () )
  $can_add = access_can_access_function ( ACCESS_EVENT_EDIT, $user );

if ( $login == '__public__' )
  $can_add = ( access_is_enabled () ? $can_add : $PUBLIC_ACCESS_CAN_ADD == 'Y' );

if ( $is_nonuser )
  $can_add = false;

$export_url = $import_url = $new_entry_url = $new_task_url = '';
$search_url = $select_user_url = $unapproved_url = '';

$help_url = 'help_index.php';
$month_url = 'month.php';
$today_url = 'day.php';
$week_url = 'week.php';
$year_url = 'year.php';

$mycal = ( empty ( $STARTVIEW ) ? 'index.php' : $STARTVIEW );

$mycal .= ( ! strpos ( $mycal, '.php' ) ? '.php' : '' );

if ( $can_add ) {
  // Add new entry.
  $new_entry_url = 'edit_entry.php';

  if ( ! empty ( $thisyear ) ) {
    $good_date = 'year=' . $thisyear
     . ( empty ( $thismonth ) ? '' : '&month=' . $thismonth )
     . ( empty ( $thisday ) ? '' : '&day=' . $thisday );
    $new_entry_url .= "?$good_date";
  }
  // Add new task.
  if ( $DISPLAY_TASKS_IN_GRID == 'Y' || $DISPLAY_TASKS == 'Y' )
    $new_task_url = 'edit_entry.php?eType=task'
     . ( empty ( $thisyear ) ? '' : "&$good_date" );
}

if ( $single_user != 'Y' ) {
  // Today
  if ( ! empty ( $user ) && $user != $login ) {
    $month_url .= '?user=' . $user;
    $today_url .= '?user=' . $user;
    $week_url .= '?user=' . $user;
    $year_url .= '?user=' . $user;

    if ( ! empty ( $new_entry_url ) )
      $new_entry_url .= '&user=' . $user;

    if ( ! empty ( $new_task_url ) )
      $new_task_url .= '&user=' . $user;
  }
  // List Unapproved.
  if ( $login != '__public__' && ! $is_nonuser && $readonly == 'N' &&
    ( $REQUIRE_APPROVALS == 'Y' || $PUBLIC_ACCESS == 'Y' ) )
    $unapproved_url = 'list_unapproved.php'
     . ( $is_nonuser_admin ? '?user=' . getValue ( 'user' ) : '' );

  // Another User's Calendar.
  if ( ( $login == '__public__' && $PUBLIC_ACCESS_OTHERS != 'Y' ) ||
      ( $is_nonuser && ! access_is_enabled () ) ) {
    // Don't allow them to see other people's calendar.
  } else
  if ( $ALLOW_VIEW_OTHER == 'Y' || $is_admin ) {
    // Also, make sure they able to access either day/week/month/year view.
    // If not, the only way to view another user's calendar is a custom view.
    if ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_ANOTHER_CALENDAR ) ) {
      // Get count of users this user can see. If > 1, then...

      $ulist = array_merge (
        get_my_users ( $login, 'view' ), get_my_nonusers ( $login, true, 'view' ) );
      
	  //remove duplicates if any
	  if ( function_exists ( 'array_intersect_key' ) )
        $ulist = array_intersect_key($ulist, array_unique(array_map('serialize', $ulist)));

      if ( count ( $ulist ) > 1 )
        $select_user_url = 'select_user.php';
    }
  }
}
// Only display some links if we're viewing our own calendar.
if ( ( empty ( $user ) || $user == $login ) || ( ! empty ( $user ) && access_is_enabled () && 
  access_user_calendar ( 'view', $user) ) ) {
  // Search
  if ( access_can_access_function ( ACCESS_SEARCH, $user ) )
    $search_url = 'search.php';
}
if ( empty ( $user ) || $user == $login ) {
  // Import/Export
  if ( access_is_enabled () || ( $login != '__public__' && ! $is_nonuser ) ) {
    if ( $readonly != 'Y' &&
      access_can_access_function ( ACCESS_IMPORT, $user ) )
      $import_url = 'import.php';

    if ( access_can_access_function ( ACCESS_EXPORT, $user ) )
      $export_url = 'export.php';
  }
}
// Help
$showHelp = ( access_is_enabled ()
  ? access_can_access_function ( ACCESS_HELP, $user )
  : ( $login != '__public__' && ! $is_nonuser ) );
// Views
$view_cnt = count ( $views );

if ( ( access_can_access_function ( ACCESS_VIEW, $user ) && $ALLOW_VIEW_OTHER != 'N' ) && $view_cnt > 0 ) {
  $views_link = array ();
  for ( $i = 0; $i < $view_cnt; $i++ ) {
    $tmp['name'] = htmlspecialchars ( $views[$i]['cal_name'], ENT_QUOTES );
    $tmp['url'] = str_replace ( '&amp;', '&', $views[$i]['url'] )
     . ( empty ( $thisdate ) ? '' : '&date=' . $thisdate );
    $views_link[$i] = $tmp;
  }
  $views_linkcnt = count ( $views_link );
  $tmp = '';
}
// Reports
$reports_linkcnt = 0;
if ( ! empty ( $REPORTS_ENABLED ) && $REPORTS_ENABLED == 'Y' &&
    access_can_access_function ( ACCESS_REPORT, $user ) ) {
  $reports_link = array ();
  $u_url = ( ! empty ( $user ) && $user != $login ? '&user=' . $user : '' );
  $rows = dbi_get_cached_rows ( 'SELECT cal_report_name, cal_report_id
    FROM webcal_report WHERE cal_login = ? OR ( cal_is_global = \'Y\'
    AND cal_show_in_trailer = \'Y\' ) ORDER BY cal_report_id',
    array ( $login ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $tmp['name'] = htmlspecialchars ( $row[0], ENT_QUOTES );
      $tmp['url'] = 'report.php?report_id=' . $row[1] . $u_url;
      $reports_link[] = $tmp;
    }
  }
  $reports_linkcnt = count ( $reports_link );
  $tmp = '';
}
// Logout/Login URL
if ( ! $use_http_auth && $single_user != 'Y' ) {
  $login_url = 'login.php';

  if ( empty ( $login_return_path ) )
    $logout_url = $login_url . '?';
  else {
    $login_url .= '?return_path=' . $login_return_path;
    $logout_url = $login_url . '&';
  }
  $logout_url .= 'action=logout';
  // Should we use another application's login/logout pages?
  if ( substr ( $GLOBALS['user_inc'], 0, 9 ) == 'user-app-' ) {
    global $app_login_page, $app_logout_page;

    $login_url = 'login-app.php'
     . ( $login_return_path != '' && $app_login_page['return'] != ''
      ? '?return_path=' . $login_return_path : '' );
    $logout_url = $app_logout_page;
  }
}
// Manage Calendar links.
if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' )
  $admincals = get_nonuser_cals ( $login );
// Make sure they have access to either month/week/day view. If they do not,
// then we cannot create a URL that shows just the boss' events. So, we would
// not include any of the "manage calendar of" links.
$have_boss_url = true;
if ( ! access_can_access_function ( ACCESS_MONTH, $user ) && !
    access_can_access_function ( ACCESS_WEEK, $user ) && !
    access_can_access_function ( ACCESS_DAY, $user ) )
  $have_boss_url = false;

if ( $have_boss_url && ( $has_boss || ! empty ( $admincals[0] ) ||
      ( $is_admin && $PUBLIC_ACCESS ) ) ) {
  $grouplist = user_get_boss_list ( $login );

  if ( ! empty ( $admincals[0] ) )
    $grouplist = array_merge ( $admincals, $grouplist );

  if ( $is_admin && $PUBLIC_ACCESS == 'Y' ) {
    $public = array (
      'cal_login' => '__public__',
      'cal_fullname' => translate ( 'Public Access' )
      );
    array_unshift ( $grouplist, $public );
  }
  $groups = '';
  $grouplistcnt = count ( $grouplist );
  for ( $i = 0; $i < $grouplistcnt; $i++ ) {
    $l = $grouplist[$i]['cal_login'];
    $f = $grouplist[$i]['cal_fullname'];
    // Don't display current $user in group list.
    if ( ! empty ( $user ) && $user == $l )
      continue;
    /*
Use the preferred view if it is day/month/week/year.php. Try not to use a
user-created view because it might not display the proper user's events.
(Fallback to month.php if this is true.)  Of course, if this user cannot
view any of the standard D/M/W/Y pages, that will force us to use the view.
*/
    $xurl = get_preferred_view ( '', 'user=' . $l );

    if ( strstr ( $xurl, 'view_' ) ) {
      if ( access_can_access_function ( ACCESS_MONTH, $user ) )
        $xurl = 'month.php?user=' . $l;
      elseif ( access_can_access_function ( ACCESS_WEEK, $user ) )
        $xurl = 'week.php?user=' . $l;
      elseif ( access_can_access_function ( ACCESS_DAY, $user ) )
        $xurl = 'day.php?user=' . $l;
      // Year does not show events, so you cannot manage someone's cal.
    }
    $xurl = str_replace ( '&amp;', '&', $xurl );
    $tmp['name'] = $f;
    $tmp['url'] = $xurl;
    $groups[] = $tmp;
  }
}
// Help URL
$help_url = ( access_can_access_function ( ACCESS_HELP, $user ) );

/* -----------------------------------------------------------------------------
              Lets make a few functions for printing menu items.
----------------------------------------------------------------------------- */
/*
JSCookMenu top menu item looks like:
[null,'Title',null,null,null,

Followed by items for that menu:
['<img src="image.png" />','Title','link.php',null,''],

Close a top level menu item:
],

Custom actions inside a menu can be done with:
[_cmNoAction, 'HTML code']

For full menu options see JSCookMenu documentation.
*/
$menuHtml = $menuScript = '';

/* A menu link.
 */
function jscMenu_menu ( $title='', $url = false, $translate=true ) {
  global $menuScript;

  $menuScript .= '[null,\'' . ( $translate ? translate ( $title ) : $title )
   . "','$url'" . ',null,null' . ( $url ? ']' : '' ) . ',';
}

/* Dropdown menu item.
 */
function jscMenu_item ( $icon, $title='', $url, $translate=true, $target = '' ) {
  global $menuScript;

  // escape single quite to avoid javascript error
  $str = preg_replace ( "/'/", "\\'", $title );
  $menuScript .= '[\'<img src="includes/menu/icons/' . $icon
   . '" alt="'. $str .'" />\',\'' . ( $translate ? translate ( $str ) : $str )
   . "','$url','$target',''],\n";
}

/* Dropdown menu item that has a sub menu.
 */
function jscMenu_sub_menu ( $icon, $title='', $translate=true  ) {
  global $menuScript;

  // escape single quite to avoid javascript error
  $str = preg_replace ( "/'/", "\\'", $title );
  $menuScript .= '[\'<img src="includes/menu/icons/' . $icon
   . '" alt="" />\',\'' . ( $translate ? translate ( $str ) : $str )
   . "','',null,'',\n";
}

/* Dropdown menu item is custom HTML.
 */
function jscMenu_custom ( $html ) {
  global $menuScript;

  $menuScript .= '[_cmNoClick,' . "'$html']\n";
}

/* Closing tag.
 */
function jscMenu_close () {
  global $menuScript;

  $menuScript .= '],
';
}

/* A divider line.
 */
function jscMenu_divider () {
  global $menuScript;

  $menuScript .= '_cmSplit,
';
}

/* -----------------------------------------------------------------------------
                        Now we need to print the menu
----------------------------------------------------------------------------- */

$menuScript .= '
    <script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
      var myMenu =
['

// Add Menu Extra if defined.
 . ( empty ( $menuExtras[0] ) ? '' : parse_menu_extras ( $menuExtras[0] ) );
// My Calendar Menu
// translate ( 'This Week' ) translate ( 'This Month' )
// translate ( 'This Year' ) translate ( 'Exit' )
if ( $menuConfig['My Calendar'] ) {
  jscMenu_menu ( 'My Calendar' );

  if ( $menuConfig['Home'] )
    jscMenu_item ( 'home.png', 'Home', $mycal );

  if ( $menuConfig['Today'] && access_can_access_function ( ACCESS_DAY ) )
    jscMenu_item ( 'today.png', 'Today', $today_url );

  if ( $menuConfig['This Week'] && access_can_access_function ( ACCESS_WEEK ) )
    jscMenu_item ( 'week.png', 'This Week', $week_url );

  if ( $menuConfig['This Month'] && access_can_access_function ( ACCESS_MONTH ) )
    jscMenu_item ( 'month.png', 'This Month', $month_url );

  if ( $menuConfig['This Year'] && access_can_access_function ( ACCESS_YEAR ) )
    jscMenu_item ( 'year.png', 'This Year', $year_url );

  if ( ! empty ( $HOME_LINK ) && $menuConfig['Exit'] )
    jscMenu_item ( 'exit.png', 'Exit', $HOME_LINK );

  jscMenu_close ();
}

// Add Menu Extra if defined.
if ( ! empty ( $menuExtras[1] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[1] );
// Events Menu
// translate ( 'Add New Event' ) translate ( 'Delete Entries' )
if ( $menuConfig['Events'] ) {
  //allow us to back out menu if empty
  $tmp1_menuScript = $menuScript;
  jscMenu_menu ( 'Events' );
  $tmp2_menuScript = $menuScript;
  if ( $new_entry_url != '' && $menuConfig['Add New Event'] )
    jscMenu_item ( 'add.png', 'Add New Event', $new_entry_url );

  if ( $new_task_url != '' && $menuConfig['Add New Task'] )
    jscMenu_item ( 'newtodo.png', 'Add New Task', $new_task_url );

  if ( $is_admin && $readonly != 'Y' && $menuConfig['Delete Entries'] )
    jscMenu_item ( 'delete.png', 'Delete Entries', 'purge.php' );

  if ( $unapproved_url != '' && $menuConfig['Unapproved Entries'] )
    jscMenu_item ( 'unapproved.png', 'Unapproved Entries', $unapproved_url );

  if ( $export_url != '' && $menuConfig['Export'] )
    jscMenu_item ( 'up.png', 'Export', $export_url );

  if ( $import_url != '' && $menuConfig['Import'] )
    jscMenu_item ( 'down.png', 'Import', $import_url );

  //if nothing was added, remove the menu
  if ( $menuScript == $tmp2_menuScript ) 
    $menuScript = $tmp1_menuScript;
  else
  jscMenu_close ();
}

// Add Menu Extra if defined.
if ( ! empty ( $menuExtras[2] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[2] );

// Views Menu
// translate ( 'My Views' ) translate ( 'Manage Calendar of' );
if ( $menuConfig['Views'] &&
  ( $select_user_url != '' || ! empty ( $views_link ) ) ) {
  //allow us to back out menu if empty
  $tmp1_menuScript = $menuScript;
  jscMenu_menu ( 'Views' );
  $tmp2_menuScript = $menuScript;
  if ( $select_user_url != '' && $menuConfig['Another Users Calendar'] )
    jscMenu_item ( 'display.png', 'Another Users Calendar',
      $select_user_url );

  if ( $login != '__public__' ) {
    if ( ! empty ( $views_link ) && $views_linkcnt > 0 && $menuConfig['My Views'] ) {
      jscMenu_sub_menu ( 'views.png', 'My Views' );

      for ( $i = 0; $i < $views_linkcnt; $i++ ) {
        jscMenu_item ( 'views.png', $views_link[$i]['name'],
          $views_link[$i]['url'], false );
      }
      jscMenu_close ();
    }

    if ( ! empty ( $groups ) && $menuConfig['Manage Calendar of'] ) {
      jscMenu_sub_menu ( 'manage_cal.png', 'Manage Calendar of' );
      $groupcnt = count ( $groups );

      for ( $i = 0; $i < $groupcnt; $i++ ) {
        jscMenu_item ( 'display.png', $groups[$i]['name'],
                  $groups[$i]['url'], false );
      }
      jscMenu_close ();
    }

    if ( ! $is_nonuser && ( ! access_is_enabled () ||
          access_can_access_function ( ACCESS_VIEW_MANAGEMENT, $user ) ) && $readonly != 'Y' && $menuConfig['Manage Views'] ) {
      jscMenu_divider ();
      jscMenu_item ( 'manage_views.png', 'Manage Views', 'views.php' );
    }
  }
  //if nothing was added, remove the menu
  if ( $menuScript == $tmp2_menuScript ) 
    $menuScript = $tmp1_menuScript;
  else
  jscMenu_close ();
}

// Add Menu Extra if defined.
if ( ! empty ( $menuExtras[3] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[3] );

// Reports Menu
// translate ( 'My Reports' )
if ( ( $is_admin || $reports_linkcnt  > 0 ) && $menuConfig['Reports'] ) {
  //allow us to back out menu if empty
  $tmp1_menuScript = $menuScript;
  jscMenu_menu ( 'Reports' );
  $tmp2_menuScript = $menuScript;
  if ( $is_admin && $menuConfig['Activity Log'] && ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_ACTIVITY_LOG, $user ) ) )
    jscMenu_item ( 'log.png', 'Activity Log', 'activity_log.php' );

  if ( $is_admin && $menuConfig['System Log'] && ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_ACTIVITY_LOG, $user ) ) )
    jscMenu_item ( 'log.png', 'System Log', 'activity_log.php?system=1' );
  if ( $is_admin && $menuConfig['Security Audit'] && ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_SECURITY_AUDIT, $user ) ) )
    jscMenu_item ( 'log.png', 'Security Audit', 'security_audit.php' );

  if ( ! empty ( $reports_link ) && $reports_linkcnt > 0 && $menuConfig['My Reports'] ) {
    jscMenu_sub_menu ( 'reports.png', 'My Reports' );

    for ( $i = 0; $i < $reports_linkcnt; $i++ ) {
      jscMenu_item ( 'document.png', $reports_link[$i]['name'],
        $reports_link[$i]['url'], false );
    }
    jscMenu_close ();
  }

  if ( $login != '__public__' && ! $is_nonuser && $REPORTS_ENABLED == 'Y' && $readonly != 'Y' && $menuConfig['Manage Reports'] && ( ! access_is_enabled () ||
        access_can_access_function ( ACCESS_REPORT, $user ) ) ) {
    jscMenu_divider ();
    jscMenu_item ( 'manage_reports.png', 'Manage Reports', 'report.php' );
  }
  //if nothing was added, remove the menu
  if ( $menuScript == $tmp2_menuScript ) 
    $menuScript = $tmp1_menuScript;
  else
  jscMenu_close ();
}

// Add Menu Extra if defined.
if ( ! empty ( $menuExtras[4] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[4] );

// Settings Menu
// translate ( 'My Profile' ) translate ( 'Public Calendar' )
// translate ( 'Unapproved Events' ) translate ( 'User Manager' )
if ( $login != '__public__' && ! $is_nonuser && $readonly 
  != 'Y' && $menuConfig['Settings'] ) {
  //allow us to back out menu if empty
  $tmp1_menuScript = $menuScript;  
  jscMenu_menu ( 'Settings' );
  $tmp2_menuScript = $menuScript;
  // Nonuser Admin Settings.
  if ( $is_nonuser_admin ) {
    if ( $single_user != 'Y' && $readonly != 'Y' && $menuConfig['NUC_Assistants'] ) {
      if ( ! access_is_enabled () ||
          access_can_access_function ( ACCESS_ASSISTANTS, $user ) )
        jscMenu_item ( 'users.png', 'Assistants',
          'assistant_edit.php?user=' . $user );
    }
    if ( $menuConfig['NUC_Preferences'] && ( ! access_is_enabled () ||
          access_can_access_function ( ACCESS_PREFERENCES, $user ) ) )
      jscMenu_item ( 'settings.png', 'Preferences', 'pref.php?user=' . $user );
    // Normal User Settings.
  } else {
    if ( $single_user != 'Y' &&
      ( $menuConfig['Assistants'] && ( ! access_is_enabled () ||
            access_can_access_function ( ACCESS_ASSISTANTS, $user ) ) ) )
      jscMenu_item ( 'users.png', 'Assistants', 'assistant_edit.php' );

    if ( $CATEGORIES_ENABLED == 'Y' && $menuConfig['Categories'] &&
      ( ! access_is_enabled () ||
          access_can_access_function ( ACCESS_CATEGORY_MANAGEMENT, $user ) ) )
      jscMenu_item ( 'folder.png', 'Categories', 'category.php' );

    if ( $menuConfig['Layers'] && ( ! access_is_enabled () ||
          access_can_access_function ( ACCESS_LAYERS, $user ) ) )
      jscMenu_item ( 'layers.png', 'Layers', 'layers.php' );

    if ( ! $is_admin && $menuConfig['My Profile'] )
      jscMenu_item ( 'profile.png', 'My Profile', 'users.php' );

    if ( $REMOTES_ENABLED == 'Y' && $menuConfig['Remote Calendars'] &&
      ( ! access_is_enabled () ||
          access_can_access_function ( ACCESS_IMPORT ) ) )
      jscMenu_item ( 'vcalendar.png', 'Remote Calendars',
        'users.php?tab=remotes' );

    if ( $menuConfig['Preferences'] && ( ! access_is_enabled () ||
          access_can_access_function ( ACCESS_PREFERENCES, $user ) ) )
      jscMenu_item ( 'settings.png', 'Preferences', 'pref.php' );

    if ( $menuConfig['Public Calendar'] && $is_admin && !
      empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' ) {
      jscMenu_sub_menu ( 'public.png', 'Public Calendar' );

      if ( $menuConfig['Public Preferences'] )
        jscMenu_item ( 'settings.png', 'Preferences', 'pref.php?public=1' );

      if ( $PUBLIC_ACCESS_CAN_ADD == 'Y' && $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y' && $menuConfig['Public Unapproved Events'] )
        jscMenu_item ( 'unapproved.png', 'Unapproved Events',
          'list_unapproved.php?user=__public__' );

      jscMenu_close ();
    }

    if ( ( $is_admin && $menuConfig['System Settings'] && !
        access_is_enabled () ) || ( access_is_enabled () &&
          access_can_access_function ( ACCESS_SYSTEM_SETTINGS, $user ) ) )
      jscMenu_item ( 'config.png', 'System Settings', 'admin.php' );

    if ( $menuConfig['User Access Control'] && access_is_enabled () &&
        ( $is_admin ||
          access_can_access_function ( ACCESS_ACCESS_MANAGEMENT, $user ) ) )
      jscMenu_item ( 'access.png', 'User Access Control', 'access.php' );

    if ( $is_admin && $menuConfig['User Manager'] && ( ( ! access_is_enabled () ||
            ( access_is_enabled () &&
              access_can_access_function ( ACCESS_USER_MANAGEMENT, $user ) ) ) ) )
      jscMenu_item ( 'user.png', 'User Manager', 'users.php' );
  }
  //if nothing was added, remove the menu
  if ( $menuScript == $tmp2_menuScript ) 
    $menuScript = $tmp1_menuScript;
  else
  jscMenu_close ();
}

// Add Menu Extra if defined.
if ( ! empty ( $menuExtras[5] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[5] );

// Search Menu
if ( ( $search_url != '' && $menuConfig['Search'] ) &&
  ( $login != '__public__' || $OVERRIDE_PUBLIC != 'Y' ) ) {
  jscMenu_menu ( 'Search' );

  $doAdv = false;
  if ( ! empty ( $menuConfig['Advanced Search'] ) ) {
    // Use UAC if enabled...
    if ( access_is_enabled () && 
      access_can_access_function ( ACCESS_ADVANCED_SEARCH ) )
      $doAdv = true;
    else if ( ! access_is_enabled () &&
      ! $is_nonuser && $login != '__public__' )
      $doAdv = true;
  }
  if ( $doAdv ) {
    jscMenu_item ( 'search.png', 'Advanced Search', 'search.php?adv=1' );
    jscMenu_divider ();
  }
  jscMenu_custom ( '<td class="ThemeMenuItemLeft"><img src="includes/menu/icons'
     . '/spacer.gif" /></td><td colspan="2"><form action="search_handler.php'
	 . ( ! empty ( $user ) ? '?users[]=' . $user : '' ) . '" '
     . 'method="post"><input type="text" name="keywords" size="25" /><input '
     . 'type="submit" value="' . translate ( 'Search' )
     . '" /></form></td>' );
  jscMenu_close ();
}

// Add Menu Extra if defined.
if ( ! empty ( $menuExtras[6] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[6] );

// Help Menu (Link)
// translate ( 'Help Contents' )  translate ( 'About WebCalendar' )
if ( $menuConfig['Help'] ) {
  jscMenu_menu ( 'Help' );

  if ( $menuConfig['Help Contents'] )
    jscMenu_item ( 'help.png', 'Help Contents', 'javascript:openHelp()' );

  if ( $menuConfig['About WebCalendar'] && $menuConfig['Help Contents'] )
    jscMenu_divider ();

  if ( $menuConfig['About WebCalendar'] )
    jscMenu_item ( 'k5n.png', 'About WebCalendar', 'javascript:openAbout()' );

  jscMenu_close ();
}
// Add spacer.
$menuScript .= "[_cmNoAction, '<td>&nbsp;&nbsp;</td>'],";
// Unapproved Icon if any exist.
$unapprovedStr = display_unapproved_events ( $is_assistant || $is_nonuser_admin
  ? $user : $login );

if ( ! empty ( $unapprovedStr ) && $unapproved_url != '' && $menuConfig['Unapproved Icon'] )
  jscMenu_item ( 'unapproved.png', '', $unapproved_url );
// Generate Printer Friendly Icon.
if ( $show_printer && $menuConfig['Printer'] )
  jscMenu_item ( 'printer.png', '', generate_printer_friendly (),
    'cal_printer_friendly' );

// Add Menu Extra if defined.
if ( ! empty ( $menuExtras[7] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[7] );

$menuScript .= '];
//]]> -->
    </script>' . "\n";

$loginStr = translate ( 'Login' );
$logoutStr = translate ( 'Logout' );

$menuHtml .= '
      <table width="100%" class="ThemeMenubar" cellspacing="0" cellpadding="0" summary="">
        <tr>
          <td class="ThemeMenubackgr"><div id="myMenuID"></div></td>'
 . ( $MENU_DATE_TOP == 'Y' && $menuConfig['MENU_DATE_TOP'] ? '
          <td class="ThemeMenubackgr ThemeMenu" align="right">
            ' . print_menu_dates ( true ) . '
          </td>' : '' ) . '
          <td class="ThemeMenubackgr ThemeMenu" align="right">'
 . ( ! empty ( $logout_url ) && $menuConfig['Login'] // Using http_auth.
  ? '<a class="menuhref" title="'
   . ( strlen ( $login ) && $login != '__public__'
    ? $logoutStr . '" href="' . $logout_url . '">' . $logoutStr
     . ':</a>&nbsp;<label>'
     . ( $menuConfig['Login Fullname'] ? $fullname : $login ) . '</label>'
    : // For public user.
    $loginStr . '" href="' . $login_url . '">' . $loginStr . '</a>' )
  : '&nbsp;&nbsp;&nbsp;' // TODO replace with something???
  ) . '&nbsp;</td>
        </tr>
      </table>';

// Add function to onload string as needed.
$BodyX = ( empty ( $BodyX ) ? 'onload="' : substr ( $BodyX, 0, -1 ) )
 . "cmDraw( 'myMenuID', myMenu, 'hbr', cmTheme, 'Theme' );\"";

/* This function allows admins to add static content to their menu.
 */
function parse_menu_extras ( $menuA ) {
  $ret = '';
  if ( $menuA[0] == 'menu' ) {
    $ret .= jscMenu_menu ( $menuA[1], $menuA[2], false );

    if ( is_array ( $menuA[3] ) ) {
      foreach ( $menuA[3] as $menuB ) {
        if ( $menuB[0] == 'item' )
          $ret .= jscMenu_item ( $menuB[1], $menuB[2], $menuB[3], false, $menuB[4] );
        elseif ( $menuB[0] == 'submenu' ) {
          $ret .= jscMenu_sub_menu ( $menuB[1], $menuB[2], false );
          foreach ( $menuB[3] as $menuC ) {
            $ret .= jscMenu_item ( $menuC[1], $menuC[2], $menuC[3], false, $menuC[4] );
          }
          $ret .= jscMenu_close ();
        } elseif ( $menuB[0] == 'divider' )
          $ret .= jscMenu_divider ();
        elseif ( $menuB[0] == 'spacer' )
          $ret .= "[_cmNoAction, '<td>&nbsp;&nbsp;</td>'],";
      }
    }
    $ret .= jscMenu_close ();
  } elseif ( $menuA[0] == 'item' )
    $ret .= jscMenu_item ( $menuA[1], $menuA[2], $menuA[3], false, $menuA[4] );

  return $ret;
}

?>
