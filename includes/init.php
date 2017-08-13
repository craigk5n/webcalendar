<?php
/**
 * Does various initialization tasks and includes all needed files.
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
 *   - {@link send_no_cache_header()};
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: init.php,v 1.159 2010/08/31 13:49:17 cknudsen Exp $
 * @package WebCalendar
 */

 if( empty( $_SERVER['PHP_SELF'] )
     || ( ! empty( $_SERVER['PHP_SELF'] )
       && preg_match( '/\/includes\//', $_SERVER['PHP_SELF'] ) ) )
  die( 'You cannot access this file directly!' );

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';
require_once 'includes/classes/Event.class';
require_once 'includes/classes/RptEvent.class';

$WebCalendar = new WebCalendar( __FILE__ );

include_once 'includes/assert.php';
include_once 'includes/config.php';
include_once 'includes/dbi4php.php';
include_once 'includes/formvars.php';
include_once 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include_once 'includes/' . $user_inc;
include_once 'includes/validate.php';
include_once 'includes/site_extras.php';
include_once 'includes/access.php';
include_once 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

/**
 * Prints the HTML header and opening HTML body tag.
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
 * @param bool   $disableUTIL  Do not include the util.js link
 */
function print_header( $includes = '', $HeadX = '', $BodyX = '',
  $disableCustom = false, $disableStyle = false, $disableRSS = false,
  $disableAJAX = false, $disableUTIL = false ) {
  global $BGCOLOR, $browser, $charset, $CUSTOM_HEADER, $CUSTOM_SCRIPT,
  $DISABLE_POPUPS, $DISPLAY_TASKS, $DISPLAY_WEEKENDS, $FONTS, $friendly,
  $is_admin, $LANGUAGE, $login, $MENU_ENABLED, $MENU_THEME, $OTHERMONTHBG,
  $POPUP_FG, $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME, $REQUEST_URI, $SCRIPT,
  $self, $TABLECELLFG, $TEXTCOLOR, $THBG, $THFG, $TODAYCELLBG, $WEEKENDBG;

  ob_start ();

  if ( defined ( '__WC_INCDIR' ) && is_dir ( __WC_INCDIR ) )
    $incdir = __WC_INCDIR;
  elseif ( is_dir ( 'includes' ) )
    $incdir = 'includes';
  elseif ( is_dir ( '../includes' ) )
    $incdir = '../includes';

  $cs_ret = $lang = $menuHtml = $menuScript = '';

  // Remember this view if the file is a view_x.php script.
  if( ! strstr( $REQUEST_URI, 'view_entry' ) )
    remember_this_view( true );

  // Menu control.
  if( ! empty( $friendly ) || $disableCustom )
    $MENU_ENABLED = 'N';

  $appStr = generate_application_name( true );
  // Include includes/css/print_styles.css as a media="print" stylesheet.
  // When the user clicks on the "Printer Friendly" link, $friendly will be
  // non-empty, including this as a normal stylesheet so they can see how it
  // will look when printed. This maintains backwards-compatibility for browsers
  // that don't support media="print" stylesheets.
  $cs_ar = array( 'css/styles.css', 'css/print_styles.css' );
  $js_ar = array();

  $ret = send_doctype( $appStr ) .
// Use "normalize.css" to set all browsers, especially IE, to the same baseline.
// Use "punctuation.css" to start getting punctuation out of the code to where the translators can get at it.
'
    <link href="//cdnjs.cloudflare.com/ajax/libs/normalize/6.0.0/normalize.css" rel="stylesheet">
    <link href="' . $incdir . '/css/punctuation.css" rel="stylesheet">';

  if( ! $disableAJAX ) {
    $ret .= '
    <!--[if IE 5]><script src="includes/js/ie5.js"></script><![endif]-->';
    if ( is_array ( $includes ) && in_array ( 'JQUERY', $includes ) ) {
      $js_ar[] = 'js/jquery-1.9.1.min.js';
      $js_ar[] = 'js/jquery-1.10.1.js';
    } else {
      $js_ar[] = 'js/prototype.js';
      $js_ar[] = 'js/scriptaculous/scriptaculous.js?load=builder,effects';
    }
  }

  // CSS and JS includes needed for the top menu.
  if( $MENU_ENABLED == 'Y' ) {
    $saveBodyX = $BodyX;
    $BodyX = '';
    $MENU_THEME = ( ! empty( $MENU_THEME ) && $MENU_THEME != 'none'
      ? $MENU_THEME : 'default' );
    $menu_theme = ( $SCRIPT == 'admin.php'
      && ! empty( $GLOBALS['sys_MENU_THEME'] )
        ? $GLOBALS['sys_MENU_THEME'] : $MENU_THEME );

    include_once 'includes/menu/index.php';

    $cs_ar[] = 'menu/themes/' . $menu_theme . '/theme.css';
    $js_ar[] = 'menu/JSCookMenu.js';
    $js_ar[] = 'menu/themes/' . $menu_theme . '/theme.js';
    if ( ! empty ( $saveBodyX ) && preg_match ( '/cmDraw/', $BodyX ) ) {
      // menu code overwrote our BodyX
      if ( preg_match ( '/onload="(\S+)"/i', $saveBodyX, $matches ) ) {
        $BodyX = 'onload="' . $matches[1] . '; ' .
         "cmDraw( 'myMenuID', myMenu, 'hbr', cmTheme, 'Theme' );\"";
      } else if ( preg_match ( '/cmDraw/', $BodyX ) ) {
         // handled...  wasn't clobbered
      } else {
        die_miserable_death ( 'BodyX error in print_header.  Menu and ' .
          $self . ' are both setting onload callback.<br/>Old: ' .
          htmlentities ( $saveBodyX ) . '<br/><br/>New: ' .
          htmlentities ( $BodyX ) );
      }
    }
  }

  if( ! $disableUTIL )
    $js_ar[] = 'js/util.js';

  if( ! empty( $js_ar ) )
    foreach( $js_ar as $j ) {
      $i = 'includes/' . $j;
      $ret .= '
    <script src="' . $i . '"></script>';
    }

  // Any other includes?
  if( is_array( $includes ) ) {
    foreach( $includes as $inc ) {
      $cs_ret .= '<!-- inc \"$inc\" INCLUDED -->' . "\n";
      if ( $inc == 'JQUERY' ) {
        // Ignore since we handled it above
        $cs_ret .= '<!-- JQUERY INCLUDED -->' . "\n";
      } if( stristr( $inc, '.css' ) ) {
        $i = 'includes/' . $inc;
        // Not added to $cs_ar because I think we want these,
        // even if $disableStyle.
        $cs_ret .= '
    <link href="' . $i . '" rel="stylesheet" />';
      } elseif( substr( $inc, 0, 12 ) == 'js/popups.js'
          && ! empty( $DISABLE_POPUPS ) && $DISABLE_POPUPS == 'Y' ) {
        // Don't load popups.js if DISABLE_POPUPS.
      } else {
        $arinc = explode( '/', $inc );
        $ret .= '
    <script src="';

        if( stristr( $inc, '/true' ) ) {
          $i = 'includes';
          foreach( $arinc as $a ) {
            if( $a == 'true' )
              break;

            $i .= '/' . $a;
          }
          $ret .= $i . '?' . filemtime( $i );
        } else {
          $ret .= 'js_cacher.php?inc=' . $inc;
        }
        $ret .= '"></script>';
      }
    }
  }
  // There has to be a way to make "$menuScript" an external file.
  $ret .= $menuScript;

  $tmp   = '" rel="alternate" title="' . $appStr . ' - Unapproved Events - ';
  $tmp_f = 'rss_unapproved.php';
  $tmp_l = '
    <link type="application/rss+xml" href="';

  // Add RSS feed for unapproved events if approvals are required
  if( $GLOBALS['REQUIRE_APPROVALS'] == 'Y'
       && $login != '__public__' && $is_admin ) {
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

    $ret .= $tmp_l . $tmp_f . '?' . filemtime( $tmp_f ) . $tmp . $login . '" />'
     . ( $is_admin && $PUBLIC_ACCESS == 'Y' ? $tmp_l . $tmp_f . '?user=public&'
     . filemtime( $tmp_f ) . $tmp . translate( $PUBLIC_ACCESS_FULLNAME )
     . '" />' : '' );
  }
  if( $is_admin ) {
    $tmp_f = 'rss_activity_log.php';
    $ret .= $tmp_l . $tmp_f . '?' . filemtime( $tmp_f ) . '" rel="alternate"'
     . ' title="' . $appStr . ' - ' . translate('Activity Log') . '" />';
  }
  if( ! $disableStyle ) {
    // Check the CSS version for cache clearing if needed.
    if( isset( $_COOKIE['webcalendar_csscache'] ) )
      $webcalendar_csscache = $_COOKIE['webcalendar_csscache'];
    else {
      $webcalendar_csscache = 1;
      SetCookie( 'webcalendar_csscache', $webcalendar_csscache );
    }
    $ret .= '
    <link href="css_cacher.php?login='
     . ( empty( $_SESSION['webcal_tmp_login'] )
       ? $login : $_SESSION['webcal_tmp_login'] )
     . '&amp;css_cache=' . $webcalendar_csscache . '" rel="stylesheet" />';
    foreach( $cs_ar as $c ) {
      $i = 'includes/' . $c;
      $ret .= '
    <link href="' . $i . '" rel="stylesheet"'
       . ( $c == 'css/print_styles.css' && empty( $friendly )
         ? ' media="print"' : '' ) . ' />';
    }
  }
  echo $ret . $cs_ret
  // Add custom script/stylesheet if enabled.
   . ( $CUSTOM_SCRIPT == 'Y' && ! $disableCustom
     ? load_template( $login, 'S' ) : '' )
  // Add RSS feed if publishing is enabled.
   . ( ! empty( $GLOBALS['RSS_ENABLED'] ) && $GLOBALS['RSS_ENABLED'] == 'Y'
       && $login == '__public__' || ( ! empty( $GLOBALS['USER_RSS_ENABLED'] )
       && $GLOBALS['USER_RSS_ENABLED'] == 'Y' ) && ! $disableRSS ?
    $tmp_l . 'rss.php?' . filemtime( 'rss.php' )
      /* TODO: single-user mode, etc. */
     . ( $login != '__public__' ? '&user=' . $login : '' )
     . '" rel="alternate" title="' . $appStr . ' [RSS 2.0]" />' : '' )
  // Do we need anything else inside the header tag?
  // $HeadX moved here because linked CSS may override standard styles.
   . ( $HeadX ? '
     ' . $HeadX : '' ) . '
    <link type="image/x-icon" href="favicon.ico?'
   . filemtime( 'favicon.ico' ) . '" rel="shortcut icon" />
  </head>
  <body'
  // Determine the page direction (left-to-right or right-to-left).
  . ( translate( 'direction' ) == 'rtl' ? ' dir="rtl"' : '' )
  /* Add <body> id. */ . ' id="' . preg_replace( '/(_|.php)/', '',
    substr( $self, strrpos( $self, '/' ) + 1 ) ) . '"'
  // Add any extra parts to the <body> tag.
  . ( empty( $BodyX ) ? '' : " $BodyX" ) . '>' . "\n"
  // If menu is enabled, place menu above custom header if desired.
  . ( $MENU_ENABLED == 'Y' && $menuConfig['Above Custom Header']
    ? $menuHtml : '' )
  // Add custom header if enabled.
  . ( $CUSTOM_HEADER == 'Y' && ! $disableCustom
    ? load_template( $login, 'H' ) : '' )
  // Add the top menu if enabled.
  . ( $MENU_ENABLED == 'Y' && ! $menuConfig['Above Custom Header']
    ? $menuHtml : '' );
  // TODO convert this to return value.
}

/**
 * Prints the common trailer.
 *
 * @param bool $include_nav_links Should the standard navigation links be
 *                                included in the trailer?
 * @param bool $closeDb           Close the database connection when finished?
 * @param bool $disableCustom     Disable the custom trailer the administrator
 *                                has setup? (This is useful for small popup
 *                                windows and pages being used in an iframe.)
 */
function print_trailer( $include_nav_links = true, $closeDb = true,
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

  if( $include_nav_links && ! $friendly ) {
    if( $MENU_ENABLED == 'N' || $MENU_DATE_TOP == 'N' )
      $ret .= '<div id="dateselector">' . print_menu_dates() . '</div>';

    if( $MENU_ENABLED == 'N' )
      include_once 'includes/trailer.php';
  }

  $ret .= ( empty( $tret ) ? '' : $tret ) // Data from trailer.
  // Add custom trailer if enabled.
  . ( $CUSTOM_TRAILER == 'Y' && ! $disableCustom && isset( $c )
    ? load_template( $login, 'T' ) : '' );

  if( $closeDb ) {
    if( isset( $c ) )
      dbi_close( $c );

    unset( $c );
  }

  // Only enable CKEditor on the following pages.  Some pages are expecting plain
  // text and HTML will cause issues.
  $pagesWithFullEditor = [ 'edit_entry.php', 'docadd.php' ];
  $includeCkeditor = ( ! empty ( $GLOBALS['ALLOW_HTML_DESCRIPTION'] ) ) &&
    $GLOBALS['ALLOW_HTML_DESCRIPTION'] == 'Y' &&
    in_array ( $GLOBALS['SCRIPT'], $pagesWithFullEditor );

  return $ret . '
<!-- ' . $GLOBALS['PROGRAM_NAME'] . '     ' . $GLOBALS['PROGRAM_URL'] . ' -->' .
    ( $includeCkeditor ?
    /* Your choices here are "basic", "standard" or "full". */ '
    <script src="//cdn.ckeditor.com/4.6.0/basic/ckeditor.js"></script>
    <script>' .
    /* Use CKEditor for ALL <textarea>. */ '
      CKEDITOR.replaceAll();
    </script>' : '' ) .

    // Adds an easy link to validate the pages.
    ( $DEMO_MODE == 'Y' ? '
    <p><a href="http://validator.w3.org/check?uri=referer">'
     . '<img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" '
     . 'class="valid" /></a></p>' : '' )/* Close HTML page properly. */ . '
  </body>
</html>
';
}
/**
 * print_menu_dates (needs description)
 */
function print_menu_dates( $menu = false ) {
  global $cat_id, $CATEGORIES_ENABLED, $custom_view, $DATE_FORMAT_MD,
  $DATE_FORMAT_MY, $DISPLAY_WEEKENDS, $id, $login, $SCRIPT, $thisday,
  $thismonth, $thisyear, $user, $WEEK_START;

  $goStr = '
            </select>' . ( $menu ? '' : '
            <input type="submit" value="' . translate( 'Go' ) . '" />' ) . '
          </form>';
  $include_id = false;
  $option = '
              <option value="';
  $ret = $urlArgs = '';
  // TODO add this to admin and pref.
  // Change this value to 'Y' to enable staying in custom views.
  $STAY_IN_VIEW = 'N';
  $selected = ' selected="selected"';
  if( $STAY_IN_VIEW == 'Y' && ! empty( $custom_view ) ) {
    $include_id = true;
    $monthUrl = $SCRIPT;
  } else
  if( access_can_view_page( 'month.php' ) )
    $monthUrl = 'month.php';
  else {
    $monthUrl = $GLOBALS['STARTVIEW'];
    if( preg_match( '/[?&](\S+)=(\S+)/', $monthUrl, $match ) ) {
      $monthUrl = $match[0];
      $urlArgs = '
              <input type="hidden" name="'
       . $match[1] . '" value="' . $match[2] . '" />';
    }
  }

  $ret .= '
          <form action="' . $monthUrl
   . '" method="get" name="SelectMonth" id="month'
   . ( $menu ? 'menu' : 'form' ) . '"> ' . $urlArgs
   . ( ! empty( $user ) && $user != $login ? '
            <input type="hidden" name="user" value="' . $user . '" />' : '' )
   . ( ! empty( $id ) && $include_id ? '
            <input type="hidden" name="id" value="' . $id . '" />' : '' )
   . ( ! empty( $cat_id ) && $CATEGORIES_ENABLED == 'Y'
     && ( ! $user || $user == $login ) ? '
            <input type="hidden" name="cat_id" value="'
     . $cat_id . '" />' : '' ) . '
            <label for="monthselect"><a '
   . 'href="javascript:document.SelectMonth.submit()">'
   . translate( 'Month' ) . '</a>:&nbsp;</label>
            <select name="date" id="monthselect" '
   . 'onchange="document.SelectMonth.submit()">';

  $d = ( empty( $thisday ) ? date( 'd' ) : $thisday );
  $m = ( empty( $thismonth ) ? date( 'm' ) : $thismonth );
  $y = ( empty( $thisyear ) ? date( 'Y' ) : $thisyear );

  $lastDay = ( $DISPLAY_WEEKENDS == 'N' ? 4 : 6 );
  $thisdate = date( 'Ymd', mktime( 0, 0, 0, $m, 1, $y ) );
  $thisweek = date( 'W', mktime( 0, 0, 0, $m, $d, $y ) );
  $wkstart = get_weekday_before( $y, $m, $d );

  $tmp = mktime( 0, 0, 0, $m - 7, 1, $y );
  $m = date( 'm', $tmp );
  $y = date( 'Y', $tmp );

  for( $i = 0; $i < 25; $i++ ) {
    $m++;
    if( $m > 12 ) {
      $m = 1;
      $y++;
    }
    if( $y > 1969 && $y < 2038 ) {
      $dateYmd = date( 'Ymd', mktime( 0, 0, 0, $m, 1, $y ) );
      $ret .= $option . $dateYmd . '"'
       . ( $dateYmd == $thisdate ? $selected : '' ) . '>'
       . date_to_str( $dateYmd, $DATE_FORMAT_MY, false, true ) . '</option>';
    }
  }

  $ret .= $goStr . ( $menu ? '
        </td>
        <td class="ThemeMenubackgr ThemeMenu">' : '' );

  if( $STAY_IN_VIEW == 'Y' && ! empty( $custom_view ) )
    $weekUrl = $SCRIPT;
  else
  if( access_can_view_page( 'week.php' ) ) {
    $urlArgs = '';
    $weekUrl = 'week.php';
  } else {
    $weekUrl = $GLOBALS['STARTVIEW'];
    if( preg_match( '/[?&](\S+)=(\S+)/', $weekUrl, $match ) ) {
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
   . ( ! empty( $user ) && $user != $login ? '
            <input type="hidden" name="user" value="' . $user . '" />' : '' )
   . ( ! empty( $id ) && $include_id ? '
            <input type="hidden" name="id" value="' . $id . '" />' : '' )
   . ( ! empty( $cat_id ) && $CATEGORIES_ENABLED == 'Y'
     && ( ! $user || $user == $login ) ? '
            <input type="hidden" name="cat_id" value="'
     . $cat_id . '" />' : '' ) . '
            <label for="weekselect"><a '
   . 'href="javascript:document.SelectWeek.submit()">'
   . translate( 'Week' ) . '</a>:&nbsp;</label>
            <select name="date" id="weekselect" '
   . 'onchange="document.SelectWeek.submit()">';

  $y = ( empty( $thisyear ) ? date( 'Y' ) : $thisyear );
  for( $i = -5; $i <= 9; $i++ ) {
    $twkstart = bump_local_timestamp( $wkstart, 0, 0, 0, 0, 7 * $i, 0 );
    $twkend = bump_local_timestamp( $twkstart, 0, 0, 0, 0, $lastDay, 0 );
    $dateSYmd = date( 'Ymd', $twkstart );
    $dateEYmd = date( 'Ymd', $twkend );
    $dateW = date( 'W', $twkstart + 86400 );
    if( $twkstart > 0 && $twkend < 2146021200 )
      $ret .= $option . $dateSYmd . '"'
       . ( $dateW == $thisweek ? $selected : '' ) . '>'
       . ( ! empty( $GLOBALS['PULLDOWN_WEEKNUMBER'] )
         && $GLOBALS['PULLDOWN_WEEKNUMBER'] == 'Y'
        ? '(' . $dateW . ')&nbsp;&nbsp;' : '' ) . sprintf( '%s - %s',
        date_to_str( $dateSYmd, $DATE_FORMAT_MD, false, true ),
        date_to_str( $dateEYmd, $DATE_FORMAT_MD, false, true ) ) . '</option>';
  }

  $ret .= $goStr . ( $menu ? '
        </td>
        <td class="ThemeMenubackgr ThemeMenu alignright">' : '' );

  if( $STAY_IN_VIEW == 'Y' && ! empty( $custom_view ) )
    $yearUrl = $SCRIPT;
  else
  if( access_can_view_page( 'year.php' ) ) {
    $urlArgs = '';
    $yearUrl = 'year.php';
  } else {
    $yearUrl = $GLOBALS['STARTVIEW'];
    if( preg_match( '/[?&](\S+)=(\S+)/', $yearUrl, $match ) ) {
      $yearUrl = $match[0];
      $urlArgs = '
            <input type="hidden" name="'
       . $match[1] . '" value="' . $match[2] . '" />';
    }
  }

  $ret .= '
          <form action="' . $yearUrl
   . '" method="get" name="SelectYear" id="year'
   . ( $menu ? 'menu' : 'form' ) . '">' . $urlArgs
   . ( ! empty( $user ) && $user != $login ? '
            <input type="hidden" name="user" value="' . $user . '" />' : '' )
   . ( ! empty( $id ) && $include_id ? '
            <input type="hidden" name="id" value="' . $id . '" />' : '' )
   . ( ! empty( $cat_id ) && $CATEGORIES_ENABLED == 'Y'
     && ( ! $user || $user == $login ) ? '
            <input type="hidden" name="cat_id" value="'
     . $cat_id . '" />' : '' ) . '
            <label for="yearselect"><a '
   . 'href="javascript:document.SelectYear.submit()">'
   . translate( 'Year' ) . '</a>:&nbsp;</label>
            <select name="year" id="yearselect" '
   . 'onchange="document.SelectYear.submit()">';

  for( $i = $y - 2, $cnt = $y + 6; $i < $cnt; $i++ ) {
    if( $i > 1969 && $i < 2038 )
      $ret .= $option . $i . '"'
       . ( $i == $y ? $selected : '' ) . '>' . $i . '</option>';
  }

  return $ret . $goStr;
}

?>
