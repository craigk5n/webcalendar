<?php
include_once 'includes/init.php';

$my_event = false;
$can_edit = false;

// First, check to see if this user should be able to delete this event.
if ( $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin || $is_assistant || $is_nonuser_admin) {
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
  if ( $owner == $login || $is_assistant || $is_nonuser_admin ) {
    $my_event = true;
    $can_edit = true;
  }
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

if ( ! $can_edit ) {
  $error = translate ( "You are not authorized" );
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
  if ( $is_admin || $my_event ) {

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
  
  
    // TODO: switch transation language based on user so each user
    // gets message in their selected language.
    for ( $i = 0; $i < count ( $partlogin ); $i++ ) {
      // Log the deletion
      activity_log ( $id, $login, $partlogin[$i], $LOG_DELETE, "" );

      $do_send = get_pref_setting ( $partlogin[$i], "EMAIL_EVENT_DELETED" );
      user_load_variables ( $partlogin[$i], "temp" );
      if ( $partlogin[$i] != $login && $do_send == "Y" && boss_must_be_notified ( $login, $partlogin[$i] ) && 
        strlen ( $tempemail ) && $send_email != "N" ) {
        $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
          translate("An appointment has been canceled for you by") .
          " " . $login_fullname .  ". " .
          translate("The subject was") . " \"" . $name . "\"\n" .
          translate("Date") . ": " . date_to_str ($eventdate) . "\n" .
          translate("Time") . ": " . display_time ($eventtime) . "\n\n";
        if ( strlen ( $login_email ) )
          $extra_hdrs = "From: $login_email\nX-Mailer: " .
            translate($application_name);
        else
          $extra_hdrs = "From: $email_fallback_from\nX-Mailer: " .
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
                  $LOG_DELETE, "" );
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
    // Not the owner of the event and are not the admin.
    // Just delete the event from this user's calendar.
    // We could just set the status to 'D' instead of deleting.
    // (but we would need to make some changes to edit_entry_handler.php
    // to accomodate this).
    dbi_query ( "DELETE FROM webcal_entry_user " .
      "WHERE cal_id = $id AND cal_login = '$login'" );
    activity_log ( $id, $login, $login, $LOG_REJECT, "" );
  }
}

$ret = getValue ( "ret" );
if ( ! empty ( $ret ) ) {
  if ( $ret == "list" ) {
    $url = "list_unapproved.php";
    if ( ! empty ( $user ) )
      $url .= "?user=$user";
  }
  else
    $url = "$STARTVIEW.php";
} else if ( strlen ( get_last_view() ) ) {
  $url = get_last_view();
} else {
  $redir = "";
  if ( $thisdate != "" )
    $redir = "?date=$thisdate";
  if ( $user != "" ) {
    if ( $redir != "" )
      $redir .= "&";
    $redir .= "user=$user";
  }
  $url = "$STARTVIEW.php" . $redir;
}
if ( empty ( $error ) ) {
  if ($is_assistant || $is_nonuser_admin)
     $url = $url . (strpos($url, "?") === false ? "?" : "&") . "user=$user";
  do_redirect ( $url );
  exit;
}
print_header();
?>

<h2 style="color:<?php echo $H2COLOR;?>;"><?php etranslate("Error")?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>

</body>
</html>
