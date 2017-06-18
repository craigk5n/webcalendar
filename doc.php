<?php
/* $Id: doc.php,v 1.20.2.4 2011/04/27 00:27:35 rjones6061 Exp $
 *
 * Description:
 *  Obtain a binary object from the database and send it back to
 *  the browser using the correct mime type.
 *
 * Input Parameters:
 *  blid(*) - The unique identifier for this blob
 * (*) required field
 */
include_once 'includes/init.php';
include_once 'includes/classes/Doc.class';

$blid = getValue ( 'blid', '-?[0-9]+', true );
$error = $res = '';
$invalidIDStr = translate ( 'Invalid entry id XXX.' );

if ( empty ( $blid ) )
  $error = translate ( 'Invalid blob id' );
else {
  $res = dbi_execute ( Doc::getSQLForDocId ( $blid ) );
  if ( ! $res )
   $error = db_error ();
}

if ( empty ( $error ) ) {
  $row = dbi_fetch_row ( $res );
  if ( ! $row )
    $error = str_replace ( 'XXX', $blid, $invalidIDStr );
  else {
    $doc = new Doc ( $row );
    $description = $doc->getDescription ();
    $filedata = $doc->getData ();
    $filename = $doc->getName ();
    $id = $doc->getId ();
    $mimetype = $doc->getMimeType ();
    $owner = $doc->getLogin ();
    $size = $doc->getSize ();
    $type = $doc->getType ();
  }
  dbi_free_result ( $res );
}

// Make sure this user is allowed to look at this file.
// If the blob is associated with an event, then the user must be able
// to view the event in order to access this file.
// TODO: move all this code (and code in view_entry.php) to a common
// function named can_view_event or something similar.
$can_view = false;
$is_my_event = false;
$is_private = $is_confidential = false;
$log = getGetValue ( 'log' );
$show_log = ! empty ( $log );

if ( empty ( $id ) )
  $can_view = true; // not associated with an event

if ( ! empty ( $id ) && empty ( $error ) ) {
  if ( $is_admin || $is_nonuser_admin || $is_assistant )
    $can_view = true;

  if ( empty ( $id ) || $id <= 0 || ! is_numeric ( $id ) )
    $error = str_replace ( 'XXX', $id, $invalidIDStr );

  if ( empty ( $error ) ) {
    // is this user a participant or the creator of the event?
    $res = dbi_execute ( 'SELECT we.cal_id FROM webcal_entry we,
      webcal_entry_user weu WHERE we.cal_id = weu.cal_id AND we.cal_id = ?
      AND ( we.cal_create_by = ? OR weu.cal_login = ? )',
      array ( $id, $login, $login ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 ) {
        $can_view = true;
        $is_my_event = true;
      }
      dbi_free_result ( $res );
    }

    if ( ($login != '__public__') && ($PUBLIC_ACCESS_OTHERS == 'Y') ) {
      $can_view = true;
    }
    if ( ! $can_view ) {
      $check_group = false;
      // if not a participant in the event, must be allowed to look at
      // other user's calendar.
      if ( $login == '__public__' ) {
        if ( $PUBLIC_ACCESS_OTHERS == 'Y' )
          $check_group = true;
      } else {
        if ( $ALLOW_VIEW_OTHER == 'Y' )
          $check_group = true;
      }
      // If $check_group is true now, it means this user can look at the
      // event only if they are in the same group as some of the people in
      // the event.
      // This gets kind of tricky. If there is a participant from a different
      // group, do we still show it?  For now, the answer is no.
      // This could be configurable somehow, but how many lines of text would
      // it need in the admin page to describe this scenario?  Would confuse
      // 99.9% of users.
      // In summary, make sure at least one event participant is in one of
      // this user's groups.
      $my_users = get_my_users ();
      $cnt = count ( $my_users );
      if ( is_array ( $my_users ) && $cnt ) {
        $sql = 'SELECT we.cal_id FROM webcal_entry we, webcal_entry_user weu
          WHERE we.cal_id = weu.cal_id AND we.cal_id = ?
          AND weu.cal_login IN ( ';
        $query_params = array ();
      $query_params[] = $id;
      for ( $i = 0; $i < $cnt; $i++ ) {
          if ( $i > 0 ) {
            $sql .= ', ';
          }
          $sql .= '?';
          $query_params[] = $my_users[$i]['cal_login'];
        }
        $res = dbi_execute ( $sql . ' )', $query_params );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          if ( $row && $row[0] > 0 )
            $can_view = true;

          dbi_free_result ( $res );
        }
      }
      // If we didn't indicate we need to check groups, then this user
      // can't view this event.
      if ( ! $check_group && ! access_is_enabled ()  )
        $can_view = false;
    }
  }
  $hide_details = ( $login == '__public__' &&
    ! empty ( $OVERRIDE_PUBLIC ) && $OVERRIDE_PUBLIC == 'Y' );

  // If they still cannot view, make sure they are not looking at a nonuser
  // calendar event where the nonuser is the _only_ participant.
  if ( empty ( $error ) && ! $can_view && ! empty ( $NONUSER_ENABLED ) &&
    $NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_nonuser_cals ();
    $nonuser_lookup = array ();
    for ( $i = 0, $cnt = count ( $nonusers ); $i < $cnt; $i++ ) {
      $nonuser_lookup[$nonusers[$i]['cal_login']] = 1;
    }
    $res = dbi_execute ( 'SELECT cal_login FROM webcal_entry_user
      WHERE cal_id = ? AND cal_status in ( \'A\', \'W\' )', array ( $id ) );
    $found_nonuser_cal = $found_reg_user = false;
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        if ( ! empty ( $nonuser_lookup[$row[0]] ) )
          $found_nonuser_cal = true;
        else
          $found_reg_user = true;
      }
      dbi_free_result ( $res );
    }
    // Does this event contain only nonuser calendars as participants?
    // If so, then grant access.
    if ( $found_nonuser_cal && ! $found_reg_user )
      $can_view = true;
  }
  if ( empty ( $error ) && ! $can_view )
    $error = print_not_auth (8);
}

if ( ! empty ( $error ) ) {
  print_header ();
  echo print_error ( $error, true) . print_trailer ();
  exit;
}

$disp = ( $type == 'A' ? 'attachment' : 'inline' );

// Print out data now.
Header ( 'Content-Length: ' . $size );
Header ( 'Content-Type: ' . $mimetype );

$description = preg_replace ( "/\n\r\t+/", ' ', $description );
Header ( 'Content-Description: ' . $description );

// Don't allow spaces in filenames.
//$filename = preg_replace ( "/\n\r\t+/", "_", $filename );
//Header ( "Content-Disposition: $disp; filename=$filename" );
Header ( 'Content-Disposition: filename=' . $filename );

echo $filedata;
exit;

?>
