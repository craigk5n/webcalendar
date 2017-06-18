<?php
/* $Id: ajax.php,v 1.16.2.9 2012/03/03 01:54:14 cknudsen Exp $
 *
 * Description
 * This is the handler for Ajax httpXmlRequests.
 */
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';
require_valide_referring_url ();

$WebCalendar->initializeFirstPhase ();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';

$WebCalendar->initializeSecondPhase ();

load_global_settings ();
load_user_preferences ();
$WebCalendar->setLanguage ();

$cat_id = getValue ( 'cat_id', '-?[0-9,\-]*', true );
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
    // translate ( 'Username already exists.' )
    if ( $row[0] == $name )
      echo str_replace ( 'XXX', $name,
        translate ( 'Username XXX already exists.', true ) );
  }
} elseif ( $page == 'email' ) {
  // We're processing email field from any page.
  $res = dbi_execute ( 'SELECT cal_email FROM webcal_user WHERE cal_email = ?',
    array ( $name ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // translate ( 'Email address already exists.' )
    if ( $row[0] == $name )
      echo str_replace ( 'XXX', $name,
        translate ( 'Email address XXX already exists.', true ) );
  }
} elseif ( $page == 'minitask' ) {
  $name = ( ! empty ( $name ) ? $name : 0 );
  require_once 'includes/classes/Event.class';
  require_once 'includes/classes/RptEvent.class';
  include_once 'includes/gradient.php';
  $column_array = array ( 'we.cal_priority', 'we.cal_name',
    'we.cal_due_date', 'weu.cal_percent' );
  $task_filter = ' ORDER BY ' . $column_array[$name % 4]
   . ( $name > 3 ? ' ASC' : ' DESC' );
  echo display_small_tasks ( $cat_id );
}

?>
