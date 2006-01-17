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
 * - login.php
 * - week_ssi.php
 * - upcoming.php
 * - tools/send_reminders.php
 *
 * How to use:
 * 1. call include_once 'includes/init.php'; at the top of your script.
 * 2. call any other functions or includes not in this file that you need
 * 3. call the print_header function with proper arguments
 *
 * What gets called:
 *
 * - require_once 'includes/classes/WebCalendar.class';
 * - require_once 'includes/classes/Event.class';
 * - require_once 'includes/classes/RptEvent.class';
 * - include_once 'includes/assert.php';
 * - include_once 'includes/config.php';
 * - include_once 'includes/php-dbi.php';
 * - include_once 'includes/functions.php';
 * - include_once "includes/$user_inc";
 * - include_once 'includes/validate.php';
 * - include_once 'includes/site_extras.php';
 * - include_once 'includes/access.php';
 * - include_once 'includes/translate.php';
 *
 * Also, for month.php, day.php, week.php, week_details.php:
 * - {@link send_no_cache_header()};
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */

 
require_once 'includes/classes/WebCalendar.class';
require_once 'includes/classes/Event.class';
require_once 'includes/classes/RptEvent.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include_once 'includes/assert.php';
include_once 'includes/config.php';
include_once 'includes/php-dbi.php';
include_once 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include_once "includes/$user_inc";
include_once 'includes/validate.php';
include_once 'includes/site_extras.php';
include_once 'includes/access.php';

include_once 'includes/translate.php';

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
 */
function print_header($includes = '', $HeadX = '', $BodyX = '',
  $disableCustom=false, $disableStyle=false, $disableRSS=false ) {
  global $APPLICATION_NAME;
  global $FONTS,$WEEKENDBG,$THFG,$THBG,$PHP_SELF;
  global $TABLECELLFG,$TODAYCELLBG,$TEXTCOLOR;
  global $POPUP_FG,$BGCOLOR,$OTHERMONTHBG;
  global $LANGUAGE, $DISABLE_POPUPS, $MENU_ENABLED;
  global $CUSTOM_HEADER, $CUSTOM_SCRIPT;
  global $friendly, $DISPLAY_WEEKENDS, $DISPLAY_TASKS;
  global $bodyid, $self, $login, $browser;
  $lang = '';
  if ( ! empty ( $LANGUAGE ) )
    $lang = languageToAbbrev ( $LANGUAGE );
  if ( empty ( $lang ) )
    $lang = 'en';

  // Start the header & specify the charset
  // The charset is defined in the translation file
  if ( ! empty ( $LANGUAGE ) ) {
    $charset = translate ( "charset" );
    if ( $charset != "charset" ) {
      echo "<?xml version=\"1.0\" encoding=\"$charset\"?>\n" .
        "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
        "\"DTD/xhtml1-transitional.dtd\">\n" .
        "<html xmlns=\"http://www.w3.org/1999/xhtml\" " .
        "xml:lang=\"$lang\" lang=\"$lang\">\n" .
        "<head>\n" .
        "<meta http-equiv=\"Content-Type\" content=\"text/html; " .
        "charset=$charset\" />\n";
      echo "<title>".translate($APPLICATION_NAME)."</title>\n";
    } else {
      echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n" .
        "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
        "\"DTD/xhtml1-transitional.dtd\">\n" .
        "<html xmlns=\"http://www.w3.org/1999/xhtml\" " .
        "xml:lang=\"en\" lang=\"en\">\n" .
        "<head>\n" .
        "<title>".translate($APPLICATION_NAME)."</title>\n";
    }
  }

  echo "<script type=\"text/javascript\" src=\"includes/js/util.js\"></script>\n";

  // Menu control
  if ( !empty ( $friendly ) || $disableCustom ) $MENU_ENABLED = 'N';

  // Includes needed for the top menu
  if ( $MENU_ENABLED == 'Y' ) {
    echo "<script type=\"text/javascript\" src=\"includes/menu/JSCookMenu.js\"></script>\n";
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"includes/menu/Office/theme.css\" />\n";
    echo "<script type=\"text/javascript\" src=\"includes/menu/Office/theme.js\"></script>\n";
  }

  // Any other includes?
  if ( is_array ( $includes ) ) {
    foreach( $includes as $inc ){
      if ( $inc == 'js/popups.php' && ! empty ( $DISABLE_POPUPS ) && 
       $DISABLE_POPUPS == "Y" ) {
       //don't load popups.php javascript if DISABLE_POPUPS
      } else {
        include_once 'includes/'.$inc;
      }
    }
  }

  // Do we need anything else inside the header tag?
  if ($HeadX) echo $HeadX."\n";

  // Include the styles
  if ( ! $disableStyle ) {
    include_once 'includes/styles.php';
  }

  // Add custom script/stylesheet if enabled
  if ( $CUSTOM_SCRIPT == 'Y' && ! $disableCustom ) {
    echo load_template ( $login, 'S' );
  }

  // Include includes/print_styles.css as a media="print" stylesheet. When the
  // user clicks on the "Printer Friendly" link, $friendly will be non-empty,
  // including this as a normal stylesheet so they can see how it will look 
  // when printed. This maintains backwards-compatibility for browsers that 
  // don't support media="print" stylesheets
  echo "<link rel=\"stylesheet\" type=\"text/css\"" . ( empty ( $friendly ) ? " media=\"print\"" : "" ) . " href=\"includes/print_styles.css\" />\n";

  // Add RSS feed if publishing is enabled
  if ( ! empty ( $GLOBALS['RSS_ENABLED'] ) && $GLOBALS['RSS_ENABLED'] == 'Y' &&
    $login == '__public__' ||
    ( ! empty ( $GLOBALS['USER_RSS_ENABLED'] ) &&
    $GLOBALS['USER_RSS_ENABLED'] == 'Y' ) && $disableRSS == false ) {
    echo "<link rel=\"alternate\" type=\"application/rss+xml\" " .
      "title=\"" . htmlentities ( $APPLICATION_NAME ) .
      " [RSS 1.0]\" href=\"rss.php";
    // TODO: single-user mode, etc.
    if ( $login != '__public__' )
      echo "?user=" . $login;
    echo "\" />\n";
  }

  // Link to favicon
  echo "<link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\" />\n";

  // Finish the header
  echo "</head>\n<body";

  // Find the filename of this page and give the <body> tag the corresponding id
  $thisPage = substr($self, strrpos($self, '/') + 1);
  if ( isset( $bodyid[$thisPage] ) )
    echo " id=\"" . $bodyid[$thisPage] . "\"";

  // Add any extra parts to the <body> tag
  if ( ! empty( $BodyX ) )
    echo " $BodyX";
  echo ">\n";

  // Add custom header if enabled
  if ( $CUSTOM_HEADER == 'Y' && ! $disableCustom ) {
    echo load_template ( $login, 'H' );
  }

  // Add the top menu if enabled
  if ( $MENU_ENABLED == 'Y' )  include_once 'includes/menu.php';
}


/**
 * Prints the common trailer.
 *
 * @param bool $include_nav_links Should the standard navigation links be
 *                               included in the trailer?
 * @param bool $closeDb           Close the database connection when finished?
 * @param bool $disableCustom     Disable the custom trailer the administrator
 *                                has setup?  (This is useful for small popup
 *                                windows and pages being used in an iframe.)
 */
function print_trailer ( $include_nav_links=true, $closeDb=true,
  $disableCustom=false )
{
  global $CUSTOM_TRAILER, $c, $STARTVIEW, $DEMO_MODE;
  global $login, $user, $cat_id, $CATEGORIES_ENABLED, $thisyear,
    $thismonth, $thisday, $DATE_FORMAT_MY, $WEEK_START, $DATE_FORMAT_MD,
    $readonly, $is_admin, $PUBLIC_ACCESS, $PUBLIC_ACCESS_CAN_ADD,
    $single_user, $use_http_auth, $login_return_path, $REQUIRE_APPROVALS,
    $is_nonuser_admin, $PUBLIC_ACCESS_OTHERS, $ALLOW_VIEW_OTHER,
    $views, $REPORTS_ENABLED, $LAYER_STATUS, $NONUSER_ENABLED,
    $GROUPS_ENABLED, $fullname, $has_boss, $is_nonuser, $DISPLAY_TASKS;
  
  if ( $include_nav_links ) {
    include_once "includes/trailer.php";
  }

  // Add custom trailer if enabled
  if ( $CUSTOM_TRAILER == 'Y' && ! $disableCustom && isset ( $c ) ) {
    echo load_template ( $login, 'T' );
  }

  if ( $closeDb ) {
    if ( isset ( $c ) )
      dbi_close ( $c );
    unset ( $c );
  }
 // adds an easy link to validate the pages
 if ( $DEMO_MODE == "Y" ) {
     echo "<p><a href=\"http://validator.w3.org/check?uri=referer\"><img " .
       "src=\"http://www.w3.org/Icons/valid-xhtml10\" " .
       "alt=\"Valid XHTML 1.0!\" class=\"valid\"  /></a></p>";
 }  
}
?>
