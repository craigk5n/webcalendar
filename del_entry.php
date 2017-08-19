<?php
include_once 'includes/init.php';
require 'includes/classes/WebCalMailer.class';
$mail = new WebCalMailer;

$can_edit = $my_event = false;
$other_user = '';

// First, check to see if this user should be able to delete this event.
if ( $id > 0 ) {
  // Then see who has access to edit this entry.
  $can_edit = ( $is_admin || $readonly != 'Y' );

  // If assistant is doing this, then we need to switch login to user in the SQL.
  $query_params = [];
  $query_params[] = $id;
  $sql = 'SELECT we.cal_id, we.cal_type FROM webcal_entry we,
    webcal_entry_user weu WHERE we.cal_id = weu.cal_id AND we.cal_id = ? ';
  if ( ! $is_admin ) {
    $sql .= ' AND ( we.cal_create_by = ? OR weu.cal_login = ? )';
    $sqlparm = ( $is_assistant ? $user : $login );
    $query_params[] = $sqlparm;
    $query_params[] = $sqlparm;
  }
  $res = dbi_execute ( $sql, $query_params );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] > 0 )
      $can_edit = true;

    $activity_type = $row[1];
    dbi_free_result ( $res );
  }
}
if ( strpos ( 'EM', $activity_type ) !== false ) {
  $log_delete = LOG_DELETE;
  $log_reject = LOG_REJECT;
} else {
  $log_delete = LOG_DELETE_T;
  $log_reject = LOG_REJECT_T;
}
// See who owns the event. Owner should be able to delete.
$res = dbi_execute ( 'SELECT cal_create_by
  FROM webcal_entry
  WHERE cal_id = ?', [$id] );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $owner = $row[0];
  dbi_free_result ( $res );

  if ( $owner == $login || $is_assistant && $user == $owner || $is_nonuser_admin )
    $can_edit = $my_event = true;

  // Check UAC.
  if ( access_is_enabled() && ! $is_admin )
    $can_edit = access_user_calendar ( 'edit', $owner );
}

// If the user is the event creator or their assistant
// allow them to delete the event from another user's calendar.
// It's essentially the same thing as editing the event and removing the
// user from the participants list.
if ( $my_event && ! empty ( $user ) && $user != $login && ! $is_assistant )
  $other_user = $user;

if ( $readonly == 'Y' )
  $can_edit = false;

// If User Access Control is enabled, check to see if the current
// user is allowed to delete events from the other user's calendar.
if ( ! $can_edit && access_is_enabled() && ! empty ( $user ) &&
    access_user_calendar ( 'edit', $user ) )
  $can_edit = true;

if ( ! $can_edit )
  $error = print_not_auth();

// Is this a repeating event?
$event_repeats = false;
$res = dbi_execute ( 'SELECT COUNT( cal_id ) FROM webcal_entry_repeats
  WHERE cal_id = ?', [$id] );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  if ( $row[0] > 0 )
    $event_repeats = true;

  dbi_free_result ( $res );
}
$override_repeat = false;
if ( ! empty ( $date ) && $event_repeats && ! empty ( $override ) )
  $override_repeat = true;

if ( $id > 0 && empty ( $error ) ) {
  if ( ! empty ( $date ) )
    $thisdate = $date;
  else {
    $res = dbi_execute ( 'SELECT cal_date
  FROM webcal_entry
  WHERE cal_id = ?', [$id] );
    if ( $res ) {
      // date format is 19991231
      $row = dbi_fetch_row ( $res );
      $thisdate = $row[0];
    }
  }

  // Only allow delete of webcal_entry & webcal_entry_repeats
  // if owner or admin, not participant.
  // If a user was specified, then only delete that user (not here) even if we
  // are the owner or an admin.
  if ( ( $is_admin || $my_event ) && ! $other_user ) {
    // Email participants that the event was deleted.
    // First, get list of participants (with status Approved or Waiting on approval).
    $res = dbi_execute ( 'SELECT cal_login FROM webcal_entry_user
  WHERE cal_id = ?
    AND cal_status IN ( "A", "W" )', [$id] );
    $partlogin = [];
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $partlogin[] = $row[0];
      }
      dbi_free_result ( $res );
    }
    // Get event name.
    $res = dbi_execute ( 'SELECT cal_name, cal_date, cal_time FROM webcal_entry
  WHERE cal_id = ?', [$id] );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $name = $row[0];
      $fmtdate = $row[1];
      $time = sprintf ( "%06d", $row[2] );
      dbi_free_result ( $res );
    }

    $eventstart = date_to_epoch ( $fmtdate . $time );
    $TIME_FORMAT = 24;
    for ( $i = 0, $cnt = count ( $partlogin ); $i < $cnt; $i++ ) {
      // Log the deletion.
      activity_log ( $id, $login, $partlogin[$i], $log_delete, '' );
      // Check UAC.
      $can_email = ( access_is_enabled()
        ? access_user_calendar ( 'email', $partlogin[$i], $login ) : false );

      // Don't email the logged in user.
      if ( $can_email && $partlogin[$i] != $login ) {
        set_env ( 'TZ', get_pref_setting ( $partlogin[$i], 'TIMEZONE' ) );
        $user_language = get_pref_setting ( $partlogin[$i], 'LANGUAGE' );
        user_load_variables ( $partlogin[$i], 'temp' );
        if ( ! $is_nonuser_admin && $partlogin[$i] != $login &&
          get_pref_setting ( $partlogin[$i], 'EMAIL_EVENT_DELETED' ) == 'Y' &&
            boss_must_be_notified ( $login, $partlogin[$i] ) && !
            empty ( $tempemail ) && $SEND_EMAIL != 'N' ) {
          reset_language ( empty ( $user_language ) || $user_language == 'none'
            ? $LANGUAGE : $user_language );
          // Use WebCalMailer class.
          $mail->WC_Send ( $login_fullname, $tempemail, $tempfullname, $name,
            str_replace ( 'XXX', $tempfullname, translate ( 'Hello, XXX.' ) )
             . ".\n\n" . str_replace ( 'XXX', $login_fullname,
              translate ( 'XXX has canceled an appointment.' ) ) . "\n"
             . str_replace ( 'XXX', $name, translate ( 'Subject XXX' ) ) . "\"\n"
             . str_replace ( 'XXX', date_to_str ( $thisdate ),
              translate ( 'Date XXX' ) ) . "\n"
             . ( ! empty ( $eventtime ) && $eventtime != '-1'
              ? str_replace ( 'XXX', display_time ( '', 2, $eventstart,
                  get_pref_setting ( $partlogin[$i], 'TIME_FORMAT' ) ),
                translate ( 'Time XXX' ) ) : '' ) . "\n\n",
            // Apply user's GMT offset and display their TZID.
            get_pref_setting ( $partlogin[$i], 'EMAIL_HTML' ), $login_email );
        }
      }
    }

    // Instead of deleting from the database...
    // mark it as deleted by setting the status for each participant to "D"
    // (instead of "A"/Accepted, "W"/Waiting-on-approval or "R"/Rejected).
    if ( $override_repeat ) {
      dbi_execute ( 'INSERT INTO webcal_entry_repeats_not
        ( cal_id, cal_date, cal_exdate ) VALUES ( ?, ?, ? )',
        [$id, $date, 1] );
      // Should we log this to the activity log???
    } else {
      // If it's a repeating event, delete any event exceptions that were entered.
      if ( $event_repeats ) {
        $res = dbi_execute ( 'SELECT cal_id
  FROM webcal_entry
  WHERE cal_group_id = ?', [$id] );
        if ( $res ) {
          $ex_events = [];
          while ( $row = dbi_fetch_row ( $res ) ) {
            $ex_events[] = $row[0];
          }
          dbi_free_result ( $res );
          for ( $i = 0, $cnt = count ( $ex_events ); $i < $cnt; $i++ ) {
            $res = dbi_execute ( 'SELECT cal_login
  FROM webcal_entry_user
  WHERE cal_id = ?', [$ex_events[$i]] );
            if ( $res ) {
              $delusers = [];
              while ( $row = dbi_fetch_row ( $res ) ) {
                $delusers[] = $row[0];
              }
              dbi_free_result ( $res );
              for ( $j = 0, $cnt = count ( $delusers ); $j < $cnt; $j++ ) {
                // Log the deletion.
                activity_log ( $ex_events[$i], $login, $delusers[$j],
                  $log_delete, '' );
                dbi_execute ( 'UPDATE webcal_entry_user SET cal_status = ?
  WHERE cal_id = ?
    AND cal_login = ?', ['D', $ex_events[$i], $delusers[$j]] );
              }
            }
          }
        }
      }

      // Now, mark event as deleted for all users.
      dbi_execute ( 'UPDATE webcal_entry_user
  SET cal_status = "D"
  WHERE cal_id = ?', [$id] );

      // Delete External users for this event
      dbi_execute ( 'DELETE FROM webcal_entry_ext_user
  WHERE cal_id = ?', [$id] );
    }
  } else {
    // Not the owner of the event, but participant or noncal_admin.
    // Just  set the status to 'D' instead of deleting.
    $del_user = ( ! empty ( $other_user ) ? $other_user : $login );
    if ( ! empty ( $user ) && $user != $login ) {
      if ( $is_admin || $my_event || ( $can_edit && $is_assistant ) ||
          ( access_is_enabled() &&
            access_user_calendar ( 'edit', $user ) ) ) {
        $del_user = $user;
      } else
        // Error: user cannot delete from other user's calendar.
        $error = print_not_auth();
    }
    if ( empty ( $error ) ) {
      if ( $override_repeat ) {
        dbi_execute ( 'INSERT INTO webcal_entry_repeats_not
          ( cal_id, cal_date, cal_exdate ) VALUES ( ?, ?, ? )',
          [$id, $date, 1] );
        // Should we log this to the activity log???
      } else {
        dbi_execute ( 'UPDATE webcal_entry_user SET cal_status = ?
  WHERE cal_id = ?
    AND cal_login = ?', ['D', $id, $del_user] );
        activity_log ( $id, $login, $login, $log_reject, '' );
      }
    }
  }
}

$ret = getValue ( 'ret' );
$return_view = get_last_view();

if ( ! empty ( $ret ) ) {
  if ( $ret == 'listall' )
    $url = 'list_unapproved.php';
  else
  if ( $ret == 'list' )
    $url = 'list_unapproved.php' . ( empty ( $user ) ? '' : '?user=' . $user );
} else
if ( ! empty ( $return_view ) )
  do_redirect ( $return_view );
else
  $url = get_preferred_view ( '', empty ( $user ) ? '' : 'user=' . $user );

// Return to login TIMEZONE.
set_env ( 'TZ', $TIMEZONE );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  do_redirect ( $url );
  exit;
}
// Process errors.
$mail->MailError ( $mailerError, $error );

?>
