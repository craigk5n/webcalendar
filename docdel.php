<?php
/*
 * $Id$
 *
 * Page Description:
 *	This page will handle deletion of an entry in webcal_blob.
 *	This could be a comment or an attachment.
 *
 * Input Parameters:
 *	For GET:
 *	  blid - unique id, corresponds to webcal_blob.cal_blob_id
 *
 * Security:
 *	Only the creator of the comment, the creator of the associated
 *	event, or an admin can delete. 
 *	(An assistant can also delete their boss' documents.)
 * Comments:
 *	TODO: perhaps add email notification on this
 */
include_once 'includes/init.php';
include_once 'includes/classes/Doc.class';

$blid = getIntValue ( 'blid', true );
$owner = '';
$event_id = -1;
$error = '';
$can_delete = false; // until proven otherwise
$type = '';
$name = '';

if ( $is_admin )
  $can_delete = true;

$res = dbi_query ( Doc::getSQLForDocId ( $blid ) );
if ( ! $res ) {
  $error = translate("Database error") . ": " . dbi_error ();
} else {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $doc =& new Doc ( $row );
    $owner = $doc->getLogin ();
    $event_id = $doc->getEventId ();
    $type = $doc->getType ();
    $name = $doc->getName ();
    if ( $owner == $login )
      $can_delete = true;
    else if ( user_is_assistant ( $login, $owner ) )
      $can_delete = true;
  } else {
    // document not found
    $error = translate ( "Invalid entry id" ) . " '$blid'";
  }
  dbi_free_result ( $res );
}

if ( empty ( $error ) && ! $can_delete && $event_id > 0 ) {
  // See if current user is creator of associated event
  $res = dbi_query ( "SELECT cal_create_by FROM webcal_entry " .
    "WHERE cal_id = $event_id" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_owner = $row[0];
      if ( $event_owner == $login )
        $can_delete = true;
      else if ( user_is_assistant ( $login, $event_owner ) )
        $can_delete = true;
    }
    dbi_free_result ( $res );
  }
}

if ( empty ( $error ) && ! $can_delete ) {
  $error = translate ( "You are not authorized" );
}

if ( empty ( $error ) && $can_delete ) {
  if ( ! dbi_query ( "DELETE FROM webcal_blob WHERE cal_blob_id = $blid" ) ) {
    $error = translate ( "Database error" ) . ": " . dbi_error ();
  } else {
    if ( $event_id > 0 ) {
      if ( $type == 'C' )
        activity_log ( $event_id, $login, $login, LOG_COMMENT,
          translate ( "Removed" ) );
      else if ( $type == 'A' )
        activity_log ( $event_id, $login, $login, LOG_ATTACHMENT,
          translate ( "Removed" ) . ": " . $name );
    }
    if ( $event_id > 0 )
      do_redirect ( "view_entry.php?id=$event_id" );
    do_redirect ( get_preferred_view () );
  }
}

// Some kind of error...
print_header ();
echo '<h2>' . translate ( 'Error' ) . '</h2>' . $error;
print_trailer ();
?>
</body></html>


