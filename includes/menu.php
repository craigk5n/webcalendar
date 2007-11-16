<?php
/* $Id$
 *
 * This menu was created using some fantastic free tools out on the internet:
 *  - Most icons by everaldo at http://en.crystalxp.net/ (with his permission )
 *  - Javascript & CSS by JSCookMenu at http://www.cs.ucla.edu/~heng/JSCookMenu/
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

// Configure your menu using this file.
include_once 'includes/menu_config.php';

global $BodyX, $fullname, $has_boss,
$is_nonuser,
$menuHtml, $menuScript,
$show_printer, $thisday,
$thismonth, $thisyear, $views;

/* -----------------------------------------------------------------------------
         First figure out what options are on and privileges we have
----------------------------------------------------------------------------- */
$export_url = $import_url = $new_entry_url = $new_task_url = '';
$search_url = $useroption =  $unapproved_url = '';

$help_url = 'help_index.php';
$month_url = 'month.php';
$today_url = 'day.php';
$week_url = 'week.php';
$year_url = 'year.php';

$mycal = getPref ( 'STARTVIEW', 1, '', 'index.php' );
//used when looking at other user's calendars
$otherUserUrl = ( strstr ( $mycal, 'view' ) ? 'month.php' : $mycal );

	

if ( $WC->canAdd() ) {
  // Add new entry.
  $new_entry_url = 'edit_entry.php';

  if ( ! empty ( $thisyear ) ) {
    $good_date = 'year=' . $thisyear
     . ( ! empty ( $thismonth ) ? '&month=' . $thismonth : '' )
     . ( ! empty ( $thisday ) ? '&day=' . $thisday : '' );
    $new_entry_url .= "?$good_date";
  }
  // Add new task.
  if ( getPref ( 'DISPLAY_TASKS' ) || getPref ( 'DISPLAY_TASKS_IN_GRID' ) )
    $new_task_url = 'edit_entry.php?eType=task'
     . ( ! empty ( $thisyear ) ? "&$good_date" : '' );
}

if ( ! _WC_SINGLE_USER ) {
  // Today
  if ( ! empty ( $new_entry_url ) )
    $new_entry_url .= $WC->getUserUrl( $new_entry_url );

  if ( ! empty ( $new_task_url ) )
    $new_task_url .= $WC->getUserUrl( $new_task_url );

  // List Unapproved
  if ( ! $is_nonuser && ! _WC_READONLY && getPref ( 'REQUIRE_APPROVALS' ) ) {
    $unapproved_url = 'list_unapproved.php';

    if ( $WC->isNonuserAdmin() )
      $unapproved_url .= $WC->getUserUrl( $unapproved_url );
  }
  // Another User's Calendar
  if ( getPref ( 'ALLOW_VIEW_OTHER' ) || $WC->isAdmin() ) {
    // Also, make sure they able to access either day/week/month/year view.
    // If not, then there is no way to view another user's calendar except
    // a custom view.
    if ( access_can_access_function ( ACCESS_ANOTHER_CALENDAR ) ) {
      // Get count of users this user can see
			$userlist = get_my_users ( '', 'view' );
      if ( getPref ( 'NONUSER_ENABLED' ) ) {
        $nonusers = get_my_nonusers ( $WC->loginId(), true );
        $userlist = ( getPref ( 'NONUSER_AT_TOP' )
          ? array_merge ( $nonusers, $userlist )
          : array_merge ( $userlist, $nonusers ) );
      }
    }
    for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      // Don't list current user
      if ( $WC->isLogin( $userlist[$i]['cal_login'] ) )
        continue;
      $useroption .= '<option value="' . $userlist[$i]['cal_login_id'] . '">'
     . $userlist[$i]['cal_fullname'] . '<\/option>';
    }
  }
}
// Only display some links if we're viewing our own calendar.
if ( $WC->isUser( false ) ) {
  // Search
  if ( access_can_access_function ( ACCESS_SEARCH, $WC->userId() ) )
    $search_url = 'search.php';
  // Import/Export
  if ( ! _WC_READONLY &&
    access_can_access_function ( ACCESS_IMPORT, $WC->userId() ) )
    $import_url = 'import.php';

  if ( access_can_access_function ( ACCESS_EXPORT, $WC->userId() ) )
    $export_url = 'export.php';

}
// Help
$showHelp = access_can_access_function ( ACCESS_HELP, $WC->userId() );
// Views
if ( access_can_access_function ( ACCESS_VIEW, $WC->userId() ) &&
  getPref ( 'ALLOW_VIEW_OTHER' ) ) {
	$views = loadViews ();
  $view_cnt = count ( $views );
  $views_link = array ();
  for ( $i = 0; $i < $view_cnt; $i++ ) {
    $tmp['name'] = htmlspecialchars ( $views[$i]['cal_name'], ENT_QUOTES );
    $tmp['url'] = str_replace ( '&amp;', '&', $views[$i]['url'] )
     . ( ! empty ( $thisdate ) ? '&date=' . $thisdate : '' );
    $views_link[] = $tmp;
  }
  $views_linkcnt = count ( $views_link );
  $tmp = '';
}
// Reports
$reports_linkcnt = 0;
if ( getPref ( 'REPORTS_ENABLED', 2 ) &&
    access_can_access_function ( ACCESS_REPORT, $WC->userId() ) ) {
  $reports_link = array ();
  $rows = dbi_get_cached_rows ( 'SELECT cal_report_name, cal_report_id
    FROM webcal_report WHERE cal_login_id = ? OR ( cal_is_global = \'Y\'
    AND cal_show_in_trailer = \'Y\' ) ORDER BY cal_report_id',
    array ( $WC->loginId() ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $tmp['name'] = htmlspecialchars ( $row[0], ENT_QUOTES  );
      $tmp['url'] = 'report.php?report_id=' . $row[1] . $WC->getUserUrl();
      $reports_link[] = $tmp;
    }
  }
  $reports_linkcnt = count ( $reports_link );
  $tmp = '';
}

// Manage Calendar links.
if ( getPref ( 'NONUSER_ENABLED' ) )
  $admincals = get_nonuser_cals ( $WC->loginId() );
// Make sure they have access to either month/week/day view. If they do not,
// then we cannot create a URL that shows just the boss' events.  So, we would
// not include any of the "manage calendar of" links.
$have_boss_url = true;
if ( ! access_can_access_function ( ACCESS_MONTH, $WC->userId() ) && !
    access_can_access_function ( ACCESS_WEEK, $WC->userId() ) && !
    access_can_access_function ( ACCESS_DAY, $WC->userId() ) )
  $have_boss_url = false;

if ( $have_boss_url && ( $has_boss || ! empty ( $admincals[0] ) ) ) {
  $grouplist = user_get_boss_list ( $WC->loginId() );

  if ( ! empty ( $admincals[0] ) )
    $grouplist = array_merge ( $admincals, $grouplist );

  $groups = '';
  $grouplistcnt = count ( $grouplist );
  for ( $i = 0; $i < $grouplistcnt; $i++ ) {
    $l = $grouplist[$i]['cal_login_id'];
    $f = $grouplist[$i]['cal_fullname'];
    // Don't display current user in group list.
    if ( $WC->userId() == $l )
      continue;
    /*
Use the preferred view if it is day/month/week/year.php.  Try not to use a
user-created view because it might not display the proper user's events.
(Fallback to month.php if this is true.)  Of course, if this user cannot
view any of the standard D/M/W/Y pages, that will force us to use the view.
*/
    $xurl = get_preferred_view ( '', 'user=' . $l );

    if ( strstr ( $xurl, 'view_' ) ) {
      if ( access_can_access_function ( ACCESS_MONTH, $WC->userId() ) )
        $xurl = 'month.php?user=' . $l;
      elseif ( access_can_access_function ( ACCESS_WEEK, $WC->userId() ) )
        $xurl = 'week.php?user=' . $l;
      elseif ( access_can_access_function ( ACCESS_DAY, $WC->userId() ) )
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
$help_url = ( access_can_access_function ( ACCESS_HELP, $WC->userId() ) );

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

For full menu options see JSCookMenu documentation
*/
$menuScript = $menuHtml = '';

/* A menu link
 */
function jscMenu_menu ( $title, $url = false ) {
  global $menuScript;

  $menuScript .= '[null,\'' . ( $title != '' ? translate ( $title ) : '' )
   . "','$url'" . ',null,null' . ( $url ? ']' : '' ) . ',';
}

/* Dropdown menu item
 */
function jscMenu_item ( $icon, $title, $url, $target = '' ) {
  global $menuScript;

  $menuScript .= '[\'<img src="images/icons/' . $icon
   . '" alt="" />\',\'' . ( $title != '' ? translate ( $title ) : '' )
   . "','$url','$target',''],\n";
}

/* Dropdown menu item that has a sub menu
 */
function jscMenu_sub_menu ( $icon, $title ) {
  global $menuScript;

  $menuScript .= '[\'<img src="images/icons/' . $icon
   . '" alt="" />\',\'' . ( $title != '' ? translate ( $title ) : '' )
   . "','',null,'',\n";
}

/* Dropdown menu item is custom html
 */
function jscMenu_custom ( $html ) {
  global $menuScript;
	
    $menuScript .= '[_cmNoClick,' . "'$html']\n";
}

/* Closing tag
 */
function jscMenu_close () {
  global $menuScript;

  $menuScript .= '],
';
}

/* A divider line
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

// Add Menu Extra if defined
 . ( ! empty ( $menuExtras[0] ) ? parse_menu_extras ( $menuExtras[0] ) : '' );
// My Calendar Menu translate ( 'My Calendar' ) translate ( 'Home' )
// translate ( 'This Week' ) translate ( 'This Month' )
// translate ( 'This Year' ) translate ( 'Exit' )
if ( $menuConfig['My Calendar'] ) {
  jscMenu_menu ( 'My Calendar' );

  if ( $menuConfig['Home'] )
    jscMenu_item ( 'home.png', 'Home', $mycal );
  if ( $menuConfig['Today'] )
    jscMenu_item ( 'today.png', 'Today', $today_url );
  if ( $menuConfig['This Week'] )
    jscMenu_item ( 'week.png', 'This Week', $week_url );
  if ( $menuConfig['This Month'] )
    jscMenu_item ( 'month.png', 'This Month', $month_url );
  if ( $menuConfig['This Year'] )
    jscMenu_item ( 'year.png', 'This Year', $year_url );
  if ( getPref ( 'HOME_LINK' ) && $menuConfig['Exit'] )
    jscMenu_item ( 'exit.png', 'Exit', getPref ( 'HOME_LINK' ) );

  jscMenu_close ();
}

// Add Menu Extra if defined
if ( ! empty ( $menuExtras[1] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[1] );
// Events Menu
// translate ( 'Add New Event' ) translate ( 'Delete Entries' )
// translate ( 'Add New Task' )
if ( $menuConfig['Events'] ) {
  jscMenu_menu ( 'Events' );

  if ( $new_entry_url != '' && $menuConfig['Add New Event'] )
    jscMenu_item ( 'add.png', 'Add New Event', $new_entry_url );
  if ( $new_task_url != '' && $menuConfig['Add New Task'] )
    jscMenu_item ( 'newtodo.png', 'Add New Task', $new_task_url );
  if ( $WC->isAdmin() && ! _WC_READONLY && $menuConfig['Delete Entries'] )
    jscMenu_item ( 'delete.png', 'Delete Entries', 'purge.php' );
  if ( $unapproved_url != '' && $menuConfig['Unapproved Entries'] )
    jscMenu_item ( 'unapproved.png', 'Unapproved Entries', $unapproved_url );
  if ( $export_url != '' && $menuConfig['Export'] )
    jscMenu_item ( 'up.png', 'Export', $export_url );
  if ( $import_url != '' && $menuConfig['Import'] )
    jscMenu_item ( 'down.png', 'Import', $import_url );

  jscMenu_close ();
}

// Add Menu Extra if defined
if ( ! empty ( $menuExtras[2] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[2] );

// Views Menu
// translate ( 'My Views' ) translate ( 'Manage Calendar of' );
if ( $menuConfig['Views'] ) {
  jscMenu_menu ( 'Views' );

  if ( $menuConfig['Another Users Calendar'] )
    jscMenu_sub_menu ( 'display.png', 'Another Users Calendar' );

    jscMenu_custom ( 
		  '<td class="MenuItemLeft" valign="top"><img src="images/icons/search.png" /><\/td>'
		  . '<td colspan="2">'
			. '<input type="text" id="hint" size="25" value"'
			. translate ( 'Lookup User' ) . '" onKeyUp="lookupName(\\\'viewuser\\\')"/>'
			. '<form action="' . $otherUserUrl .'" method="get" name="SelectUser">'
			. '<select style="margin-left:3px" id="viewuser" name="user" onchange="document.SelectUser.submit()">'
			. $useroption
			. '<input type="submit" value="' . translate ( 'Go' ) .'" />'
			. '<\/form><\/td>');
		jscMenu_close ();
		
    if ( ! empty ( $views_link ) && $views_linkcnt > 0 &&
      $menuConfig['My Views'] ) {
      jscMenu_sub_menu ( 'views.png', 'My Views' );

      for ( $i = 0; $i < $views_linkcnt; $i++ ) {
        jscMenu_item ( 'views.png', $views_link[$i]['name'],
          $views_link[$i]['url'] );
      }
      jscMenu_close ();
    }

    if ( ! empty ( $groups ) && $menuConfig['Manage Calendar of'] ) {
      jscMenu_sub_menu ( 'manage_cal.png', 'Manage Calendar of' );
      $groupcnt = count ( $groups );

      for ( $i = 0; $i < $groupcnt; $i++ ) {
        jscMenu_item ( 'display.png', $groups[$i]['name'], $groups[$i]['url'] );
      }
      jscMenu_close ();
    }

    if ( ! $is_nonuser &&
      access_can_access_function ( ACCESS_VIEW_MANAGEMENT, $WC->userId() ) &&
      ! _WC_READONLY && $menuConfig['Manage Views'] ) {

      jscMenu_divider ();
      jscMenu_item ( 'manage_views.png', 'Manage Views', 'views.php' );
    }
  jscMenu_close ();
}

// Add Menu Extra if defined
if ( ! empty ( $menuExtras[3] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[3] );

// Reports Menu
// translate ( 'My Reports' )
if ( ! $is_nonuser && getPref ( 'REPORTS_ENABLED', 2 )
  && $menuConfig['Reports'] ) {
  jscMenu_menu ( 'Reports' );

  if ( $WC->isAdmin() && $menuConfig['Activity Log'] &&
    access_can_access_function ( ACCESS_ACTIVITY_LOG, $WC->userId() ) )
    jscMenu_item ( 'log.png', 'Activity Log', 'activity_log.php' );

  if ( $WC->isAdmin() && $menuConfig['System Log'] &&
    access_can_access_function ( ACCESS_ACTIVITY_LOG, $WC->userId() ) )
    jscMenu_item ( 'log.png', 'System Log', 'activity_log.php?system=1' );

  if ( ! empty ( $reports_link ) && $reports_linkcnt > 0 &&
    $menuConfig['My Reports'] ) {
    jscMenu_sub_menu ( 'reports.png', 'My Reports' );

    for ( $i = 0; $i < $reports_linkcnt; $i++ ) {
      jscMenu_item ( 'document.png', $reports_link[$i]['name'],
        $reports_link[$i]['url'] );
    }
    jscMenu_close ();
  }

  if ( ! $is_nonuser && getPref ( 'REPORTS_ENABLED', 2 ) && 
    ! _WC_READONLY && $menuConfig['Manage Reports'] &&
    access_can_access_function ( ACCESS_REPORT, $WC->userId() ) ) {
    jscMenu_divider ();
    jscMenu_item ( 'manage_reports.png', 'Manage Reports', 'report.php' );
  }
  jscMenu_close ();
}

// Add Menu Extra if defined
if ( ! empty ( $menuExtras[4] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[4] );

// Settings Menu
// translate ( 'My Profile' ) translate ( 'Public Calendar' )
// translate ( 'Unapproved Events' ) translate ( 'User Manager' )

if ( ! $WC->isNonUser ( ) && ! _WC_READONLY &&
  $menuConfig['Settings'] ) {
  jscMenu_menu ( 'Settings' );

    if ( getPref ( 'CATEGORIES_ENABLED' ) && $menuConfig['Categories'] &&
      access_can_access_function ( ACCESS_CATEGORY_MANAGEMENT, 
	    $WC->userId() ) )
      jscMenu_item ( 'folder.png', 'Categories', 'category.php' );

    if ( $menuConfig['Layers'] &&
      access_can_access_function ( ACCESS_LAYERS, 
	    $WC->userId() ) )
      jscMenu_item ( 'layers.png', 'Layers', 'layers.php' );

    if ( ! $WC->isAdmin() && $menuConfig['My Profile'] )
      jscMenu_item ( 'profile.png', 'My Profile', 'users.php' );

    if ( getPref ( 'REMOTES_ENABLED', 2 ) && $menuConfig['Remote Calendars'] &&
      access_can_access_function ( ACCESS_IMPORT ) )
      jscMenu_item ( 'vcalendar.png', 'Remote Calendars', 'users.php?tab=remotes' );

    if ( $menuConfig['Preferences'] &&
      access_can_access_function ( ACCESS_PREFERENCES, 
	    $WC->userId() ) )
      jscMenu_item ( 'settings.png', 'Preferences', 'pref.php' );


    if ( $menuConfig['System Settings'] && 
      access_can_access_function ( ACCESS_SYSTEM_SETTINGS, 
	    $WC->userId() ) )
      jscMenu_item ( 'config.png', 'System Settings', 'admin.php' );

    if ( $menuConfig['User Access Control'] &&
      access_can_access_function ( ACCESS_ACCESS_MANAGEMENT, 
	    $WC->userId() ) )
      jscMenu_item ( 'access.png', 'User Access Control', 'access.php' );

    if ( $menuConfig['User Manager'] && 
      access_can_access_function ( ACCESS_USER_MANAGEMENT, 
	    $WC->userId() ) )
      jscMenu_item ( 'user.png', 'User Manager', 'users.php' );
  jscMenu_close ();
}

// Add Menu Extra if defined
if ( ! empty ( $menuExtras[5] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[5] );

// Search Menu
if ( $search_url != '' && $menuConfig['Search'] ) {
  jscMenu_menu ( 'Search' );

  if ( $menuConfig['Advanced Search'] && 
    ( access_can_access_function ( ACCESS_ADVANCED_SEARCH ) ) ) {
    jscMenu_item ( 'search.png', 'Advanced Search', 'search.php?adv=1' );
    jscMenu_divider ();
  }
  jscMenu_custom ( '<td class="MenuItemLeft"><img src="images/icons'
     . '/spacer.gif" /><\/td><td colspan="2"><form action="search_handler.php" '
     . 'method="post"><input type="text" name="keywords" size="25" /><input '
     . 'type="submit" value="' . translate ( 'Search' )
     . '" /><\/form><\/td>' );
  jscMenu_close ();
}

// Add Menu Extra if defined
if ( ! empty ( $menuExtras[6] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[6] );

// Help Menu (Link )
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
// Add spacer
$menuScript .= "[_cmNoAction, '<td>&nbsp;&nbsp;<\/td>'],";
// Unapproved Icon if any exist
$unapprovedStr = display_unapproved_events ( ( $WC->isNonuserAdmin()
    ? $WC->userId() : $WC->loginId() ) );

if ( ! empty ( $unapprovedStr ) && $unapproved_url != '' &&
  $menuConfig['Unapproved Icon'] )
  jscMenu_item ( 'unapproved.png', '', $unapproved_url );
// Generate Printer Friendly Icon
if ( $menuConfig['Printer'] ) {
  $href = generate_printer_friendly ();
  jscMenu_item ( 'printer.png', '', $href, 'cal_printer_friendly' );
}

// Add Menu Extra if defined
if ( ! empty ( $menuExtras[7] ) )
  $menuScript .= parse_menu_extras ( $menuExtras[7] );

$menuScript .= '];
//]]> -->
    </script>' . "\n";

/* This function allows admins to add static content to their menu.
 */
function parse_menu_extras ( $menuA ) {
  $ret = '';
  if ( $menuA[0] == 'menu' ) {
    $ret .= jscMenu_menu ( $menuA[1], $menuA[2] );

    if ( is_array ( $menuA[3] ) ) {
      foreach ( $menuA[3] as $menuB ) {
        if ( $menuB[0] == 'item' )
          $ret .= jscMenu_item ( $menuB[1], $menuB[2], $menuB[3], $menuB[4] );
        elseif ( $menuB[0] == 'submenu' ) {
          $ret .= jscMenu_sub_menu ( $menuB[1], $menuB[2] );
          foreach ( $menuB[3] as $menuC ) {
            $ret .= jscMenu_item ( $menuC[1], $menuC[2], $menuC[3], $menuC[4] );
          }
          $ret .= jscMenu_close ();
        } elseif ( $menuB[0] == 'divider' )
          $ret .= jscMenu_divider ();
        elseif ( $menuB[0] == 'spacer' )
          $ret .= "[_cmNoAction, '<td>&nbsp;&nbsp;<\/td>'],";
      }
    }
    $ret .= jscMenu_close ();
  } elseif ( $menuA[0] == 'item' )
    $ret .= jscMenu_item ( $menuA[1], $menuA[2], $menuA[3], $menuA[4] );
  return $ret;
}


?>