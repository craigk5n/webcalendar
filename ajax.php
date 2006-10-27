<?php
/* $Id$
 *
 * Description
 * This is the handler for Ajax httpXmlRequests
 */
require_once 'includes/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';
include 'includes/translate.php';

$WebCalendar->initializeSecondPhase();

load_global_settings ();
load_user_preferences ();
$WebCalendar->setLanguage();

$page = getPostValue ( 'page' );
$name = getPostValue ( 'name' );
// we're processing edit_remotes Calendar ID field
if ( $page == 'edit_remotes' || $page == 'edit_nonuser' ) {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_nonuser_cals
    WHERE cal_login = ?', array ( $NONUSER_PREFIX . $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // assuming we are using '_NUC_' as $NONUSER_PREFIX
    if ( $name == substr ( $row[0], strlen ( $NONUSER_PREFIX ) ) )
      echo translate ( 'Duplicate Name', true ) . ": $name";
  }
} else
  // we're processing username field
  if ( $page == 'register' || $page == 'edit_user' ) {
    $res = dbi_execute ( 'SELECT cal_login FROM webcal_user WHERE cal_login = ?',
      array ( $name ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row[0] == $name )
        echo translate ( 'Username already exists', true ) . ": $name";
    }
  } else
    // we're processing email field from any page field
    if ( $page == 'email' ) {
      $res = dbi_execute ( 'SELECT cal_email FROM webcal_user
        WHERE cal_email = ?', array ( $name ) );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        if ( $row[0] == $name )
          echo translate ( 'Email address already exists', true ) . ": $name";
      }
    }

    ?>
