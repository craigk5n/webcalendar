<?php

/*  
 * This menu was created using some fantastic free tools out on the 
 * internet:
 *   - Most icons by everaldo at http://en.crystalxp.net/ (with his permission)
 *   - Javascript & CSS by JSCookMenu at http://www.cs.ucla.edu/~heng/JSCookMenu/ 
 */ 

global $readonly, $is_nonuser, $is_nonuser_admin, $single_user, $user,
       $REQUIRE_APPROVALS, $PUBLIC_ACCESS, $PUBLIC_ACCESS_OTHERS, $login,
       $ALLOW_VIEW_OTHER, $DISPLAY_TASKS, $thisyear, $thismonth, $thisday,
       $views, $REPORTS_ENABLED, $use_http_auth, $login_return_path,
       $NONUSER_ENABLED, $has_boss, $is_admin, $CATEGORIES_ENABLED,
       $PUBLIC_ACCESS_CAN_ADD, $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL;


//------------------------------------------------------------------//
//    First figure out what options are on and privileges we have   //
//------------------------------------------------------------------//
$can_add = true;
if ( $readonly == 'Y' ) {
  $can_add = false;
} else if ( access_is_enabled () ) {
  $can_add = access_can_access_function ( ACCESS_EVENT_EDIT, $user );
} else {
  if ( $login == '__public__' )
    $can_add = $GLOBALS['PUBLIC_ACCESS_CAN_ADD'] == 'Y';
  if ( $is_nonuser )
    $can_add = false;
}
$unapproved_url = '';
$select_user_url = '';
$search_url = '';
$import_url = '';
$export_url = '';
$new_entry_url = '';
$new_task_url = '';
$help_url = 'help_index.php';
$today_url = 'day.php';
$month_url = 'month.php';
$week_url = 'week.php';
$year_url = 'year.php';

// Add new entry
if ( $can_add ) {
  $new_entry_url = 'edit_entry.php';
  if ( ! empty ( $thisyear ) ) {
    $new_entry_url .= "?year=$thisyear";
    if ( ! empty ( $thismonth ) ) {
      $new_entry_url .= "&amp;month=$thismonth";
    }
    if ( ! empty ( $thisday ) ) {
      $new_entry_url .= "&amp;day=$thisday";
    }
  }
}

// Add new task
if ( $can_add && ! empty ( $DISPLAY_TASKS ) && $DISPLAY_TASKS == "Y") {
  $new_task_url = 'edit_entry.php?eType=task';
  if ( ! empty ( $thisyear ) ) {
    $new_task_url .= "&amp;year=$thisyear";
    if ( ! empty ( $thismonth ) ) {
      $new_task_url .= "&amp;month=$thismonth";
    }
    if ( ! empty ( $thisday ) ) {
      $new_task_url .= "&amp;day=$thisday";
    }
  }
}

if ( $single_user != "Y" ) {
  // Today
  if ( ! empty ( $user ) && $user != $login ) { 
    $today_url .= '?user=' . $user;
    $week_url  .= '?user=' . $user;
    $month_url .= '?user=' . $user;
    $year_url  .= '?user=' . $user;
    if (! empty ($new_entry_url ) ) $new_entry_url .= '&user=' . $user;
    if (! empty ($new_task_url ) )$new_task_url  .= '&user=' . $user;
  }

  // List Unapproved
  if ( $login != "__public__" && ! $is_nonuser &&  $readonly == "N" &&
    ( $REQUIRE_APPROVALS == "Y" || $PUBLIC_ACCESS == "Y" ) ) {
    $unapproved_url = 'list_unapproved.php';
    if ( $is_nonuser_admin ) $unapproved_url .= "?user=" . getValue ( 'user' );
  }
  
  
  // Another User's Calendar
  if ( ( $login == "__public__" && $PUBLIC_ACCESS_OTHERS != "Y" ) ||
    ( $is_nonuser && ! access_is_enabled () ) ) {
    // don't allow them to see other people's calendar
  } else if ( $ALLOW_VIEW_OTHER == "Y" || $is_admin ) {
    // Also, make sure they able to access either day/week/month/year view
    // If not, then there is no way to view another user's calendar except
    // a custom view.
    if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_ANOTHER_CALENDAR, $user ) ) {
      // get count of users this user can see.  if > 1, then...
      $ulist = array_merge ( get_my_users(), get_nonuser_cals () );
      if ( count ( $ulist ) > 1 ) {
        $select_user_url = 'select_user.php'; 
      }
    }
  }
  
}


// only display some links if we're viewing our own calendar.
if ( empty ( $user ) || $user == $login ) {

  // Search
  if ( access_can_access_function ( ACCESS_SEARCH, $user ) ) {
    $search_url = 'search.php';
  }
  
  // Import/Export
  if ( $login != '__public__' && ! $is_nonuser ) {
    if ( access_can_access_function ( ACCESS_IMPORT, $user ) ) {
      $import_url = 'import.php';
    }
    if ( access_can_access_function ( ACCESS_EXPORT, $user ) ) {
      $export_url = 'export.php';
    }
  }
}

// Help
if ( access_is_enabled () ) {
  $showHelp = access_can_access_function ( ACCESS_HELP, $user );
} else {
  $showHelp = ( $login != '__public__' && ! $is_nonuser );
}


// Views
if ( ( access_can_access_function ( ACCESS_VIEW, $user ) && $ALLOW_VIEW_OTHER != "N" )
  && count ( $views ) > 0 ) {
  $views_link = array ();
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    $tmp['name'] = htmlspecialchars ($views[$i]['cal_name']);
    $tmp['url'] = $views[$i]['url'];
    if ( ! empty ( $thisdate ) )
      $tmp['url'] .= "&amp;date=$thisdate";
    $views_link[] = $tmp;
  }
  $tmp = '';
}


// Reports
if ( ! empty ( $REPORTS_ENABLED ) && $REPORTS_ENABLED == 'Y' &&
  access_can_access_function ( ACCESS_REPORT, $user ) ) {
  $reports_link = array ();
  if ( ! empty ( $user ) && $user != $login ) {
    $u_url = "&amp;user=$user";
  } else {
    $u_url = "";
  }
  $res = dbi_execute ( "SELECT cal_report_name, cal_report_id " .
    "FROM webcal_report " .
    "WHERE cal_login = ? OR " .
    "( cal_is_global = 'Y' AND cal_show_in_trailer = 'Y' ) " .
    "ORDER BY cal_report_id", array ( $login ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $tmp['name'] = htmlspecialchars ( $row[0] );
      $tmp['url'] = "report.php?report_id=$row[1]$u_url";
      $reports_link[] = $tmp;
    }
    dbi_free_result ( $res );
  }
  $tmp = '';
}


// Logout/Login URL
if ( ! $use_http_auth ) {
  if ( empty ( $login_return_path ) ) {
    $logout_url = "login.php?action=logout";
    $login_url = "login.php";
  } else {
    $logout_url = "login.php?return_path=$login_return_path&action=logout";
    $login_url = "login.php?return_path=$login_return_path";
  }
  // Should we use another application's login/logout pages?
  if ( substr ( $GLOBALS['user_inc'], 0, 9 ) == 'user-app-' ) {  
    global $app_login_page, $app_logout_page;
    $logout_url = $app_logout_page;
    $login_url = "login-app.php";
    if ( $login_return_path != '' && $app_login_page['return'] != '' ) {
      $login_url .= "?return_path=$login_return_path";
    } 
  }  
}   

// Manage Calendar links
if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == "Y" )
  $admincals = get_nonuser_cals ( $login );
// Make sure they have access to either month/week/day view.
// If they do not, then we cannot create a URL that shows just
// the boss' events.  So, we would not include any of the
// "manage calendar of" links.
$have_boss_url = true;
if ( ! access_can_access_function ( ACCESS_MONTH, $user ) &&
  ! access_can_access_function ( ACCESS_WEEK, $user ) &&
  ! access_can_access_function ( ACCESS_DAY, $user ) )
  $have_boss_url = false;
if ( $have_boss_url && ( $has_boss || ! empty ( $admincals[0] ) ||
  ( $is_admin && $PUBLIC_ACCESS ) ) ) {
  $grouplist = user_get_boss_list ( $login );
  if ( ! empty ( $admincals[0] ) ) {
    $grouplist = array_merge ( $admincals, $grouplist );
  }
  if ( $is_admin && $PUBLIC_ACCESS == 'Y' ) {
    $public = array (
      "cal_login" => "__public__",
      "cal_fullname" => translate ( "Public Access" )
    );
    array_unshift ( $grouplist, $public );
  }
  $groups = "";
  for ( $i = 0; $i < count ( $grouplist ); $i++ ) {
    $l = $grouplist[$i]['cal_login'];
    $f = $grouplist[$i]['cal_fullname'];
    // Use the preferred view if it is day/week/month/year.php.  Try
    // not to use a user-created view because it might not display the
    // proper user's events.  (Fallback to month.php if this is true.)
    // Of course, if this user cannot view any of the standard D/W/M/Y
    // pages, that will force us to use the view.
    $xurl = get_preferred_view ( "", "user=$l" );
    if ( strstr ( $xurl, "view_" ) ) {
      if ( access_can_access_function ( ACCESS_MONTH, $user ) )
        $xurl = "month.php?user=$l";
      else if ( access_can_access_function ( ACCESS_WEEK, $user ) )
        $xurl = "week.php?user=$l";
      else if ( access_can_access_function ( ACCESS_DAY, $user ) )
        $xurl = "day.php?user=$l";
      // year does not show events, so you cannot manage someone's cal
    }
    $tmp['name'] = $f;
    $tmp['url'] = $xurl;
    $groups[] = $tmp;
  }
}

// Help URL
if ( ! access_can_access_function ( ACCESS_HELP, $user ) ) {
  $help_url = false;
}

//------------------------------------------------------------------//
//        Lets make a few functions for printing menu items         //
//------------------------------------------------------------------//
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

// A menu link
function jscMenu_menu ( $title, $url = false ) {
  if ( $url ) {
    echo "\n  [null,'".translate($title)."','$url',null,null],\n";
  } else {
    echo "\n  [null,'".translate($title)."',null,null,null,\n";
  }
}

// Dropdown menu item
function jscMenu_item ( $icon, $title, $url ) {
  echo "    ['<img src=\"includes/menu/icons/$icon\" alt=\"\" />','".
       translate( $title )."','$url',null,''],\n";
}

// Dropdown menu item that has a sub menu
function jscMenu_sub_menu ( $icon, $title ) {
  echo "    ['<img src=\"includes/menu/icons/$icon\" alt=\"\" />','".
       translate( $title )."','',null,'',\n";
}

// Dropdown menu item is custom html
function jscMenu_custom ( $html ) {
  echo "    [_cmNoClick,'$html']\n";
}

// Closing tag
function jscMenu_close () {
  echo "  ],\n";
}

// A divider line
function jscMenu_divider () {
  echo "    _cmSplit,\n";
}

//------------------------------------------------------------------//
//                Now we need to print the menu                     //
//------------------------------------------------------------------//
?>

<table width="100%" class="ThemeMenubar" cellpadding="0" cellspacing="0" border="0">
  <tr>
   <td class="ThemeMenubackgr">
<div id="myMenuID"></div>

<script language="JavaScript" type="text/javascript">
<!-- <![CDATA[
function openHelp () {
  window.open ( "help_index.php", "cal_help","dependent,menubar,scrollbars,height=500,width=600,innerHeight=520,outerWidth=620" );
}

var myMenu =
[
<?php

  // My Calendar Menu
  jscMenu_menu ('My Calendar');
    jscMenu_item ( 'home.png', 'Home', 'index.php' );
    if ( $today_url != '' ) jscMenu_item ( 'today.png', 'Today', $today_url );
    jscMenu_item ( 'week.png', 'This Week', $week_url );
    jscMenu_item ( 'month.png', 'This Month', $month_url );
    jscMenu_item ( 'year.png', 'This Year', $year_url );
  jscMenu_close();
  
  
  // Events Menu  
  jscMenu_menu ('Events');
    if ( $new_entry_url != '' ) jscMenu_item ( 'add.png', 'Add New Event', $new_entry_url );
    if ( $new_task_url != '' ) jscMenu_item ( 'newtodo.png', 'Add New Task', $new_task_url );
    if ( $is_admin ) jscMenu_item ( 'delete.png', 'Delete Events', 'purge.php' );
    if ( $unapproved_url != '' ) jscMenu_item ( 'unapproved.png', 'Unapproved Events', $unapproved_url );
    if ( $export_url != '' ) jscMenu_item ( 'up.png', 'Export', $export_url );
    if ( $import_url != '' ) jscMenu_item ( 'down.png', 'Import', $import_url );
  jscMenu_close();


  // Views Menu
  jscMenu_menu ('Views');
    if ( $select_user_url != '' ) jscMenu_item ( 'display.png', "Another User\'s Calendar", $select_user_url );

    if ( $login != '__public__' ) {
      if ( ! empty ( $views_link ) && count ( $views_link ) > 0 ) { 
        jscMenu_sub_menu ( 'views.png', 'My Views' );
        for ( $i = 0; $i < count ( $views_link ); $i++ ) {
          jscMenu_item ( 'views.png', $views_link[$i]['name'], $views_link[$i]['url'] );
        }
        jscMenu_close();
      }
      
      if ( ! empty ( $groups ) ) {
        jscMenu_sub_menu ( 'manage_cal.png', 'Manage Calendar of' ); 
        for ( $i = 0; $i < count ( $groups ); $i++ ) {
          jscMenu_item ( 'display.png', $groups[$i]['name'], $groups[$i]['url'] );
        }
        jscMenu_close();
      }    
      
      if ( ! access_is_enabled () || access_can_access_function ( ACCESS_VIEW_MANAGEMENT, $user ) ) {    
        jscMenu_divider();
        jscMenu_item ( 'manage_views.png', 'Manage Views', 'views.php' );
      }
    }
  jscMenu_close();


  // Reports Menu
  if ( $login != '__public__' ) {
  jscMenu_menu ('Reports');
    if ( $is_admin && ( ! access_is_enabled () || 
      access_can_access_function ( ACCESS_ACTIVITY_LOG, $user ) ) )
      jscMenu_item ( 'log.png', 'Activity Log', 'activity_log.php' );

    if ( ! empty ( $reports_link ) && count ( $reports_link ) > 0 ) {
      jscMenu_sub_menu ( 'reports.png', 'My Reports' );
      for ( $i = 0; $i < count ( $reports_link ); $i++ ) { 
        jscMenu_item ( 'document.png', $reports_link[$i]['name'], $reports_link[$i]['url'] );
      }
      jscMenu_close();
    }    

    if ( $REPORTS_ENABLED == 'Y' && 
      ( ! access_is_enabled () || access_can_access_function ( ACCESS_REPORT, $user ) ) ) {
      jscMenu_divider();
      jscMenu_item ( 'manage_reports.png', 'Manage Reports', 'report.php' );
    }
  jscMenu_close();
  }
  

  // Settings Menu
  if ( $login != '__public__' ) {
  jscMenu_menu ('Settings');  

    // Nonuser Admin Settings
    if ( $is_nonuser_admin ) {
      if ( $single_user != 'Y' ) {
        if ( ! access_is_enabled () || access_can_access_function ( ACCESS_ASSISTANTS, $user ) ) { 
          jscMenu_item ( 'users.png', 'Assistants', "assistant_edit.php?user=$user" );
        }
      }
      if ( ! access_is_enabled () || access_can_access_function ( ACCESS_PREFERENCES, $user ) ) {
        jscMenu_item ( 'settings.png', 'Preferences', "pref.php?user=$user" );
      }
  
    // Normal User Settings  
    } else {

      if ( $single_user != 'Y' ) {
        if ( ! access_is_enabled () || access_can_access_function ( ACCESS_ASSISTANTS, $user ) )
          jscMenu_item ( 'users.png', 'Assistants', 'assistant_edit.php' );
      }

      if ( $CATEGORIES_ENABLED == 'Y' ) {
        if ( ! access_is_enabled () || access_can_access_function ( ACCESS_CATEGORY_MANAGEMENT, $user ) )
          jscMenu_item ( 'folder.png', 'Categories', 'category.php' );
      }  

      if ( ! access_is_enabled () || access_can_access_function ( ACCESS_LAYERS, $user ) ) {
        jscMenu_item ( 'layers.png', 'Layers', 'layers.php' );
      }

      if ( ! $is_admin ) {
        jscMenu_item ( 'profile.png', 'My Profile', 'users.php' );
      }

      if ( ! access_is_enabled () || access_can_access_function ( ACCESS_PREFERENCES, $user ) ) {
        jscMenu_item ( 'settings.png', 'Preferences', 'pref.php' );
      }

      if ( $is_admin && ! empty ($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y' ) {
        jscMenu_sub_menu ( 'public.png', 'Public Calendar' );
        jscMenu_item ( 'settings.png', 'Preferences', 'pref.php?public=1' );
        if ( $PUBLIC_ACCESS_CAN_ADD == 'Y' && $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y' ) {
          jscMenu_item ( 'unapproved.png', 'Unapproved Events', 'list_unapproved.php?user=__public__' );
        }
        jscMenu_close();
      }


      if ( ( $is_admin && ! access_is_enabled () ) || access_can_access_function ( ACCESS_SYSTEM_SETTINGS, $user ) ) {
        jscMenu_item ( 'config.png', 'System Settings', 'admin.php' );
      }

      if ( $is_admin ) {
        if ( access_is_enabled () ) {
          jscMenu_item ( 'access.png', 'User Access Control', 'access.php' );
        }
        jscMenu_item ( 'user.png', 'User Manager', 'users.php' );
      }
    }  
  jscMenu_close();
  }

  // Search Menu
  if ( $search_url != '' ) {
    jscMenu_menu ('Search');
    jscMenu_item ( 'search.png', 'Advanced Search', 'search.php' );
    jscMenu_divider();
    jscMenu_custom('<td class="ThemeMenuItemLeft"><img src="includes/menu/icons/spacer.gif" /></td><td colspan="2"><form action="search_handler.php" method="post"><input type="text" name="keywords" size="25" /><input type="submit" value="Search" /></form></td>');
    jscMenu_close();
  }

  // Help Menu (Link)
  if ( $help_url != '' ) jscMenu_menu ('Help','javascript:openHelp()');
?>  
];
cmDraw ('myMenuID', myMenu, 'hbr', cmTheme, 'Theme');
//]]> -->
</script>
</td>
<td class="ThemeMenubackgr" align="right">
<?php print_menu_dates ( true ); ?>
<td>
<td class="ThemeMenubackgr" align="right">
<?php
if ( ! empty ( $logout_url ) ) { //using http_auth
  if ( strlen ( $login ) && $login != "__public__" ) {
    echo "<a title=\"" . 
      translate("Logout") . "\" href=\"$logout_url\">" . 
      translate("Logout") . "</a>: $login\n";
    } else {
    // For public user
    echo "<a title=\"" . 
      translate("Login") . "\" href=\"$login_url\">" . 
      translate("Login") . "</a>\n";
  }
} else {
  echo "&nbsp;&nbsp;&nbsp;";  //TODO replace with something???
}
?>
 &nbsp;</td>
</tr></table>
