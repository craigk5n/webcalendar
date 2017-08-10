<?php
/**
 * Page Description:
 *  This page will handle deletion of an entry in webcal_blob.
 *  This could be a comment or an attachment.
 *
 * Input Parameters:
 *  For GET:
 *    blid - unique id, corresponds to webcal_blob.cal_blob_id
 *
 * Security:
 *  Only the creator of the comment, the creator of the associated
 *  event, or an admin can delete.
 *  (An assistant can also delete their boss' documents.)
 * Comments:
 *  TODO: perhaps add email notification on this
 */
include_once 'includes/init.php';
include_once 'includes/classes/Doc.class';

$blid = getValue ( 'blid', '-?[0-9]+', true );
$can_delete = false; // until proven otherwise
$error = $name = $owner = $type = '';
$event_id = -1;

if ( $is_admin )
  $can_delete = true;

$res = dbi_execute ( Doc::getSQLForDocId ( $blid ) );
if ( ! $res )
  $error = db_error();
else {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $doc = new Doc( $row );
    $event_id = $doc->getEventId();
    $name = $doc->getName();
    $owner = $doc->getLogin();
    $type = $doc->getType();
    if ( $owner == $login || user_is_assistant ( $login, $owner ) )
      $can_delete = true;
  } else
    // document not found
    $error = str_replace ( 'XXX', $blid, translate ( 'Invalid entry id XXX.' ) );

  dbi_free_result ( $res );
}

if ( empty ( $error ) && ! $can_delete && $event_id > 0 ) {
  // See if current user is creator of associated event
  $res = dbi_execute ( 'SELECT cal_create_by
  FROM webcal_entry
  WHERE cal_id = ?', [$event_id] );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_owner = $row[0];
      if ( $event_owner == $login || user_is_assistant ( $login, $event_owner ) )
        $can_delete = true;
    }
    dbi_free_result ( $res );
  }
}

if ( empty ( $error ) && ! $can_delete )
  $error = print_not_auth();

if ( empty ( $error ) && $can_delete ) {
  if ( ! dbi_execute ( 'DELETE FROM webcal_blob
  WHERE cal_blob_id = ?', [$blid] ) )
    $error = db_error();
  else {
    if ( $event_id > 0 ) {
      $removeStr = translate ( 'Removed' );
      if ( $type == 'A' )
        activity_log ( $event_id, $login, $login, LOG_ATTACHMENT, $removeStr
         . ': ' . $name );
      elseif ( $type == 'C' )
        activity_log ( $event_id, $login, $login, LOG_COMMENT, $removeStr );
    }
    if ( $event_id > 0 )
      do_redirect ( 'view_entry.php?id=' . $event_id );

    do_redirect ( get_preferred_view() );
  }
}
// Some kind of error...
print_header();
echo print_error ( $error ) . print_trailer();

?>
