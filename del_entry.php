<?php
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class' );
$mail = new WebCalMailer;


$my_event = false;
$can_edit = false;
$other_user = '';

// First, check to see if this user should be able to delete this event.
if ( $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin ) {
    $can_edit = true;
  } else if ( $readonly == 'Y') {
    $can_edit = false;
  } else {
    $can_edit = false;
  }
    $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_type FROM webcal_entry, " .
      "webcal_entry_user WHERE webcal_entry.cal_id = " .
      "webcal_entry_user.cal_id AND webcal_entry.cal_id = ? " .
      "AND (webcal_entry.cal_create_by = ? " .
      "OR webcal_entry_user.cal_login = ?)";
    $res = dbi_execute ( $sql, array( $id, $login, $login ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 )
        $can_edit = true;
      $activity_type = $row[1];
      dbi_free_result ( $res );
    }
}
if ( $activity_type =='E' || $activity_type == 'M' ) {
  $log_delete = LOG_DELETE;
  $log_reject = LOG_REJECT;
} else {
  $log_delete = LOG_DELETE_T;
  $log_reject = LOG_REJECT_T;
}
// See who owns the event.  Owner should be able to delete.
$res = dbi_execute (
  "SELECT cal_create_by FROM webcal_entry WHERE cal_id = ?", array( $id ) );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $owner = $row[0];
  dbi_free_result ( $res );

  if ( $owner == $login || $is_assistant && ( $user == $owner ) ||
    $is_nonuser_admin ) {
    $my_event = true;
    $can_edit = true;
  }
  //check UAC
  if ( access_is_enabled () && ! $is_admin) {
    $can_edit = access_user_calendar ( 'edit', $owner );
  }
}

// If the user is the creator of the event, allow them to delete
// the event from another user's calendar.
// It's essentially the same thing as editing the event and removing the
// user from the participants list.
if ( $my_event && ! empty ( $user ) && $user != $login ) {
  $other_user = $user;
}

if ( $readonly == 'Y' )
  $can_edit = false;

// If User Access Control is enabled, check to see if the current
// user is allowed to delete events from the other user's calendar.
if ( ! $can_edit && access_is_enabled () && ! empty ( $user ) ) {
  if ( access_user_calendar ( 'edit', $user ) )
    $can_edit = true;
}

if ( ! $can_edit ) {
  $error = translate ( 'You are not authorized' );
}

// Is this a repeating event?
$event_repeats = false;
$res = dbi_execute ( "SELECT COUNT(cal_id) FROM webcal_entry_repeats " .
  "WHERE cal_id = ?", array( $id ) );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  if ( $row[0] > 0 )
    $event_repeats = true;
  dbi_free_result ( $res );
}
$override_repeat = false;
if ( ! empty ( $date ) && $event_repeats && ! empty ( $override ) ) {
  $override_repeat = true;
}

if ( $id > 0 && empty ( $error ) ) {
  if ( ! empty ( $date ) ) {
    $thisdate = $date;
  } else {
    $res = dbi_execute ( "SELECT cal_date FROM webcal_entry WHERE cal_id = ?", array( $id ) );
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
  
    // Email participants that the event was deleted
    // First, get list of participants (with status Approved or
    // Waiting on approval).
    $sql = "SELECT cal_login FROM webcal_entry_user WHERE cal_id = ? " .
      "AND cal_status IN ('A','W')";
    $res = dbi_execute ( $sql, array( $id ) );
    $partlogin = array ();
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] != $login )
          $partlogin[] = $row[0];
      }
      dbi_free_result($res);
    }

    // Get event name
    $sql = "SELECT cal_name, cal_date, cal_time " .
      "FROM webcal_entry WHERE cal_id = ?";
    $res = dbi_execute( $sql, array( $id ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $name = $row[0];
      $fmtdate = $row[1];
      $time = $row[2];
      dbi_free_result ( $res );
    }
    
    $eventstart = date_to_epoch ( $fmtdate . $time );
    $TIME_FORMAT=24;
    for ( $i = 0; $i < count ( $partlogin ); $i++ ) {
      // Log the deletion
      activity_log ( $id, $login, $partlogin[$i], $log_delete, "" );
      //check UAC
      $can_email = 'Y'; 
      if ( access_is_enabled () ) {
        $can_email = access_user_calendar ( 'email', $partlogin[$i], $login);
      }  
      if ( $can_email == 'Y' ) {  
        $do_send = get_pref_setting ( $partlogin[$i], 'EMAIL_EVENT_DELETED' );
        $htmlmail = get_pref_setting ( $partlogin[$i], 'EMAIL_HTML' );
        $t_format = get_pref_setting ( $partlogin[$i], 'TIME_FORMAT' );
        $user_TIMEZONE = get_pref_setting ( $partlogin[$i], 'TIMEZONE' );
        set_env ( 'TZ', $user_TIMEZONE );
        $user_language = get_pref_setting ( $partlogin[$i], 'LANGUAGE' );
        user_load_variables ( $partlogin[$i], 'temp' );         
        if ( ! $is_nonuser_admin && $partlogin[$i] != $login && $do_send == 'Y' &&
          boss_must_be_notified ( $login, $partlogin[$i] ) && 
          ! empty ( $tempemail ) && $SEND_EMAIL != 'N' ) {
            if ( empty ( $user_language ) || ( $user_language == 'none' )) {
               reset_language ( $LANGUAGE );
            } else {
               reset_language ( $user_language );
            }
          $msg = translate( 'Hello' ) . ", " . unhtmlentities( $tempfullname ). ".\n\n" .
            translate( 'An appointment has been canceled for you by' ) .
            " " . $login_fullname .  ".\n" .
            translate( 'The subject was' ) . " \"" . $name . "\"\n" .
            translate( 'Date' ) . ": " . date_to_str ($thisdate) . "\n";
            if ( ! empty ( $eventtime ) && $eventtime != '-1' ) 
              $msg .= translate( 'Time' ) . ": " . 
             // Apply user's GMT offset and display their TZID
             display_time ( '', 2, $eventstart, $t_format );
            $msg .= "\n\n";
            $msg = stripslashes ( $msg );
            //use WebCalMailer class
            $from = $login_email;
            if ( empty ( $from ) && ! empty ( $EMAIL_FALLBACK_FROM ) )
              $from = $EMAIL_FALLBACK_FROM;
            if ( strlen ( $from ) ) {
              $mail->From = $from;
              $mail->FromName = $login_fullname;
            } else {
              $mail->From = $login_fullname;
            }
            $mail->IsHTML( $htmlmail == 'Y' ? true : false );
            $mail->AddAddress( $tempemail, unhtmlentities( $tempfullname ) );
            $mail->WCSubject ( $name );
            $mail->Body  = $htmlmail == 'Y' ? nl2br ( $msg ) : $msg;;                    
            $mail->Send();
            $mail->ClearAll();
        }
      }
    }

    // Instead of deleting from the database... mark it as deleted
    // by setting the status for each participant to "D" (instead
    // of "A"/Accepted, "W"/Waiting-on-approval or "R"/Rejected)
    if ( $override_repeat ) {
      dbi_execute ( "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) " .
        "VALUES ( ?, ?, ? )", array( $id, $date, 1 ) );
      // Should we log this to the activity log???
    } else {
      // If it's a repeating event, delete any event exceptions
      // that were entered.
      if ( $event_repeats ) {
        $res = dbi_execute ( "SELECT cal_id FROM webcal_entry " .
          "WHERE cal_group_id = ?", array( $id ) );
        if ( $res ) {
          $ex_events = array ();
          while ( $row = dbi_fetch_row ( $res ) ) {
            $ex_events[] = $row[0];
          }
          dbi_free_result ( $res );
          for ( $i = 0; $i < count ( $ex_events ); $i++ ) {
            $res = dbi_execute ( "SELECT cal_login FROM " .
              "webcal_entry_user WHERE cal_id = ?", array( $ex_events[$i] ) );
            if ( $res ) {
              $delusers = array ();
              while ( $row = dbi_fetch_row ( $res ) ) {
                $delusers[] = $row[0];
              }
              dbi_free_result ( $res );
              for ( $j = 0; $j < count ( $delusers ); $j++ ) {
                // Log the deletion
                activity_log ( $ex_events[$i], $login, $delusers[$j],
                  $log_delete, "" );
                dbi_execute ( "UPDATE webcal_entry_user SET cal_status = ? " .
                  "WHERE cal_id = ? " .
                  "AND cal_login = ?", array( 'D', $ex_events[$i], $delusers[$j] ) );
              }
            }
          }
        }
      }

      // Now, mark event as deleted for all users.
      dbi_execute ( "UPDATE webcal_entry_user SET cal_status = 'D' " .
        "WHERE cal_id = ?", array( $id ) );
        
      // Delete External users for this event
      dbi_execute ( "DELETE FROM webcal_entry_ext_user " .
        "WHERE cal_id = ?", array( $id ) );
    }
  } else {
    // Not the owner of the event, but participant or noncal_admin
    // Just  set the status to 'D' instead of deleting.
    $del_user = ( ! empty ( $other_user ) ?  $other_user : $login );
    if ( ! empty ( $user ) && $user != $login ) {
      if ( $is_admin || $my_event ||
        ( access_is_enabled () &&
        access_user_calendar ( 'edit', $user ) ) ) {
        $del_user = $user;
      } else {
        // Error: user cannot delete from other user's calendar
        $error = translate ( 'You are not authorized' );
      }
    }
    if ( empty ( $error ) ) {
      dbi_execute ( "UPDATE webcal_entry_user SET cal_status = ? " .
        "WHERE cal_id = ? AND cal_login = ?", array( 'D', $id, $del_user ) );
      activity_log ( $id, $login, $login, $log_reject, "" );
    }
  }
}

$ret = getValue ( 'ret' );
$return_view = get_last_view ();
if ( ! empty ( $ret ) && $ret == 'listall' ) {
  $url = 'list_unapproved.php';
} else if ( ! empty ( $ret ) && $ret == 'list' ) {
  $url = 'list_unapproved.php';
  if ( ! empty ( $user ) )
    $url .= "?user=$user";
}else if ( ! empty ( $return_view ) ) {
    do_redirect ( $return_view );
} else {
  $url = get_preferred_view ( '', empty ( $user ) ? '' : "user=$user" );
}

//return to login TIMEZONE
set_env ( 'TZ', $TIMEZONE );
if ( empty ( $error ) ) {
  do_redirect ( $url );
  exit;
}
print_header();
?>

<h2><?php etranslate( 'Error' )?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>

</body>
</html>
