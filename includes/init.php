<?php
//--------------------------------------------------------------------------
// init.php written by Jeff Hoover
// - simplifies script initialization
// - puts HTML headers in an easy to call function
//
// ** NOTE that the following scripts do not use this file:
//  - login.php
//  - week_ssi.php
//  - tools/send_reminders.php
//
// How to use:
// 1. call include_once 'includes/init.php'; at the top of your script.
// 2. call any other functions or includes not in this file that you need
// 3. call the print_header function with proper arguments
//--------------------------------------------------------------------------

// Get script name
preg_match("/\/(\w+\.php)/",$_SERVER['PHP_SELF'],$match);
$SCRIPT = $match[1];
unset($match); // clean-up

// Several files need a no-cache header and some of the same code
$special = array('month.php', 'day.php', 'week.php', 'week_details.php');
$DMW = in_array($SCRIPT, $special);
unset($special); // clean-up

include_once 'includes/config.php';
include_once 'includes/php-dbi.php';
include_once 'includes/functions.php';
include_once "includes/$user_inc";
include_once 'includes/validate.php';
include_once 'includes/connect.php';

load_global_settings ();

if ( empty ( $ovrd ) )
   load_user_preferences ();

include_once 'includes/translate.php';

// Load if $SCRIPT is in $special array:
if ($DMW) {
  
  // Tell the browser not to cache
  send_no_cache_header ();

  if ( empty ( $friendly ) && empty ( $user ) )
    remember_this_view ();

  if ( $allow_view_other != 'Y' && ! $is_admin )
    $user = "";

  $can_add = ( $readonly == "N" || $is_admin == "Y" );
  if ( $public_access == "Y" && $login == "__public__" ) {
    if ( $public_access_can_add != "Y" )
      $can_add = false;
    if ( $public_access_others != "Y" )
      $user = ""; // security precaution
  }

  if ( ! empty ( $user ) ) {
    $u_url = "user=$user&";
    user_load_variables ( $user, "user_" );
  } else {
    $u_url = "";
    $user_fullname = $fullname;
  }

  if ( empty ( $friendly ) ) {
    $friendly = 0;
    $hide_icons = false;
  } else {
    $hide_icons = true;
  }

  if ( ! empty ( $date ) && ! empty ( $date ) ) {
    $thisyear = substr ( $date, 0, 4 );
    $thismonth = substr ( $date, 4, 2 );
    $thisday = substr ( $date, 6, 2 );
  } else {
    if ( empty ( $month ) || $month == 0 )
      $thismonth = date("m");
    else
      $thismonth = $month;
    if ( empty ( $year ) || $year == 0 )
      $thisyear = date("Y");
    else
      $thisyear = $year;
  }

  if ( $categories_enabled == "Y" && ( !$user || $user == $login ) ) {
    if ( isset ( $cat_id ) ) {
      $cat_id = $cat_id;
    } elseif ( isset ( $CATEGORY_VIEW ) ) {
      $cat_id = $CATEGORY_VIEW;
    } else {
      $cat_id = '';
    }
  } else {
    $cat_id = '';
  }
  if ( empty ( $cat_id ) )
    $caturl = "";
  else
    $caturl = "&cat_id=$cat_id";

}

// Prints the HTML header and opening Body tag.
//      $includes - an array of additional files to include referenced from
//                  the includes directory
//      $HeadX - a variable containing any other data to be printed inside
//               the head tag (META, SCRIPT, etc)
//      $BodyX - a variable containing any other data to be printed inside
//               the Body tag (onload for example)
//
function print_header($includes = '', $HeadX = '', $BodyX = '') {
  global $application_name;
  global $FONTS,$WEEKENDBG,$THFG,$THBG;
  global $TABLECELLFG,$TODAYCELLBG,$TEXTCOLOR;
  global $POPUP_FG,$BGCOLOR;

  // Start the header
  echo "<HTML>\n<HEAD>\n<TITLE>".translate($application_name)."</TITLE>\n";

  // Include the styles
  include_once 'includes/styles.php';

  // Any other includes?
  if ( is_array ( $includes ) ) {
    foreach( $includes as $inc ){
      include_once 'includes/'.$inc;
    }
  }

  // Do we need anything else inside the header tag?
  if ($HeadX) echo $HeadX."\n";

  // Finish the header
  echo "</HEAD>\n<BODY BGCOLOR=\"$BGCOLOR\" CLASS=\"defaulttext\" $BodyX>\n";
}
?>