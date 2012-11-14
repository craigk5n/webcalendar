<?php
/*
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$Id$
 * @package WebCalendar
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

// NOTE: This file is included by the print_trailer function in "includes/init.php".
// If you add a global variable somewhere in this file,
// be sure to declare it global in the print_trailer function or use $GLOBALS[].
$tret = '';
if ( access_can_access_function ( ACCESS_TRAILER ) ) {
  $tret .= '
    <div id="trailer">
      <div id="menu">';

  $goto_link = $manage_calendar_link = $reports_link = $views_link = array();

  $addNewEntryStr= translate ( 'Add New Entry' );
  $addNewTaskStr = translate ( 'Add New Task' );
  $currentUserStr= translate ( 'Current User_' );
  $importStr     = translate ( 'Import' );
  $loginStr      = translate ( 'Login' );
  $logoutStr     = translate ( 'Logout' );
  $myCalStr      = translate ( 'My Calendar' );
  $todayStr      = translate ( 'Today' );
  $unapprovedStr = translate ( 'Unapproved Entries' );
  $publicStr = $PUBLIC_ACCESS_FULLNAME;
  if ( empty ( $readonly ) || $readonly != 'Y' )
    $readonly = 'N';
  // Go To links.
  $can_add = true;
  if ( $readonly == 'Y' )
    $can_add = false;
  elseif( access_is_enabled() )
    $can_add = access_can_access_function ( ACCESS_EVENT_EDIT );
  else {
    if ( $login == '__public__' )
      $can_add = ( $GLOBALS['PUBLIC_ACCESS_CAN_ADD'] == 'Y' );

    if ( $is_nonuser )
      $can_add = false;
  }

  // Get HOME URL and text.
  if ( ! empty ( $GLOBALS['HOME_LINK'] ) ) {
    $goto_link[] = 'href=" ' . $GLOBALS['HOME_LINK'] .'" class="bold">'
     . translate( 'Home' );
  }

  $mycal = ( empty ( $GLOBALS['STARTVIEW'] )
    ? 'index.php' : $GLOBALS['STARTVIEW'] );
  $mycal .= ( strpos( $mycal, '.php' ) ? '' : '.php' );

  // Calc URL to today.
  $reqURI = 'month.php';
  if ( ! empty ( $GLOBALS['SCRIPT_NAME'] ) )
    $reqURI = $GLOBALS['SCRIPT_NAME'];
  else
  if ( ! empty ( $_SERVER['SCRIPT_NAME'] ) )
    $reqURI = $_SERVER['SCRIPT_NAME'];

  $todayURL = ( ! strpos ( '
day.php
month.php
week.php', $reqURI ) ? 'day.php' : $reqURI );

  if ( ! access_can_view_page ( $todayURL ) )
    $todayURL = '';

  if ( $single_user != 'Y' ) {
    $goto_link[] = 'href="' . $mycal . '" class="bold" title="'
     . $myCalStr . '">' . ( ! empty( $user ) && $user != $login
       ? translate( 'Back to My Calendar' ) : $myCalStr );

    if ( ! empty ( $todayURL ) ) {
      if ( ! empty ( $user ) && $user != $login )
        $todayURL .= '?user=' . $user;

      $goto_link[] = 'href="' . $todayURL . '" class="bold">' . $todayStr;
    }
    if ( $login != '__public__' ) {
      if ( ! $is_nonuser && $readonly == 'N' ) {
        if( ( ! access_is_enabled()
            || access_can_access_function( ACCESS_ADMIN_HOME )
            || access_can_access_function( ACCESS_PREFERENCES ) ) )
          $goto_link[] = 'href="adminhome.php'
           . ( $is_nonuser_admin ? '?user=' . $user : '' )
           . '" class="bold">' . $adminStr;

        if ( $REQUIRE_APPROVALS == 'Y' || $PUBLIC_ACCESS == 'Y' )
          $goto_link[] = 'href="list_unapproved.php'
           . ( $is_nonuser_admin ? '?user=' . getValue ( 'user' ) : '' )
           . "\">$unapprovedStr"';
      }
    } elseif( $PUBLIC_ACCESS_OTHERS != 'Y'
        || ( $is_nonuser && ! access_is_enabled() ) ) {
      // Don't allow them to see other people's calendar.
    } elseif ( ( $ALLOW_VIEW_OTHER == 'Y' || $is_admin )
        // Also, make sure they able to access either day/week/month/year view.
        // If not, the only way to view another user's calendar is a custom view.
        && ( ! access_is_enabled()
          || access_can_access_function( ACCESS_ANOTHER_CALENDAR ) ) ) {
      // Get count of users this user can see. If > 1, then...
      $ulist = array_merge( get_my_users(), get_my_nonusers( $login, true ) );
      if ( count ( $ulist ) > 1 ) {
        $goto_link[] = 'href="select_user.php">'
         . translate( 'Another Users Calendar' );
      }
    }
  } else {
    $goto_link[] = 'href="' . $mycal . '" class="bold">' . $myCalStr;
    $goto_link[] = 'href="' . $todayURL . '" class="bold">' . $todayStr;

    if ( $readonly == 'N' )
      $goto_link[] = 'href="adminhome.php" class="bold">' . $adminStr;
  }
  // Only display some links if we're viewing our own calendar.
  if ( empty ( $user ) || $user == $login ) {
    if ( access_can_access_function ( ACCESS_SEARCH ) )
      $goto_link[] = 'href="search.php">' . translate( 'Search_' );

    if ( $login != '__public__' && ! $is_nonuser && $readonly != 'Y' ) {
      if ( access_can_access_function ( ACCESS_IMPORT ) )
        $goto_link[] = 'href="import.php">' . $importStr;

      if ( access_can_access_function ( ACCESS_EXPORT ) )
        $goto_link[] = 'href="export.php">' . translate( 'Export' );
    }
    if ( $can_add ) {
      if ( ! empty ( $thisyear ) )
        $tmpYrStr = 'year=' . $thisyear
         . ( empty( $thismonth ) ? '' : '&amp;month=' . $thismonth )
         . ( empty( $thisday ) ? '' : '&amp;day=' . $thisday );

      $goto_link[] = 'href="edit_entry.php'
       . ( empty( $thisyear ) ? '' : '?' . $tmpYrStr ) . '">' . $addNewEntryStr;

      if ( $DISPLAY_TASKS_IN_GRID == 'Y' || $DISPLAY_TASKS == 'Y' )
        $goto_link[] = 'href="edit_entry.php?eType=task'
         . ( empty( $thisyear ) ? '' : '&amp;' . $tmpYrStr )
         . '">' . $addNewTaskStr;
    }
  }
  $showHelp = ( access_is_enabled()
    ? access_can_access_function ( ACCESS_HELP )
    : ( $login != '__public__' && ! $is_nonuser ) );

  if ( $showHelp )
    $goto_link[] = 'id="openHelp">' . $helpStr;

  if ( count ( $goto_link ) > 0 ) {
    $tret .= '
        <span class="prefix">' . translate( 'Go to' ) . '</span>';
    for ( $i = 0, $cnt = count ( $goto_link ); $i < $cnt; $i++ ) {
      $tret .= ( $i > 0 ? ' | ' : '' ) . '
        <a ' . $goto_link[$i] . '</a>';
    }
  }

  $tret .= '
<!-- VIEWS -->';

  if ( ( access_can_access_function ( ACCESS_VIEW ) && $ALLOW_VIEW_OTHER != 'N' )
      && count ( $views ) > 0 ) {
    foreach ( $views as $i ) {
      $views_link[] = 'href="' . $i['url']
       . ( empty ( $thisdate ) ? '' : '&amp;date=' . $thisdate )
       . '">' . htmlspecialchars ( $i['cal_name'] );
    }
  }
  if ( count ( $views_link ) > 0 ) {
    $tret .= '<br>
        <span class="prefix">' . translate( 'Views_' ) . '</span>&nbsp;';
    for ( $i = 0, $cnt = count ( $views_link ); $i < $cnt; $i++ ) {
      $tret .= ( $i > 0 ? ' | ' : '' ) . '
        <a ' . $views_link[$i] . '</a>';
    }
  }

  $tret .= '
<!-- REPORTS -->';

  if ( ! empty ( $REPORTS_ENABLED ) && $REPORTS_ENABLED == 'Y' &&
      access_can_access_function ( ACCESS_REPORT ) ) {
    $reports_link = array();
    $rows = dbi_get_cached_rows ( 'SELECT cal_report_name, cal_report_id
      FROM webcal_report WHERE cal_login = ? OR ( cal_is_global = \'Y\'
      AND cal_show_in_trailer = \'Y\' ) ORDER BY cal_report_id',
      array ( $login ) );
    if ( $rows ) {
      foreach ( $rows as $row ) {
        $reports_link[] = 'href="report.php?report_id=' . $row[1]
         . ( ! empty ( $user ) && $user != $login ? '&amp;user=' . $user : '' )
         . '">' . htmlspecialchars( $row[0] );
      }
    }
    if ( count ( $reports_link ) > 0 ) {
      $tret .= '<br>
        <span class="prefix">' . translate( 'Reports_' ) . '</span>&nbsp;';
      for ( $i = 0, $cnt = count ( $reports_link ); $i < $cnt; $i++ ) {
        $tret .= ( $i > 0 ? ' | ' : '' ) . '
        <a ' . $reports_link[$i] . '</a>';
      }
    }
  }

  $tret .= '
<!-- CURRENT USER -->';

  if ( ! $use_http_auth ) {
    $login_url = $logout_url = 'login.php';
    if ( empty ( $login_return_path ) )
      $logout_url .= '?action=logout';
    else {
      $login_url .= '?return_path=' . $login_return_path;
      $logout_url .= $login_url . '&action=logout';
    }

    // Should we use another application's login/logout pages?
    if ( substr ( $GLOBALS['user_inc'], 0, 9 ) == 'user-app-' ) {
      global $app_login_page, $app_logout_page;
      $logout_url = $app_logout_page;
      $login_url = 'login-app.php'
       . ( $login_return_path != '' && $app_login_page['return'] != ''
        ? '?return_path=' . $login_return_path : '' );
    }

    if ( $readonly != 'Y' )
      $tret .= '<br>
        <span class="prefix">' . $currentUserStr . '</span>&nbsp;'
       . ( strlen ( $login ) && $login != '__public__'
        ? $fullname . '&nbsp;(<a href="' . $logout_url . '">' . $logoutStr
        : // For public user (who did not actually login).
        $publicStr . '&nbsp;(<a href="' . $login_url . '">' . $loginStr )
       . "</a>)\n";
  }

  // Manage Calendar links.
  if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y' )
    $admincals = get_nonuser_cals ( $login );
  // Make sure they have access to at least one of month/week/day view.
  // If they do not, then we cannot create a URL that shows just the boss' events.
  // So, we would not include any of the "manage calendar of" links.
  $have_boss_url = true;
  if ( ! access_can_access_function ( ACCESS_MONTH ) && !
      access_can_access_function ( ACCESS_WEEK ) && !
      access_can_access_function ( ACCESS_DAY ) )
    $have_boss_url = false;
  if ( $have_boss_url && ( $has_boss || ! empty ( $admincals[0] ) ||
        ( $is_admin && $PUBLIC_ACCESS ) ) ) {
    $grouplist = user_get_boss_list ( $login );
    if ( ! empty ( $admincals[0] ) )
      $grouplist = array_merge ( $admincals, $grouplist );

    if ( $is_admin && $PUBLIC_ACCESS == 'Y' ) {
      $public = array (
        'cal_login'   => '__public__',
        'cal_fullname'=> $publicStr
        );
      array_unshift ( $grouplist, $public );
    }
    $groups = '';
    for ( $i = 0, $cnt = count ( $grouplist ); $i < $cnt; $i++ ) {
      $l = $grouplist[$i]['cal_login'];
      $f = $grouplist[$i]['cal_fullname'];
      // don't display current $user in group list
      if ( ! empty ( $user ) && $user == $l )
        continue;

      // Use the preferred view if it is day/week/month/year.php. Try not to
      // use a user-created view because it might not display the proper user's
      // events. (Fallback to month.php if this is true.)  Of course, if this
      // user cannot view any of the standard D/W/M/Y pages, that will force us
      // to use the view.
      $xurl = get_preferred_view ( '', 'user=' . $l );
      if ( strpos ( ' ' . $xurl, 'view_' ) ) {
        if ( access_can_access_function ( ACCESS_MONTH ) )
          $xurl = 'month.php?user=' . $l;
        elseif ( access_can_access_function ( ACCESS_WEEK ) )
          $xurl = 'week.php?user=' . $l;
        elseif ( access_can_access_function ( ACCESS_DAY ) )
          $xurl = 'day.php?user=' . $l;
        // Year does not show events, so there is nothing to manage there.
      }
      $groups .= ( $i > 0 && $groups != '' ? ',' : '' ) . '
        <a href="' . "$xurl\">$f".'</a>';
    }
    if ( ! empty ( $groups ) )
      $tret .= '<br>
        <span class="prefix">'
       . translate( 'Manage calendar of' ) . '</span>&nbsp;' . $groups;
  }

  // WebCalendar Info...
  $tret .= '<br><br>
        <a href="' . $GLOBALS['PROGRAM_URL'] . '" id="programname">'
   . $GLOBALS['PROGRAM_NAME'] . '</a>
      </div>
    </div>
<!-- /TRAILER -->';
}
$tret .= '
<!-- Db queries: ' . dbi_num_queries() . '   Cached queries: '
 . dbi_num_cached_queries() . ' -->';
if( dbi_get_debug() ) {
  $tret .= '
    <blockquote id="trailbq">
      <b>Executed queries:</b> ' . dbi_num_queries()
   . '&nbsp;&nbsp;<b>Cached queries:</b> ' . dbi_num_cached_queries() . '<br>
      <ol>';
  $log = $GLOBALS['SQLLOG'];
  foreach ( $log as $i ) {
    $tret .= '
        <li>' . $i . '</li>';
  }
  $tret .= '
      </ol>
    </blockquote>';
}

?>
