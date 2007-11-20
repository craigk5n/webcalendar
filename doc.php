<?php
/*
 * $Id$
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
include_once 'includes/classes/Doc.class.php';

$blid = $WC->getValue ( 'blid', '-?[0-9]+', true );
$error = $res = '';

if ( empty ( $blid ) ) {
  $error = translate ( 'Invalid blob id' );
} else {
  $res = dbi_execute ( Doc::getSQLForDocId ( $blid ) );
  if ( ! $res ) {
   $error = db_error ();
  }
}

if ( empty ( $error ) ) {
  $row = dbi_fetch_row ( $res );
  if ( ! $row ) {
    $error = translate ( 'Invalid entry id' );
  } else {
    $doc =& new Doc ( $row );
    $eid = $doc->getId();
    $filename = $doc->getName ();
    $description = $doc->getDescription ();
    $owner = $doc->getLogin ();
    $size = $doc->getSize();
    $type = $doc->getType ();
    $mimetype = $doc->getMimeType();
    $filedata = $doc->getData ();
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
$log = $WC->getGET ( 'log' );
$show_log = ! empty ( $log );

if ( empty ( $eid ) )
  $can_view = true; // not associated with an event

if ( ! empty ( $eid ) && empty ( $error ) ) {
  if ( $WC->isAdmin() || $WC->isNonuserAdmin() ) {
    $can_view = true;
  } 
  if ( empty ( $eid ) || $eid <= 0 || ! is_numeric ( $eid ) ) {
    $error = translate( 'Invalid entry id' ) . '.'; 
  }

  if ( empty ( $error ) ) {
    // is this user a participant or the creator of the event?
    $sql = 'SELECT we.cal_id FROM webcal_entry we, webcal_entry_user weu 
      WHERE we.cal_id = weu.cal_id AND we.cal_id = ?
      AND (we.cal_create_by = ? OR weu.cal_login_id = ?)';
    $res = dbi_execute ( $sql, array( $eid, $WC->loginId(), 
	  $WC->loginId() ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 ) {
        $can_view = true;
        $is_my_event = true;
      }
      dbi_free_result ( $res );
    }

    if ( ! $can_view ) {
      $check_group = false;
      // if not a participant in the event, must be allowed to look at
      // other user's calendar.
      if ( getPref ( '_ALLOW_VIEW_OTHER' ) ) {
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
          AND weu.cal_login_id IN ( ';
        $query_params = array();
      $query_params[] = $eid;
      for ( $i = 0; $i < $cnt; $i++ ) {
          if ( $i > 0 ) {
            $sql .= ', ';
          }
          $sql .= '?';
          $query_params[] = $my_users[$i]['cal_login'];
        }
        $sql .= ' )';
        $res = dbi_execute ( $sql, $query_params );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          if ( $row && $row[0] > 0 ) {
            $can_view = true;
          }
          dbi_free_result ( $res );
        }
      }
      // If we didn't indicate we need to check groups, then this user
      // can't view this event.
      if ( ! $check_group ) {
        $can_view = false;
      }
    }
  }
  // If they still cannot view, make sure they are not looking at a nonuser
  // calendar event where the nonuser is the _only_ participant.
  if ( empty ( $error ) && ! $can_view   && getpref ( '_ENABLE_NONUSERS' ) ) {
    $nonusers = get_nonuser_cals ();
    $nonuser_lookup = array ();
    for ( $i = 0, $cnt = count ( $nonusers ); $i < $cnt; $i++ ) {
      $nonuser_lookup[$nonusers[$i]['cal_login']] = 1;
    }
    $sql = 'SELECT cal_login_id FROM webcal_entry_user ' .
      "WHERE cal_id = ? AND cal_status in ('A','W')";
    $res = dbi_execute ( $sql, array( $eid ) );
    $found_nonuser_cal = false;
    $found_reg_user = false;
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        if ( ! empty ( $nonuser_lookup[$row[0]] ) ) {
          $found_nonuser_cal = true;
        } else {
          $found_reg_user = true;
        }
      }
      dbi_free_result ( $res );
    }
    // Does this event contain only nonuser calendars as participants?
    // If so, then grant access.
    if ( $found_nonuser_cal && ! $found_reg_user ) {
      $can_view = true;
    }
  } 
  if ( empty ( $error ) && ! $can_view ) {
    $error = print_not_auth ();
  }
}

if ( ! empty ( $error ) ) {
  build_header ();
  echo print_error ( $error, true);
  echo print_trailer ();
  exit;
}

if ( $type == 'A' )
  $disp = 'attachment';
else
  $disp = 'inline';

// Print out data now.
Header ( "Content-Length: $size" );
Header ( "Content-Type: $mimetype" );

$description = preg_replace ( "/\n\r\t+/", " ", $description );
Header ( "Content-Description: $description" );

// Don't allow spaces in filenames
//$filename = preg_replace ( "/\n\r\t+/", "_", $filename );
//Header ( "Content-Disposition: $disp; filename=$filename" );
Header ( "Content-Disposition: filename=$filename" );

echo $filedata;
exit;

?>
