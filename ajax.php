<?php
/* $Id$
 *
 * Description
 * This is the handler for Ajax httpXmlRequests.
 */
require_once 'includes/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';

$WebCalendar->initializeSecondPhase();

load_global_settings ();
load_user_preferences ();
$WebCalendar->setLanguage();

$name = getPostValue ( 'name' );
$page = getPostValue ( 'page' );
// We're processing edit_remotes Calendar ID field.
if ( $page == 'edit_remotes' || $page == 'edit_nonuser' ) {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_nonuser_cals
    WHERE cal_login = ?', array ( $NONUSER_PREFIX . $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // Presuming we are using '_NUC_' as $NONUSER_PREFIX.
    if ( $name == substr ( $row[0], strlen ( $NONUSER_PREFIX ) ) )
      // translate ( 'Duplicate Name' )
      echo str_replace ( 'XXX', $name, translate ( 'Duplicate Name XXX', true ) );
  }
} elseif ( $page == 'register' || $page == 'edit_user' ) {
  // We're processing username field.
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user WHERE cal_login = ?',
    array ( $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // translate ( 'Username already exists' )
    if ( $row[0] == $name )
      echo str_replace ( 'XXX', $name,
        translate ( 'Username XXX already exists.', true ) );
  }
} elseif ( $page == 'email' ) {
  // We're processing email field from any page field.
  $res = dbi_execute ( 'SELECT cal_email FROM webcal_user
    WHERE cal_email = ?', array ( $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // translate ( 'Email address already exists' )
    if ( $row[0] == $name )
      echo str_replace ( 'XXX', $name,
        translate ( 'Email address XXX already exists', true ) );
  }
}

?>
