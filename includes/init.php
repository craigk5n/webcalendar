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
 * Returns a custom header, stylesheet or tailer.
 * The data will be loaded from the webcal_user_template table.
 * If the global variable $allow_external_header is set to 'Y', then
 * we load an external file using include.
 * This can have serious security issues since a
 * malicous user could open up /etc/passwd.
 *
 * @param string  $login	Current user login
 * @Param string  $type		type of template ('H' = header,
 *				'S' = stylesheet, 'T' = trailer)
 */
function load_template ( $login, $type )
{
  global $allow_user_header;
  $found = false;
  $ret = '';

  // First, check for a user-specific template
  if ( ! empty ( $allow_user_header ) && $allow_user_header == 'Y' ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_user_template " .
      "WHERE cal_type = '$type' and cal_login = '$login'" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        $ret = $row[0];
        $found = true;
      }
      dbi_free_result ( $res );
    }
  }

  // If no user-specific template, check for the system template
  if ( ! $found ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_user_template " .
      "WHERE cal_type = '$type' and cal_login = '__system__'" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        $ret = $row[0];
        $found = true;
      }
      dbi_free_result ( $res );
    }
  }

  // If still not found, the check the old location (WebCalendar 1.0 and
  // before)
  if ( ! $found ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_report_template " .
      "WHERE cal_template_type = '$type' and cal_report_id = 0" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        echo $row[0];
        $found = true;
      }
      dbi_free_result ( $res );
    }
  }

  if ( $found ) {
    if ( ! empty ( $GLOBALS['allow_external_header'] ) &&
      $GLOBALS['allow_external_header'] == 'Y' ) {
      if ( file_exists ( $ret ) ) {
        ob_start ();
        include "$ret";
        $ret = ob_get_contents ();
        ob_end_clean ();
      }
    }
  }
  
  return $ret;
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
 */
function print_header($includes = '', $HeadX = '', $BodyX = '',
  $disableCustom=false, $disableStyle=false) {
  global $application_name;
  global $FONTS,$WEEKENDBG,$THFG,$THBG,$PHP_SELF;
  global $TABLECELLFG,$TODAYCELLBG,$TEXTCOLOR;
  global $POPUP_FG,$BGCOLOR;
  global $LANGUAGE;
  global $CUSTOM_HEADER, $CUSTOM_SCRIPT;
  global $friendly;
  global $bodyid, $self, $login;
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
     echo "<title>".translate($application_name)."</title>\n";
   } else {
     echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n" .
       "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
       "\"DTD/xhtml1-transitional.dtd\">\n" .
       "<html xmlns=\"http://www.w3.org/1999/xhtml\" " .
       "xml:lang=\"en\" lang=\"en\">\n" .
       "<head>\n" .
       "<title>".translate($application_name)."</title>\n";
   }
 }

 echo "<script type=\"text/javascript\" src=\"includes/js/util.js\"></script>\n";

 // Any other includes?
 if ( is_array ( $includes ) ) {
   foreach( $includes as $inc ){
     include_once 'includes/'.$inc;
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
    $GLOBALS['USER_RSS_ENABLED'] == 'Y' ) ) {
    echo "<link rel=\"alternate\" type=\"application/rss+xml\" " .
      "title=\"" . htmlentities ( $application_name ) .
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
  global $CUSTOM_TRAILER, $c, $STARTVIEW;
  global $login, $user, $cat_id, $categories_enabled, $thisyear,
    $thismonth, $thisday, $DATE_FORMAT_MY, $WEEK_START, $DATE_FORMAT_MD,
    $readonly, $is_admin, $public_access, $public_access_can_add,
    $single_user, $use_http_auth, $login_return_path, $require_approvals,
    $is_nonuser_admin, $public_access_others, $allow_view_other,
    $views, $reports_enabled, $LAYER_STATUS, $nonuser_enabled,
    $groups_enabled, $fullname, $has_boss, $is_nonuser;
  
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
}
?>
