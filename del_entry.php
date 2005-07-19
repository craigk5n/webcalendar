<?php
include_once 'includes/init.php';

$my_event = false;
$can_edit = false;
$other_user = '';

// First, check to see if this user should be able to delete this event.
if ( $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin ) {
    $can_edit = true;
  } else if ( $readonly == "Y" ) {
    $can_edit = false;
  } else {
    $can_edit = false;
    $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
      "webcal_entry_user WHERE webcal_entry.cal_id = " .
      "webcal_entry_user.cal_id AND webcal_entry.cal_id = $id " .
      "AND (webcal_entry.cal_create_by = '$login' " .
      "OR webcal_entry_user.cal_login = '$login')";
    $res = dbi_query ( $sql );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 )
        $can_edit = true;
      dbi_free_result ( $res );
    }
  }
}

// See who owns the event.  Owner should be able to delete.
$res = dbi_query (
  "SELECT cal_create_by FROM webcal_entry WHERE cal_id = $id" );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $owner = $row[0];
  dbi_free_result ( $res );
  if ( $owner == $login || $is_assistant && ( $user == $owner ) ||
    $is_nonuser_admin && ( $user == $owner ) ) {
    $my_event = true;
    $can_edit = true;
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
if ( ! $can_edit && access_is_enabled () ) {
  if ( access_can_delete_user_calendar ( $user ) )
    $can_edit = true;
}

if ( ! $can_edit ) {
  $error = translate ( "You are not authorized" );
}

// Is this a repeating event?
$event_repeats = false;
$res = dbi_query ( "SELECT COUNT(cal_id) FROM webcal_entry_repeats " .
  "WHERE cal_id = $id" );
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
    $res = dbi_query ( "SELECT cal_date FROM webcal_entry WHERE cal_id = $id" );
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
  if ( ( $is_admin || $my_event ) &&
    ( empty ( $user ) || $user == $login ) ) {

    // Email participants that the event was deleted
    // First, get list of participants (with status Approved or
    // Waiting on approval).
    $sql = "SELECT cal_login FROM webcal_entry_user WHERE cal_id = $id " .
      "AND cal_status IN ('A','W')";
    $res = dbi_query ( $sql );
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
      "FROM webcal_entry WHERE cal_id = $id";
    $res = dbi_query($sql);
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $name = $row[0];
      $eventdate = $row[1];
      $eventtime = $row[2];
      dbi_free_result ( $res );
    }
    $TIME_FORMAT=24;
    for ( $i = 0; $i < count ( $partlogin ); $i++ ) {
      // Log the deletion
      activity_log ( $id, $login, $partlogin[$i], LOG_DELETE, "" );

      $do_send = get_pref_setting ( $partlogin[$i], "EMAIL_EVENT_DELETED" );
      $user_TIMEZONE = get_pref_setting ( $partlogin[$i], "TIMEZONE" );
      $user_language = get_pref_setting ( $partlogin[$i], "LANGUAGE" );
      user_load_variables ( $partlogin[$i], "temp" );
            
      if ( $partlogin[$i] != $login && $do_send == "Y" &&
        boss_must_be_notified ( $login, $partlogin[$i] ) && 
        strlen ( $tempemail ) && $send_email != "N" ) {
        if ( ( $GLOBALS['LANGUAGE'] != $user_language)  &&
          ! empty ( $user_language ) && ( $user_language != 'none' ) ) {
          reset_language ( $user_language );
        }
        $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
          translate("An appointment has been canceled for you by") .
          " " . $login_fullname .  ".\n" .
          translate("The subject was") . " \"" . $name . "\"\n" .
          translate("Date") . ": " . date_to_str ($thisdate) . "\n";
          if ( $eventtime != '-1' ) $msg .= translate("Time") . ": " . 
	  // Apply user's GMT offset and display their TZID
	  display_time ( $eventdate . $eventtime, 2, '', $user_TIMEZONE );
          $msg .= "\n\n";
        if ( strlen ( $login_email ) )
          $extra_hdrs = "From: $login_email\r\nX-Mailer: " .
            translate($application_name);
        else
          $extra_hdrs = "From: $email_fallback_from\r\nX-Mailer: " .
            translate($application_name);
        mail ( $tempemail,
          translate($application_name) . " " .
          translate("Notification") . ": " . $name,
          html_to_8bits ($msg), $extra_hdrs );
      }
    }

    // Instead of deleting from the database... mark it as deleted
    // by setting the status for each participant to "D" (instead
    // of "A"/Accepted, "W"/Waiting-on-approval or "R"/Rejected)
    if ( $override_repeat ) {
      dbi_query ( "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date ) " .
        "VALUES ( $id, $date )" );
      // Should we log this to the activity log???
    } else {
      // If it's a repeating event, delete any event exceptions
      // that were entered.
      if ( $event_repeats ) {
        $res = dbi_query ( "SELECT cal_id FROM webcal_entry " .
          "WHERE cal_group_id = $id" );
        if ( $res ) {
          $ex_events = array ();
          while ( $row = dbi_fetch_row ( $res ) ) {
            $ex_events[] = $row[0];
          }
          dbi_free_result ( $res );
          for ( $i = 0; $i < count ( $ex_events ); $i++ ) {
            $res = dbi_query ( "SELECT cal_login FROM " .
              "webcal_entry_user WHERE cal_id = $ex_events[$i]" );
            if ( $res ) {
              $delusers = array ();
              while ( $row = dbi_fetch_row ( $res ) ) {
                $delusers[] = $row[0];
              }
              dbi_free_result ( $res );
              for ( $j = 0; $j < count ( $delusers ); $j++ ) {
                // Log the deletion
                activity_log ( $ex_events[$i], $login, $delusers[$j],
                  LOG_DELETE, "" );
                dbi_query ( "UPDATE webcal_entry_user SET cal_status = 'D' " .
                  "WHERE cal_id = $ex_events[$i] " .
                  "AND cal_login = '$delusers[$j]'" );
              }
            }
          }
        }
      }

      // Now, mark event as deleted for all users.
      dbi_query ( "UPDATE webcal_entry_user SET cal_status = 'D' " .
        "WHERE cal_id = $id" );
    }
  } else {
    // Not the owner of the event and are not the admin or a user
    // was specified.
    // Just delete the event from this user's calendar.
    // We could just set the status to 'D' instead of deleting.
    // (but we would need to make some changes to edit_entry_handler.php
    // to accomodate this).
    $del_user = $login;
    if ( ! empty ( $user ) && $user != $login ) {
      if ( $is_admin || $my_event ||
        ( access_is_enabled () &&
        access_can_delete_user_calendar ( $user ) ) ) {
        $del_user = $user;
      } else {
        // Error: user cannot delete from other user's calendar
        $error = translate ( "You are not authorized" );
      }
    }
    if ( empty ( $error ) ) {
      dbi_query ( "DELETE FROM webcal_entry_user " .
        "WHERE cal_id = $id AND cal_login = '$del_user'" );
      activity_log ( $id, $login, $login, LOG_REJECT, "" );
    }
  }
}

$ret = getValue ( "ret" );
if ( ! empty ( $ret ) && $ret == "listall" ) {
  $url = "list_unapproved.php";
} else if ( ! empty ( $ret ) && $ret == "list" ) {
  $url = "list_unapproved.php";
  if ( ! empty ( $user ) )
    $url .= "?user=$user";
} else {
  $url = get_preferred_view ( "", empty ( $user ) ? "" : "user=$user" );
}

if ( empty ( $error ) ) {
  do_redirect ( $url );
  exit;
}
print_header();
?>

<h2><?php etranslate("Error")?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>

</body>
</html>
