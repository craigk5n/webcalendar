<?php
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

// NOTE: This file is included within the print_trailer function found
// in includes/init.php.  If you add a global variable somewhere in this
// file, be sure to declare it global in the print_trailer function
// or use $GLOBALS[].
$tret = '';
if ( access_can_access_function ( ACCESS_TRAILER ) ) { 

$tret .= '<div id="trailer">';
 
$tret .= '<div id="menu">' . "\n";

$goto_link = array ( );
$views_link = array ( );
$reports_link = array ( );
$manage_calendar_link = array ( );

$myCalStr = translate( 'My Calendar' );
$todayStr = translate( 'Today' );
$adminStr = translate( 'Admin' );
$unapprovedStr = translate( 'Unapproved Entries' );
$searchStr = translate( 'Search' );
$importStr = translate( 'Import' );
$exportStr = translate( 'Export' );
$addNewEntryStr = translate( 'Add New Entry' );
$addNewTaskStr = translate( 'Add New Task' );
$loginStr = translate( 'Login' );
$logoutStr = translate( 'Logout' );
$currentUserStr = translate( 'Current User' );
$helpStr = translate( 'Help' );
$publicStr = $PUBLIC_ACCESS_FULLNAME;

// Go To links
$can_add = true;
if ( $readonly == 'Y' )
  $can_add = false;
else if ( access_is_enabled () )
  $can_add = access_can_access_function ( ACCESS_EVENT_EDIT );
else {
  if ( $login == '__public__' )
    $can_add = $GLOBALS['PUBLIC_ACCESS_CAN_ADD'] == 'Y';
  if ( $is_nonuser )
    $can_add = false;
}

// get HOME URL and text 
if ( ! empty ( $GLOBALS['HOME_LINK'] ) ) {
  $home = $GLOBALS['HOME_LINK'];
  $homeStr = translate( 'Home' );
  $goto_link[] = '<a title="' . $homeStr . '" class="bold" ' 
    . "href=\"$home\">$homeStr" . '</a>';
}


if ( ! empty ( $GLOBALS['STARTVIEW'] ) ) {
  $mycal = $GLOBALS['STARTVIEW'];
} else {
  $mycal = "index.php";
}

// calc URL to today
$todayURL = 'month.php';
$reqURI = 'month.php';
if ( ! empty ( $GLOBALS['SCRIPT_NAME'] ) ) {
  $reqURI = $GLOBALS['SCRIPT_NAME'];
} else if ( ! empty ( $_SERVER['SCRIPT_NAME'] ) ) {
  $reqURI = $_SERVER['SCRIPT_NAME'];
}
if ( ! strstr ( $reqURI, 'month.php' ) &&
   ! strstr ( $reqURI, 'week.php' ) &&
   ! strstr ( $reqURI, 'day.php' ) ) {
  $todayURL = 'day.php';
} else {
  $todayURL = $reqURI;
}
if ( ! access_can_view_page ( $todayURL ) ) {
  $todayURL = '';
}

if ( $single_user != 'Y' ) {
  if ( ! empty ( $user ) && $user != $login ) {
    $goto_link[] = '<a title="' . $myCalStr . '" class="bold" ' 
      . "href=\"$mycal\">" . translate( 'Back to My Calendar' ) . '</a>';
  } else {
    $goto_link[] = '<a title="' . 
      $myCalStr . '" class="bold" ' 
        . "href=\"$mycal\">$myCalStr" . '</a>';
  }
  if ( ! empty ( $user ) && $user != $login && ! empty ( $todayURL ) ) {
    $todayURL .= '?user=' . $user;
  }
  if ( ! empty ( $todayURL ) ) {
    $goto_link[] = '<a title="' . $todayStr . '" class="bold" ' 
      . "href=\"$todayURL\">$todayStr" . '</a>';
  }
  if ( $login != '__public__' && ! $is_nonuser && $readonly == 'N' ) {
    if ( ! access_is_enabled () || access_can_access_function ( ACCESS_ADMIN_HOME ) ||
    access_can_access_function ( ACCESS_PREFERENCES )) {
      $url = 'adminhome.php';
      if ($is_nonuser_admin) $url .= "?user=$user";
      $goto_link[] = '<a title="' . $adminStr. '" class="bold" ' 
        . "href=\"$url\">$adminStr" . '</a>';
    }
  }
  if ( $login != '__public__' && ! $is_nonuser &&  $readonly == 'N' &&
    ( $REQUIRE_APPROVALS == 'Y' || $PUBLIC_ACCESS == 'Y' ) ) {
    $url = 'list_unapproved.php';
    if ( $is_nonuser_admin ) {
      $url .= "?user=" . getValue ( 'user' );
    }
    $goto_link[] = '<a title="' . $unapprovedStr 
      . "\" href=\"$url\">$unapprovedStr" . '</a>';
  }
  if ( ( $login == '__public__' && $PUBLIC_ACCESS_OTHERS != 'Y' ) ||
    ( $is_nonuser && ! access_is_enabled () ) ) {
    // don't allow them to see other people's calendar
  } else if ( $ALLOW_VIEW_OTHER == 'Y' || $is_admin ) {
    // Also, make sure they able to access either day/week/month/year view
    // If not, then there is no way to view another user's calendar except
    // a custom view.
    if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_ANOTHER_CALENDAR ) ) {
      // get count of users this user can see.  if > 1, then...
      $ulist = array_merge ( get_my_users(), get_my_nonusers ( $login , true ) );
      if ( count ( $ulist ) > 1 ) {
        $calStr = translate ( 'Another User&#39;s Calendar' );
        $goto_link[] = '<a title="' .  $calStr 
          . '" href="select_user.php">' . $calStr . '</a>';
      }
    }
  }
} else {
  $goto_link[] = '<a title="' . $myCalStr . '" class="bold" ' 
    . "href=\"$mycal\">$myCalStr" . '</a>';
  $goto_link[] = '<a title="' . $todayStr . '" class="bold" ' 
    . "href=\"$todayURL\">$todayStr" . '</a>';
  if ( $readonly == 'N' ) {
    $goto_link[] = '<a title="' .  $adminStr . '" class="bold" ' 
      . 'href="adminhome.php">' . $adminStr . '</a>';
  }
}
// only display some links if we're viewing our own calendar.
if ( empty ( $user ) || $user == $login ) {
  if ( access_can_access_function ( ACCESS_SEARCH ) ) {
    $goto_link[] = '<a title="' . 
      $searchStr . '" href="search.php">' .
      $searchStr . '</a>';
  }
  if ( $login != '__public__' && ! $is_nonuser
    && $readonly != 'Y' ) {
    if ( access_can_access_function ( ACCESS_IMPORT ) ) {
      $goto_link[] = '<a title="' . 
        $importStr . '" href="import.php">' . 
        $importStr . '</a>';
    }
    if ( access_can_access_function ( ACCESS_EXPORT ) ) {
      $goto_link[] = '<a title="' . 
        $exportStr . '" href="export.php">' . 
        $exportStr . '</a>';
    }
  }
  if ( $can_add ) {
    $url = '<a title="' . 
      $addNewEntryStr . '" href="edit_entry.php';
    if ( ! empty ( $thisyear ) ) {
      $url .= "?year=$thisyear";
      if ( ! empty ( $thismonth ) ) {
        $url .= "&amp;month=$thismonth";
      }
      if ( ! empty ( $thisday ) ) {
        $url .= "&amp;day=$thisday";
      }
    }
    $url .= '">' . $addNewEntryStr . '</a>';
    $goto_link[] = $url;
  }
  if ( $can_add && ( $DISPLAY_TASKS_IN_GRID == 'Y' || $DISPLAY_TASKS == 'Y' ) ) {
    $url = '<a title="' . 
      $addNewTaskStr . '" href="edit_entry.php?eType=task';
    if ( ! empty ( $thisyear ) ) {
      $url .= "&amp;year=$thisyear";
      if ( ! empty ( $thismonth ) ) {
        $url .= "&amp;month=$thismonth";
      }
      if ( ! empty ( $thisday ) ) {
        $url .= "&amp;day=$thisday";
      }
    }
    $url .= '">' . $addNewTaskStr . '</a>';
    $goto_link[] = $url;
  }
}
if ( access_is_enabled () ) {
  $showHelp = access_can_access_function ( ACCESS_HELP );
} else {
  $showHelp = ( $login != '__public__' && ! $is_nonuser );
}
if ( $showHelp ) {
  $goto_link[] = '<a title="' . 
    $helpStr . '" href="#" onclick="window.open ' .
    "( 'help_index.php', 'cal_help', 'dependent,menubar,scrollbars, " .
    "height=500,width=600,innerHeight=520,outerWidth=620' ); \" " .
    "onmouseover=\"window.status=''; return true\">" .
    $helpStr . '</a>';
}

if ( count ( $goto_link ) > 0 ) {
 $tret .= '<span class="prefix">' .translate( 'Go to' ) . ':</span>' . "\n";
  $gotocnt = count ( $goto_link );
  for ( $i = 0; $i < $gotocnt; $i++ ) {
    if ( $i > 0 )
      $tret .= ' | ';
    $tret .= $goto_link[$i] . "\n";
  }
}

$tret .= '<!-- VIEWS -->' . "\n";

$viewcnt = count ( $views );
if ( ( access_can_access_function ( ACCESS_VIEW ) && $ALLOW_VIEW_OTHER != 'N' )
  && $viewcnt > 0 ) {
  for ( $i = 0; $i < $viewcnt; $i++ ) {
    $out = '<a title="' .
      htmlspecialchars ( $views[$i]['cal_name'] ) .
      '" href="';
    $out .= $views[$i]['url'];
    if ( ! empty ( $thisdate ) )
      $out .= "&amp;date=$thisdate";
    $out .= '">' .
      htmlspecialchars ( $views[$i]['cal_name'] ) . "</a>\n";
    $views_link[] = $out;
  }
}
$views_linkcnt = count ( $views_link );
if ( $views_linkcnt > 0 ) { 
  $tret .= '<br /><span class="prefix">' . translate( 'Views' ) . ':</span>&nbsp;' . "\n";
  for ( $i = 0; $i < $views_linkcnt; $i++ ) {
    if ( $i > 0 )
      $tret .= ' | ';
    $tret .= $views_link[$i];
  }
}


$tret .= '<!-- REPORTS -->' . "\n";

if ( ! empty ( $REPORTS_ENABLED ) && $REPORTS_ENABLED == 'Y' &&
  access_can_access_function ( ACCESS_REPORT ) ) {
$reports_link = array ();
  if ( ! empty ( $user ) && $user != $login ) {
    $u_url = "&amp;user=$user";
  } else {
    $u_url = '';
  }
  $rows = dbi_get_cached_rows ( 'SELECT cal_report_name, cal_report_id ' .
    'FROM webcal_report ' .
    'WHERE cal_login = ? OR ' .
    "( cal_is_global = 'Y' AND cal_show_in_trailer = 'Y' ) " .
    'ORDER BY cal_report_id', array( $login ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $reports_link[] = '<a title="' . 
        htmlspecialchars ( $row[0] ) . 
        "\" href=\"report.php?report_id=$row[1]$u_url\">" . 
        htmlspecialchars ( $row[0] ) . '</a>';
    }
  }
  $reports_linkcnt = count ( $reports_link );
  if ( $reports_linkcnt  > 0 ) {
    $tret .= '<br /><span class="prefix">' . 
      translate( 'Reports' ) . ':</span>&nbsp;' . "\n";
    for ( $i = 0; $i < $reports_linkcnt ; $i++ ) {
      if ( $i > 0 )
        $tret .= ' | ';
      $tret .= $reports_link[$i] . "\n";
    }
  }
}

$tret .= '<!-- CURRENT USER -->' . "\n";

if ( ! $use_http_auth ) {
 if ( empty ( $login_return_path ) ) {
  $logout_url = 'login.php?action=logout';
  $login_url = 'login.php';
 } else {
  $logout_url = "login.php?return_path=$login_return_path&action=logout";
  $login_url = "login.php?return_path=$login_return_path";
 }

  // Should we use another application's login/logout pages?
  if ( substr ( $GLOBALS['user_inc'], 0, 9 ) == 'user-app-' ) {  
    global $app_login_page, $app_logout_page;
    $logout_url = $app_logout_page;
    $login_url = 'login-app.php';
    if ( $login_return_path != '' && $app_login_page['return'] != '' ) {
      $login_url .= "?return_path=$login_return_path";
    } 
  }  
    
  if ( $readonly != 'Y' ) {
    if ( strlen ( $login ) && $login != '__public__' ) {
     $tret .= '<br /><span class="prefix">' .
      $currentUserStr . ":</span>&nbsp;$fullname&nbsp;(<a title=\"" . 
      $logoutStr . "\" href=\"$logout_url\">" . 
      $logoutStr . "</a>)\n";
    } else {
     // For public user (who did not actually login)
     $tret .= '<br /><span class="prefix">' .
      $currentUserStr . ':</span>&nbsp;' . 
      $publicStr . '&nbsp;(<a title="' . 
      $loginStr . "\" href=\"$login_url\">" . 
      $loginStr . "</a>)\n";
    }
  }
}

// Manage Calendar links
if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' )
  $admincals = get_nonuser_cals ( $login );
// Make sure they have access to either month/week/day view.
// If they do not, then we cannot create a URL that shows just
// the boss' events.  So, we would not include any of the
// "manage calendar of" links.
$have_boss_url = true;
if ( ! access_can_access_function ( ACCESS_MONTH ) &&
  ! access_can_access_function ( ACCESS_WEEK ) &&
  ! access_can_access_function ( ACCESS_DAY ) )
  $have_boss_url = false;
if ( $have_boss_url && ( $has_boss || ! empty ( $admincals[0] ) ||
  ( $is_admin && $PUBLIC_ACCESS ) ) ) {
  $grouplist = user_get_boss_list ( $login );
  if ( ! empty ( $admincals[0] ) ) {
    $grouplist = array_merge ( $admincals, $grouplist );
  }
  if ( $is_admin && $PUBLIC_ACCESS == 'Y' ) {
    $public = array (
      'cal_login' => '__public__',
      'cal_fullname' => $publicStr
    );
    array_unshift ( $grouplist, $public );
  }
  $groups = '';
  for ( $i = 0, $cnt = count ( $grouplist ); $i < $cnt; $i++ ) {
    $l = $grouplist[$i]['cal_login'];
    $f = $grouplist[$i]['cal_fullname'];
    //don't display current $user in group list
    if ( ! empty ( $user ) && $user == $l ) {
       continue;
    }
    // Use the preferred view if it is day/week/month/year.php.  Try
    // not to use a user-created view because it might not display the
    // proper user's events.  (Fallback to month.php if this is true.)
    // Of course, if this user cannot view any of the standard D/W/M/Y
    // pages, that will force us to use the view.
    $xurl = get_preferred_view ( '', "user=$l" );
    if ( strstr ( $xurl, 'view_' ) ) {
      if ( access_can_access_function ( ACCESS_MONTH ) )
        $xurl = "month.php?user=$l";
      else if ( access_can_access_function ( ACCESS_WEEK ) )
        $xurl = "week.php?user=$l";
      else if ( access_can_access_function ( ACCESS_DAY ) )
        $xurl = "day.php?user=$l";
      // year does not show events, so you cannot manage someone's cal
    }
    if ( $i > 0 && $groups != '' )
      $groups .= ", \n";
    $groups .= "<a title=\"$f\" href=\"$xurl\">$f</a>";
  }
  if ( ! empty ( $groups ) ) {
    $tret .= '<br /><span class="prefix">';
    $tret .= translate ( 'Manage calendar of' );
    $tret .= ':</span>&nbsp;' . $groups;
  }
}

// WebCalendar Info...
$tret .= '<br /><br />' . "\n" . '<a title="' . $GLOBALS['PROGRAM_NAME'] . '" ' .
  'id="programname" href="' . $GLOBALS['PROGRAM_URL'] . '" target="_blank">' .
  $GLOBALS['PROGRAM_NAME'] . "</a>\n";

$tret .= '</div></div>' . "\n";

$tret .= '<!-- /TRAILER -->' . "\n";
 } 
  $tret .= '<!-- Db queries: ' . dbi_num_queries () .
  '   Cached queries: ' . dbi_num_cached_queries () . " -->\n";
if ( dbi_get_debug() ) { 
  $tret .= '<blockquote style="border: 1px solid #ccc; background-color: #eee;">' . "\n";
  $tret .= '<b>Executed queries:' .dbi_num_queries ();
  $tret .= '&nbsp;&nbsp; <b>Cached queries:</b>' . dbi_num_cached_queries ();
  $tret .= "<br/><ol>\n";
  $log = $GLOBALS['SQLLOG'];
  //$log=0;
  $logcnt = count ( $log );
  for ( $i = 0; $i < $logcnt; $i++ ) {
    $tret .= '<li>' . $log[$i] . '</li>';
  }
  $tret .= "</ol>\n</blockquote>\n";
}
?>
