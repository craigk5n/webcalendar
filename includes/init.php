<?php
/* Does various initialization tasks and includes all needed files.
 *
 * This page is included by most WebCalendar pages as the only include file.
 * This greatly simplifies the other PHP pages since they don't need to worry
 * about what files it includes.
 *
 * <b>Comments:</b>
 * The following scripts do not use this file:
 *   - login.php
 *   - week_ssi.php
 *   - upcoming.php
 *   - tools/send_reminders.php
 *
 * How to use:
 *   1. call include_once 'includes/init.php'; at the top of your script.
 *   2. call any other functions or includes not in this file that you need
 *   3. call the print_header function with proper arguments
 *
 * What gets called:
 *   - include_once 'includes/translate.php';
 *   - require_once 'includes/classes/WebCalendar.class';
 *   - require_once 'includes/classes/Event.class';
 *   - require_once 'includes/classes/RptEvent.class';
 *   - include_once 'includes/assert.php';
 *   - include_once 'includes/config.php';
 *   - include_once 'includes/dbi4php.php';
 *   - include_once 'includes/formvars.php';
 *   - include_once 'includes/functions.php';
 *   - include_once "includes/$user_inc";
 *   - include_once 'includes/validate.php';
 *   - include_once 'includes/site_extras.php';
 *   - include_once 'includes/access.php';
 *
 * Also, for month.php, day.php, week.php, week_details.php:
 *   - {@link send_no_cache_header ()};
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: init.php,v 1.130.2.15 2011/08/09 03:27:56 cknudsen Exp $
 * @package WebCalendar
 */
if ( empty ( $_SERVER['PHP_SELF'] ) ||
    ( ! empty ( $_SERVER['PHP_SELF'] ) &&
      preg_match ( "/\/includes\//", $_SERVER['PHP_SELF'] ) ) )
  die ( 'You cannot access this file directly!' );

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';
require_once 'includes/classes/Event.class';
require_once 'includes/classes/RptEvent.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include_once 'includes/assert.php';
include_once 'includes/config.php';
include_once 'includes/dbi4php.php';
include_once 'includes/formvars.php';
include_once 'includes/functions.php';

$WebCalendar->initializeFirstPhase ();

include_once 'includes/' . $user_inc;
include_once 'includes/validate.php';
include_once 'includes/site_extras.php';
include_once 'includes/access.php';
include_once 'includes/gradient.php';

$WebCalendar->initializeSecondPhase ();

/* Prints the HTML header and opening HTML body tag.
 *
 * @param array  $includes     Array of additional files to include referenced
 *                             from the includes directory
 * @param string $HeadX        Data to be printed inside the head tag (meta,
 *                             script, etc)
 * @param string $BodyX        Data to be printed inside the Body tag (onload
 *                             for example)
 * @param bool   $disbleCustom Do not include custom header? (useful for small
 *                             popup windows, such as color selection)
 * @param bool   $disableStyle Do not include the standard css?
 * @param bool   $disableRSS   Do not include the RSS link
 * @param bool   $disableAJAX  Do not include the prototype.js link
 */
function print_header ( $includes = '', $HeadX = '', $BodyX = '',
  $disableCustom = false, $disableStyle = false, $disableRSS = false,
  $disableAJAX = false, $disableUTIL = false ) {
  global $BGCOLOR, $browser, $charset, $CUSTOM_HEADER, $CUSTOM_SCRIPT,
  $DISABLE_POPUPS, $DISPLAY_TASKS, $DISPLAY_WEEKENDS, $FONTS, $friendly,
  $LANGUAGE, $login, $MENU_ENABLED, $MENU_THEME, $OTHERMONTHBG,
  $POPUP_FG, $REQUEST_URI, $self, $TABLECELLFG, $TEXTCOLOR,
  $THBG, $THFG, $TODAYCELLBG, $WEEKENDBG, $SCRIPT, $PUBLIC_ACCESS_FULLNAME,
  $PUBLIC_ACCESS, $is_admin;

  $lang = $ret = '';
  // Remember this view if the file is a view_x.php script.
  if ( ! strstr ( $REQUEST_URI, 'view_entry' ) )
    remember_this_view ( true );

  // Check the CSS version for cache clearing if needed.
  if ( ! $disableStyle ) {
    if ( isset ( $_COOKIE['webcalendar_csscache'] ) )
      $webcalendar_csscache = $_COOKIE['webcalendar_csscache'];
    else {
      $webcalendar_csscache = 1;
      SetCookie ( 'webcalendar_csscache', $webcalendar_csscache );
    }
  }
  // Menu control.
  if ( ! empty ( $friendly ) || $disableCustom )
    $MENU_ENABLED = 'N';

  $appStr = generate_application_name ( true );

  $ret .= send_doctype ( $appStr );
  
  $ret .= ( ! $disableAJAX ? '
    <script type="text/javascript" src="includes/js/prototype.js"></script>'
    : '' );
  // Includes needed for the top menu.
  if ( $MENU_ENABLED == 'Y' ) {
    $MENU_THEME = ( ! empty ( $MENU_THEME ) && $MENU_THEME != 'none'
      ? $MENU_THEME : 'default' );
    $menu_theme =  ( $SCRIPT == 'admin.php' && ! empty ( $GLOBALS['sys_MENU_THEME'] ) 
      ? $GLOBALS['sys_MENU_THEME'] :
      $MENU_THEME );
    $ret .= '
    <script type="text/javascript" src="includes/menu/JSCookMenu.js"></script>
    <script type="text/javascript" src="includes/menu/themes/' . $menu_theme
     . '/theme.js"></script>';
  }

  $ret .= ( ! $disableUTIL ? '
    <script type="text/javascript" src="includes/js/util.js"></script>' : '' );
  // Any other includes?
  if ( is_array ( $includes ) ) {
    foreach ( $includes as $inc ) {
      if ( substr ( $inc, 0, 13 ) == 'js/popups.php' && !
          empty ( $DISABLE_POPUPS ) && $DISABLE_POPUPS == 'Y' ) {
        // Don't load popups.php javascript if DISABLE_POPUPS.
      } else
        $ret .= '
    <script type="text/javascript" src="js_cacher.php?inc='
         . $inc . '"></script>';
    }
  }
  // Do we need anything else inside the header tag?
  if ( $HeadX )
    $ret .= '
    ' . $HeadX;
  // Include the CSS needed for the top menu and themes.
  if ( $MENU_ENABLED == 'Y' ) {
    include_once 'includes/menu/index.php';
    $ret .= '
    <link rel="stylesheet" type="text/css" href="includes/menu/themes/'
     . $menu_theme . '/theme.css" />';
  }
  // Add RSS feed for unapproved events if approvals are required
  if ( $GLOBALS['REQUIRE_APPROVALS'] == 'Y' && $login != '__public__' && $is_admin ) {

  // Prh .. fix theme change for auth_http which does not set webcal*login
  //        variables.
  // 
  //        Pass the logged in user id as login=<whatever> on the URL
  //        Add css_cache=<cookie setting> to change the URL signature
  //        to force a fetch from the server rather than from the 
  //        browser cache when the style changes. 
    // Note: we could do all the queries to add the RSS feed for every user
    // the current user has permissions to approve for, but I'm thinking
    // that's too many db requests to repeat on every page.
    $ret .= '<link rel="alternate" type="application/rss+xml" title="' . $appStr
      . ' - Unapproved Events - ' . $login . '" href="rss_unapproved.php"/>';
    if ( $is_admin && $PUBLIC_ACCESS == 'Y' )
      $ret .= '<link rel="alternate" type="application/rss+xml" title="' .
        $appStr . ' - Unapproved Events - ' .
        translate ( $PUBLIC_ACCESS_FULLNAME ) .
        '" href="rss_unapproved.php?user=public"/>';
  }
  if ( $is_admin ) {
    $ret .= '<link rel="alternate" type="application/rss+xml" title="' . $appStr
      . ' - ' . translate('Activity Log') . '" href="rss_activity_log.php"/>';
  }
  // If loading admin.php, we will not use an exrternal file because we need to
  // override the global colors and this is impossible if loading external file.
  // We will still increment the webcalendar_csscache cookie though.
  echo $ret . ( $disableStyle ? '' : '
    <link rel="stylesheet" type="text/css" href="css_cacher.php?login='
     . ( empty ( $_SESSION['webcal_tmp_login'] )
      ? $login : $_SESSION['webcal_tmp_login'] )
     . '&amp;css_cache=' . $webcalendar_csscache
     . '" />' )
  // Add custom script/stylesheet if enabled.
  . ( $CUSTOM_SCRIPT == 'Y' && ! $disableCustom
    ? load_template ( $login, 'S' ) : '' )
  // Include includes/print_styles.css as a media="print" stylesheet. When the
  // user clicks on the "Printer Friendly" link, $friendly will be non-empty,
  // including this as a normal stylesheet so they can see how it will look
  // when printed. This maintains backwards-compatibility for browsers that
  // don't support media="print" stylesheets
  . ( empty ( $friendly ) ? '' : '
    <link rel="stylesheet" type="text/css"'
     . ( empty ( $friendly ) ? ' media="print"' : '' )
     . ' href="includes/print_styles.css" />' )
  // Add RSS feed if publishing is enabled.
  . ( ! empty ( $GLOBALS['RSS_ENABLED'] ) && $GLOBALS['RSS_ENABLED'] == 'Y' &&
    $login == '__public__' || ( ! empty ( $GLOBALS['USER_RSS_ENABLED'] ) &&
    $GLOBALS['USER_RSS_ENABLED'] == 'Y' ) && ! $disableRSS ? '
    <link rel="alternate" type="application/rss+xml" title="' . $appStr
     . ' [RSS 2.0]" href="rss.php'
     /* TODO: single-user mode, etc. */
     . ( $login != '__public__' ? '?user=' . $login : '' ) . '" />' : '' ) . '
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />'
   . ( $MENU_ENABLED == 'Y' ? $menuScript : '' ) . '
  </head>
  <body'
  // Determine the page direction (left-to-right or right-to-left).
  . ( translate ( 'direction' ) == 'rtl' ? ' dir="rtl"' : '' )
  /* Add <body> id. */ . ' id="' . preg_replace ( '/(_|.php)/', '',
    substr ( $self, strrpos ( $self, '/' ) + 1 ) ) . '"'
  // Add any extra parts to the <body> tag.
  . ( empty ( $BodyX ) ? '' : " $BodyX" ) . '>' . "\n"
  // If menu is enabled, place menu above custom header if desired.
  . ( $MENU_ENABLED == 'Y' && $menuConfig['Above Custom Header']
    ? $menuHtml : '' )
  // Add custom header if enabled.
  . ( $CUSTOM_HEADER == 'Y' && ! $disableCustom
    ? load_template ( $login, 'H' ) : '' )
  // Add the top menu if enabled.
  . ( $MENU_ENABLED == 'Y' && ! $menuConfig['Above Custom Header']
    ? $menuHtml : '' );
  // TODO convert this to return value.
}

/* Prints the common trailer.
 *
 * @param bool $include_nav_links Should the standard navigation links be
 *                                included in the trailer?
 * @param bool $closeDb           Close the database connection when finished?
 * @param bool $disableCustom     Disable the custom trailer the administrator
 *                                has setup? (This is useful for small popup
 *                                windows and pages being used in an iframe.)
 */
function print_trailer ( $include_nav_links = true, $closeDb = true,
  $disableCustom = false ) {
  global $ALLOW_VIEW_OTHER, $c, $cat_id, $CATEGORIES_ENABLED, $CUSTOM_TRAILER,
  $DATE_FORMAT_MD, $DATE_FORMAT_MY, $DEMO_MODE, $DISPLAY_TASKS, $friendly,
  $DISPLAY_TASKS_IN_GRID, $fullname, $GROUPS_ENABLED, $has_boss, $is_admin,
  $is_nonuser, $is_nonuser_admin, $LAYER_STATUS, $login, $login_return_path,
  $MENU_DATE_TOP, $MENU_ENABLED, $NONUSER_ENABLED, $PUBLIC_ACCESS,
  $PUBLIC_ACCESS_CAN_ADD, $PUBLIC_ACCESS_FULLNAME, $PUBLIC_ACCESS_OTHERS,
  $readonly, $REPORTS_ENABLED, $REQUIRE_APPROVALS, $single_user, $STARTVIEW,
  $thisday, $thismonth, $thisyear, $use_http_auth, $user, $views, $WEEK_START;

  $ret = '';

  if ( $include_nav_links && ! $friendly ) {
    if ( $MENU_ENABLED == 'N' || $MENU_DATE_TOP == 'N' )
      $ret .= '<div id="dateselector">' . print_menu_dates () . '</div>';

    if ( $MENU_ENABLED == 'N' )
      include_once 'includes/trailer.php';
  }

  $ret .= ( empty ( $tret ) ? '' : $tret ) // Data from trailer.
  // Add custom trailer if enabled.
  . ( $CUSTOM_TRAILER == 'Y' && ! $disableCustom && isset ( $c )
    ? load_template ( $login, 'T' ) : '' );

  if ( $closeDb ) {
    if ( isset ( $c ) )
      dbi_close ( $c );

    unset ( $c );
  }

  // Only include version info if user is admin.  No need to publicize
  // version to would-be hackers.
  return $ret .
    ( $is_admin ?
    "<!-- " . $GLOBALS['PROGRAM_NAME'] . "     "
    . $GLOBALS['PROGRAM_URL'] . " -->\n" : '' )
  // Adds an easy link to validate the pages.
  . ( $DEMO_MODE == 'Y' ? '
    <p><a href="http://validator.w3.org/check?uri=referer">'
     . '<img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" '
     . 'class="valid" /></a></p>' : '' )/* Close HTML page properly. */ . '
  </body>
</html>
';
}

function print_menu_dates ( $menu = false ) {
  global $cat_id, $CATEGORIES_ENABLED, $custom_view, $DATE_FORMAT_MD,
  $DATE_FORMAT_MY, $DISPLAY_WEEKENDS, $id, $login, $SCRIPT, $thisday,
  $thismonth, $thisyear, $user, $WEEK_START;

  $goStr = translate ( 'Go' );
  $ret = $urlArgs = $include_id = '';
  // TODO add this to admin and pref.
  // Change this value to 'Y' to enable staying in custom views.
  $STAY_IN_VIEW = 'N';
  $selected = ' selected="selected"';
  if ( $STAY_IN_VIEW == 'Y' && ! empty ( $custom_view ) ) {
    $include_id = true;
    $monthUrl = $SCRIPT;
  } else
  if ( access_can_view_page ( 'month.php' ) )
    $monthUrl = 'month.php';
  else {
    $monthUrl = $GLOBALS['STARTVIEW'];
    if ( preg_match ( '/[?&](\S+)=(\S+)/', $monthUrl, $match ) ) {
      $monthUrl = $match[0];
      $urlArgs = '
              <input type="hidden" name="'
       . $match[1] . '" value="' . $match[2] . '" />';
    }
  }
  if ( access_can_access_function ( ACCESS_MONTH ) ) {
    $ret .= '
            <form action="' . $monthUrl
     . '" method="get" name="SelectMonth" id="month'
     . ( $menu ? 'menu' : 'form' ) . '"> ' . $urlArgs
     . ( ! empty ( $user ) && $user != $login ? '
              <input type="hidden" name="user" value="' . $user . '" />' : '' )
     . ( ! empty ( $id ) && $include_id ? '
              <input type="hidden" name="id" value="' . $id . '" />' : '' )
     . ( ! empty ( $cat_id ) && $CATEGORIES_ENABLED == 'Y' &&
      ( ! $user || $user == $login ) ? '
              <input type="hidden" name="cat_id" value="'
       . $cat_id . '" />' : '' ) . '
              <label for="monthselect"><a '
     . 'href="javascript:document.SelectMonth.submit()">'
     . translate ( 'Month' ) . '</a>:&nbsp;</label>
              <select name="date" id="monthselect" '
     . 'onchange="document.SelectMonth.submit()">';
  
    if ( ! empty ( $thisyear ) && ! empty ( $thismonth ) ) {
      $m = $thismonth;
      $y = $thisyear;
    } else {
      $m = date ( 'm' );
      $y = date ( 'Y' );
    }
    $d_time = mktime ( 0, 0, 0, $m, 1, $y );
    $thisdate = date ( 'Ymd', $d_time );
    // $y--;
    $m -= 7;
    for ( $i = 0; $i < 25; $i++ ) {
      $m++;
      if ( $m > 12 ) {
        $m = 1;
        $y++;
      }
      if ( $y >= 1970 && $y < 2038 ) {
        $d = mktime ( 0, 0, 0, $m, 1, $y );
        $dateYmd = date ( 'Ymd', $d );
        $ret .= '
                  <option value="' . $dateYmd . '"'
         . ( $dateYmd == $thisdate ? $selected : '' ) . '>'
         . date_to_str ( $dateYmd, $DATE_FORMAT_MY, false, true, 0 ) . '</option>';
      }
    }
  }
  if ( access_can_access_function ( ACCESS_WEEK ) ) {
    $ret .= '
              </select>' . ( $menu ? '' : '
              <input type="submit" value="' . $goStr . '" />' ) . '
            </form>' . ( $menu ? '
          </td>
          <td class="ThemeMenubackgr ThemeMenu">' : '' );
  
    if ( $STAY_IN_VIEW == 'Y' && ! empty ( $custom_view ) )
      $weekUrl = $SCRIPT;
    else
    if ( access_can_view_page ( 'week.php' ) ) {
      $urlArgs = '';
      $weekUrl = 'week.php';
    } else {
      $weekUrl = $GLOBALS['STARTVIEW'];
      if ( preg_match ( '/[?&](\S+)=(\S+)/', $weekUrl, $match ) ) {
        $weekUrl = $match[0];
        $urlArgs = '
                <input type="hidden" name="'
         . $match[1] . '" value="' . $match[2] . '" />';
      }
    }
    $ret .= '
            <form action="' . $weekUrl
     . '" method="get" name="SelectWeek" id="week'
     . ( $menu ? 'menu' : 'form' ) . '">' . $urlArgs
     . ( ! empty ( $user ) && $user != $login ? '
              <input type="hidden" name="user" value="' . $user . '" />' : '' )
     . ( ! empty ( $id ) && $include_id ? '
              <input type="hidden" name="id" value="' . $id . '" />' : '' )
     . ( ! empty ( $cat_id ) && $CATEGORIES_ENABLED == 'Y' &&
      ( ! $user || $user == $login ) ? '
              <input type="hidden" name="cat_id" value="'
       . $cat_id . '" />' : '' ) . '
              <label for="weekselect"><a '
     . 'href="javascript:document.SelectWeek.submit()">'
     . translate ( 'Week' ) . '</a>:&nbsp;</label>
              <select name="date" id="weekselect" '
     . 'onchange="document.SelectWeek.submit()">';
  
    if ( ! empty ( $thisyear ) && ! empty ( $thismonth ) ) {
      $m = $thismonth;
      $y = $thisyear;
    } else {
      $m = date ( 'm' );
      $y = date ( 'Y' );
    }
    $d = ( empty ( $thisday ) ? date ( 'd' ) : $thisday  );
    $d_time = mktime ( 0, 0, 0, $m, $d, $y );
    $thisweek = date ( 'W', $d_time );
    $wkstart = get_weekday_before ( $y, $m, $d );
    $lastDay = ( $DISPLAY_WEEKENDS == 'N' ? 4 : 6 );
    for ( $i = -5; $i <= 9; $i++ ) {
      $twkstart = $wkstart + ( 604800 * $i );
      $twkend = $twkstart + ( 86400 * $lastDay );
      $dateSYmd = date ( 'Ymd', $twkstart );
      $dateEYmd = date ( 'Ymd', $twkend );
      $dateW = date ( 'W',  $twkstart + 86400 );
      if ( $twkstart > 0 && $twkend < 2146021200 )
        $ret .= '
                <option value="' . $dateSYmd . '"'
         . ( $dateW == $thisweek ? $selected : '' ) . '>'
         . ( ! empty ( $GLOBALS['PULLDOWN_WEEKNUMBER'] ) &&
          ( $GLOBALS['PULLDOWN_WEEKNUMBER'] == 'Y' )
          ? '( ' . $dateW . ' )&nbsp;&nbsp;' : '' ) . sprintf ( "%s - %s",
          date_to_str ( $dateSYmd, $DATE_FORMAT_MD, false, true, 0 ),
          date_to_str ( $dateEYmd, $DATE_FORMAT_MD, false, true, 0 ) )
         . '</option>';
    }
  }
  if ( access_can_access_function ( ACCESS_YEAR ) ) {
    $ret .= '
                </select>' . ( $menu ? '' : '
              <input type="submit" value="' . $goStr . '" />' ) . '
            </form>' . ( $menu ? '
          </td>
          <td class="ThemeMenubackgr ThemeMenu" align="right">' : '' );
  
    if ( $STAY_IN_VIEW == 'Y' && ! empty ( $custom_view ) )
      $yearUrl = $SCRIPT;
    else
    if ( access_can_view_page ( 'year.php' ) ) {
      $urlArgs = '';
      $yearUrl = 'year.php';
    } else {
      $yearUrl = $GLOBALS['STARTVIEW'];
      if ( preg_match ( '/[?&](\S+)=(\S+)/', $yearUrl, $match ) ) {
        $yearUrl = $match[0];
        $urlArgs = '
              <input type="hidden" name="'
         . $match[1] . '" value="' . $match[2] . '" />';
      }
    }
    $ret .= '
            <form action="' . $yearUrl . '" method="get" name="SelectYear" id="year'
     . ( $menu ? 'menu' : 'form' ) . '">' . $urlArgs
     . ( ! empty ( $user ) && $user != $login ? '
              <input type="hidden" name="user" value="' . $user . '" />' : '' )
     . ( ! empty ( $id ) && $include_id ? '
              <input type="hidden" name="id" value="' . $id . '" />' : '' )
     . ( ! empty ( $cat_id ) && $CATEGORIES_ENABLED == 'Y' &&
      ( ! $user || $user == $login ) ? '
              <input type="hidden" name="cat_id" value="'
       . $cat_id . '" />' : '' ) . '
              <label for="yearselect"><a '
     . 'href="javascript:document.SelectYear.submit()">'
     . translate ( 'Year' ) . '</a>:&nbsp;</label>
              <select name="year" id="yearselect" '
     . 'onchange="document.SelectYear.submit()">';
  
    $y = ( empty ( $thisyear ) ? date ( 'Y' ) : $thisyear );
  
    for ( $i = $y - 2; $i < $y + 6; $i++ ) {
      if ( $i >= 1970 && $i < 2038 )
        $ret .= '
                <option value="' . $i . '"'
         . ( $i == $y ? $selected : '' ) . ">$i" . '</option>';
    }
  
    $ret .= '
              </select>' . ( $menu ? '' : '
              <input type="submit" value="' . $goStr . '" />' ) . '
            </form>';
  }
return $ret;
}

?>
