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
 *   - require_once 'includes/classes/WebCalendar.php';
 *   - require_once 'includes/classes/Event.php';
 *   - require_once 'includes/classes/RptEvent.php';
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
 * @package WebCalendar
 */

 if( empty( $_SERVER['PHP_SELF'] )
     || ( ! empty( $_SERVER['PHP_SELF'] )
       && preg_match( '/\/includes\//', $_SERVER['PHP_SELF'] ) ) ) {
  die( 'You cannot access this file directly!' );
 }

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.php';
require_once 'includes/classes/Event.php';
require_once 'includes/classes/RptEvent.php';

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

// TODO: Start using composer for dependency management...
//require_once 'vendor/autoload.php';

// Return the toplevel URL (no path) of the current URL.
function get_server_top_url () {
  if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
     $url = "https://";
  else
     $url = "http://";
  // Append the host(domain name, ip) to the URL.
  $url.= $_SERVER['HTTP_HOST'];
  return $url;
}


/**
  * Send HTTP headers.  Would be nice to make some of these configurable
  * in the admin System Settings.
  */
function send_http_headers () {
  // TODO: Allow a (future) admin settings override some of these cookie settings

  // Prevent click-jacking by including a "frame-breaker" script in each page that should not be framed.
  // Source: https://cheatsheetseries.owasp.org/cheatsheets/Clickjacking_Defense_Cheat_Sheet.html
  Header("Content-Security-Policy: frame-ancestors 'self'");

  // WebCalendar cannot be loaded in a frame
  // Options for this cookie: deny, sameorigin, allow-from
  Header("X-Frame-Options: sameorigin");

  // Marker used by the server to indicate that the MIME types advertised in the
  // Content-Type headers should be followed and not be changed.
  // NOTE: Some web servers (e.g. apache2) set this for us, so users may get 2 of these.
  Header("X-Content-Type-Options: nosniff");

  // Set the Content Security Policy.  WebCalendar specifically bundles required
  // components like jQuery and Bootstrap so that external resources don't need
  // to be loaded.  However, if an admin user creates a custom header that loads
  // external content, this may break.
  // More info: https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
  // TODO: Fix this.  It seems to break CSS, images or something.  I'm trying
  // to restrict all content to one server, but it's blocking more than it should.
  //Header("Content-Security-Policy: default-src " . get_server_top_url() .
  //  "; img-src *; style-src *");
}

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
 * @param bool   $IGNORED      Parameter not used (ignored)
 * @param bool   $disableUTIL  Do not include the util.js link
 */
function print_header( $includes = '', $HeadX = '', $BodyX = '',
  $disableCustom = false, $disableStyle = false, $disableRSS = false,
  $IGNORED = false, $disableUTIL = false ) {
  global $BGCOLOR, $browser, $charset, $CUSTOM_HEADER, $CUSTOM_SCRIPT,
  $DISABLE_POPUPS, $DISPLAY_TASKS, $DISPLAY_WEEKENDS, $FONTS, $friendly,
  $is_admin, $LANGUAGE, $login, $MENU_ENABLED, $MENU_THEME, $OTHERMONTHBG,
  $POPUP_FG, $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME, $REQUEST_URI, $SCRIPT,
  $self, $TABLECELLFG, $TEXTCOLOR, $THBG, $THFG, $TODAYCELLBG, $WEEKENDBG,
  $ASSETS;

  ob_start ();

  if ( is_dir ( 'includes' ) ) {
    $incdir = 'includes';
  } elseif ( is_dir ( '../includes' ) ) {
    $incdir = '../includes';
  }

  $cs_ret = $lang = '';

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

  $ret = send_doctype( $appStr );
// Use "punctuation.css" to start getting punctuation out of the code to where the translators can get at it.
  //  <link href="' . $incdir . '/css/punctuation.css" rel="stylesheet">';

  send_http_headers ();

  $ret .= "\n<style id=\"antiClickjack\">\n  body{display:none !important;}\n</style>\n" .
    "<script type=\"text/javascript\">\n" .
    "  if (self.location.hostname === top.location.hostname) {\n" .
    "      var antiClickjack = document.getElementById(\"antiClickjack\");\n" .
    "      antiClickjack.parentNode.removeChild(antiClickjack);\n" .
    "  } else {\n" .
    "      top.location = self.location;\n" .
    "  }\n" .
    "</script>\n";
 

  $ret .= $ASSETS;

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
      //$cs_ret .= '<!-- inc "' . $inc . '" INCLUDED -->' . "\n";
      if ( $inc == 'JQUERY' ) {
        // Ignore since we handled it above
        //$cs_ret .= '<!-- JQUERY INCLUDED -->' . "\n";
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
      sendCookie( 'webcalendar_csscache', $webcalendar_csscache );
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
         ? ' media="print"' : '' ) . ' />' . "\n";
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
  // Add custom header if enabled.
  . ( $CUSTOM_HEADER == 'Y' && ! $disableCustom
    ? load_template( $login, 'H' ) : '' );
  // HTML includes needed for the top menu.
  if( $MENU_ENABLED == 'Y' ) {
    include "menu.php";
  }
  // TODO convert this to return value.
  echo '<div class="container-fluid">';
}

/**
 * Returns the common trailer.  (DOES NOT print it, unlike print_header.)
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

  // If menu enabled, include JS for Bootstrap v4 submenu.
  // TODO: Get the submenu working to allow for more dates in the menu.
  if ($MENU_ENABLED != 'N') {
    $ret .= '<script src="./includes/js/menu.js"></script>' . "\n";
  }
  if( $include_nav_links && ! $friendly ) {
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

  return $ret .
    '<!-- ' . $GLOBALS['PROGRAM_NAME'] . '     ' . $GLOBALS['PROGRAM_URL'] . ' -->' .
    ( $includeCkeditor ?
    /* Load local copy of ckeditor */ '
    <script src="pub/ckeditor/ckeditor.js"></script>
    <script>' .
    /* Use CKEditor for ALL <textarea>. */ '
      CKEDITOR.replaceAll();
    </script>' : '' ) .

    // Adds an easy link to validate the pages.
    ( $DEMO_MODE == 'Y' ? '
    <p><a href="http://validator.w3.org/check?uri=referer">'
     . '<img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" '
     . 'class="valid" /></a></p>' : '' )/* Close HTML page properly. */ . '
    </div>
    </body>
  </html>';
}

?>
