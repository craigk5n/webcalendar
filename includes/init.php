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
$user = getValue ( "user", "[A-Za-z0-9_\.=@,\-]+", true );
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
    $u_url = "user=$user&amp;";
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

  //if ( $categories_enabled == "Y" && ( !$user || $user == $login ) ) {
  if ( $categories_enabled == "Y" ) {
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
    $caturl = "&amp;cat_id=$cat_id";

}

// Prints the HTML header and opening Body tag.
//      $includes - an array of additional files to include referenced from
//                  the includes directory
//      $HeadX - a variable containing any other data to be printed inside
//               the head tag (META, SCRIPT, etc)
//      $BodyX - a variable containing any other data to be printed inside
//               the Body tag (onload for example)
//	$disableCustom - do not include custom header (useful for small
//		popup windows, such as color selection)
//	$disableStyle - do not include the standard css
//
function print_header($includes = '', $HeadX = '', $BodyX = '',
  $disableCustom=false, $disableStyle=false) {
  global $application_name;
  global $FONTS,$WEEKENDBG,$THFG,$THBG;
  global $TABLECELLFG,$TODAYCELLBG,$TEXTCOLOR;
  global $POPUP_FG,$BGCOLOR;
  global $LANGUAGE;
  global $CUSTOM_HEADER, $CUSTOM_SCRIPT;
  $lang = '';
  if ( ! empty ( $LANGUAGE ) )
    $lang = languageToAbbrev ( $LANGUAGE );
  if ( empty ( $lang ) )
    $lang = 'en';

 // Start the header & Specify the charset
 // The charset is defined in the translation file.
 if ( ! empty ( $LANGUAGE ) ) {
   $charset = translate ( "charset" );
   if ( $charset != "charset" ) {
 echo "<?xml version=\"1.0\" encoding=\"$charset\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"$lang\" lang=\"$lang\">\n
 <head>\n<title>".translate($application_name)."</title>\n";
  } else {
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n
 <head>\n<title>".translate($application_name)."</title>\n";    }
 }

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
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_report_template " .
      "WHERE cal_template_type = 'S' and cal_report_id = 0" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        echo $row[0];
      }
      dbi_free_result ( $res );
    }
  }

  // Finish the header
  echo "</head>\n<body $BodyX>\n";

  // Add custom header if enabled
  if ( $CUSTOM_HEADER == 'Y' && ! $disableCustom ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_report_template " .
      "WHERE cal_template_type = 'H' and cal_report_id = 0" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        echo $row[0];
      }
      dbi_free_result ( $res );
    }
  }
}

function languageToAbbrev ( $name ) {
  global $browser_languages;
  foreach ( $browser_languages as $abbrev => $langname ) {
    if ( $langname == $name )
      return $abbrev;
  }
  return false;
}


// Print the common trailer.
// Include custom trailer if enabled
function print_trailer ( $include_nav_links=true, $closeDb=true,
  $disableCustom=false )
{
  global $CUSTOM_TRAILER, $c, $friendly, $STARTVIEW;
  global $login, $user, $cat_id, $categories_enabled, $thisyear,
    $thismonth, $thisday, $DATE_FORMAT_MY, $WEEK_START, $DATE_FORMAT_MD,
    $readonly, $is_admin, $public_access, $public_access_can_add,
    $single_user, $use_http_auth, $login_return_path, $require_approvals,
    $is_nonuser_admin, $public_access_others, $allow_view_other,
    $views, $reports_enabled, $LAYER_STATUS, $nonuser_enabled,
    $groups_enabled, $fullname, $has_boss;
  

  if ( $include_nav_links && empty ( $friendly ) ) {
    include_once "includes/trailer.php";
  }

  // Add custom trailer if enabled
  if ( $CUSTOM_TRAILER == 'Y' && ! $disableCustom && isset ( $c ) ) {
    $res = dbi_query (
      "SELECT cal_template_text FROM webcal_report_template " .
      "WHERE cal_template_type = 'T' and cal_report_id = 0" );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        echo $row[0];
      }
      dbi_free_result ( $res );
    }
  }

  if ( $closeDb ) {
    if ( isset ( $c ) )
      dbi_close ( $c );
    unset ( $c );
  }
}
?>
