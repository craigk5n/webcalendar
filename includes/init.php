<?php
/*--------------------------------------------------------------------
 init.php written by Jeff Hoover
 - simplifies script initialization
 - puts HTML headers in an easy to call function

 ** NOTE that the following scripts do not use this file:
  - login.php
  - week_ssi.php
  - tools/send_reminders.php

 How to use:
 1. call include_once 'includes/init.php'; at the top of your script.
 2. call any other functions or includes not in this file that you need
 3. call the print_header function with proper arguments

 What gets called:

  include_once 'includes/config.php';
  include_once 'includes/php-dbi.php';
  include_once 'includes/functions.php';
  include_once "includes/$user_inc";
  include_once 'includes/validate.php';
  include_once 'includes/connect.php';
  load_global_settings ();
  load_user_preferences ();
  include_once 'includes/translate.php';
  include_once 'includes/styles.php';

 Also, for month.php, day.php, week.php, week_details.php:

  send_no_cache_header ();

//--------------------------------------------------------------------
*/

// Get script name
$self = $_SERVER['PHP_SELF'];
if ( empty ( $self ) )
  $self = $PHP_SELF;
preg_match ( "/\/(\w+\.php)/", $self, $match);
$SCRIPT = $match[1];

// Several files need a no-cache header and some of the same code
$special = array('month.php', 'day.php', 'week.php', 'week_details.php');
$DMW = in_array($SCRIPT, $special);

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

// error-check some commonly used form variable names
$id = getValue ( "id", "[0-9]+", true );
$user = getValue ( "user", "[A-Za-z_\.=@,]+", true );
$date = getValue ( "date", "[0-9]+" );
$year = getValue ( "year", "[0-9]+" );
$month = getValue ( "month", "[0-9]+" );
$hour = getValue ( "hour", "[0-9]+" );
$minute = getValue ( "minute", "[0-9]+" );
$cat_id = getValue ( "cat_id", "[0-9]+" );
$friendly = getValue ( "friendly", "[01]" );

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
    if ( $user == "__public__" )
      $user_fullname = translate ( $PUBLIC_ACCESS_FULLNAME );
  } else {
    $u_url = "";
    $user_fullname = $fullname;
    if ( $login == "__public__" )
      $user_fullname = translate ( $PUBLIC_ACCESS_FULLNAME );
  }

  if ( empty ( $friendly ) ) {
    $friendly = 0;
    $hide_icons = false;
  } else {
    $hide_icons = true;
  }

  set_today($date);

  if ( $categories_enabled == "Y" && ( !$user || $user == $login ) ) {
    if ( ! empty ( $cat_id ) ) {
      $cat_id = $cat_id;
    } elseif ( ! empty ( $CATEGORY_VIEW ) ) {
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
  global $LANGUAGE;

  // Start the header
  echo "<HTML>\n<HEAD>\n<TITLE>".translate($application_name)."</TITLE>\n";

  // Any other includes?
  if ( is_array ( $includes ) ) {
    foreach( $includes as $inc ){
      include_once 'includes/'.$inc;
    }
  }

  // Specify the charset via a meta tag.
  // The charset is defined in the translation file.
  if ( ! empty ( $LANGUAGE ) ) {
    $charset = translate ( "charset" );
    if ( $charset != "charset" ) {
      echo "<meta http-equiv=\"Content-type\" " .
        " content=\"text/html;charset=$charset\">\n";
    } else {
      echo "<!-- no charset defined for $LANGUAGE -->\n";
    }
  }

  // Do we need anything else inside the header tag?
  if ($HeadX) echo $HeadX."\n";

  // Include the styles
  include_once 'includes/styles.php';

  // Finish the header
  echo "</HEAD>\n<BODY BGCOLOR=\"$BGCOLOR\" CLASS=\"defaulttext\" $BodyX>\n";
}
?>
