<?php
/* $Id$ */
include_once 'includes/init.php';

$error = '';
// Only proceed if id was passed.
if ( $eid > 0 ) {
  // Double check to make sure user doesn't already have the event.
  $is_my_event = false;
  $res = dbi_execute ( 'SELECT cal_id FROM webcal_entry_user
    WHERE cal_login_id = ? AND cal_id = ?', array ( $WC->loginId(), $eid ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $eid ) {
      $is_my_event = true;
      echo str_replace ('XXX', $eid,
       translate ( 'Event XXX is already on your calendar.' ) );
      exit;
    }
    dbi_free_result ( $res );
  }
  // Now lets make sure the user is allowed to add the event (not private).
  $res = dbi_execute ( 'SELECT cal_access FROM webcal_entry WHERE cal_id = ?',
    array ( $eid ) );
  if ( ! $res ) {
    echo str_replace ('XXX', $eid, translate ( 'Invalid entry id XXX' ) );
    exit;
  }
  $mayNotAddStr =
  translate ( 'a XXX event may not be added to your calendar' );
  $row = dbi_fetch_row ( $res );

  if ( ! $is_my_event ) {
    if ( $row[0] == 'R' && ! $WC->isNonuserAdmin() ) {
      $is_private = true;
      echo str_replace ( 'XXX', translate ( 'private' ), $mayNotAddStr );
      exit;
    } else
    if ( $row[0] == 'C'  && ! $WC->isNonuserAdmin() ) {
      $is_private = true;
      echo str_replace ( 'XXX', translate ( 'confidential' ), $mayNotAddStr );
      exit;
    }
  } else
    $is_private = false;
  // Add the event.
  if ( ! _WC_READONLY && ! $is_my_event && ! $is_private ) {
    if ( ! dbi_execute ( 'INSERT INTO webcal_entry_user ( cal_id, cal_login_id,
      cal_status ) VALUES ( ?, ?, ? )', array ( $eid, $WC->loginId(), 'A' ) ) )
// translate ( 'Error adding event' )
      $error = str_replace ('XXX', dbi_error (),
        translate ( 'Error adding event XXX' ) );
  }
}

send_to_preferred_view ();
exit;

?>
