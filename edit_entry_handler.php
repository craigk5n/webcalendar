<?php
include_once 'includes/init.php';
include_once 'includes/site_extras.php';
load_user_categories ();

$error = "";

$do_override = false;
$old_id = -1;
if ( ! empty ( $override ) && ! empty ( $override_date ) ) {
  // override date specified.  user is going to create an exception
  // to a repeating event.
  $do_override = true;
  $old_id = $id;
}
// Remember previous cal_goup_id if present
$old_id = ( ! empty ( $parent ) ? $parent : $old_id );

if ( empty ( $TZ_OFFSET ) ) {
  $TZ_OFFSET = 0;
}

if ( empty ( $endhour ) ) {
  $endhour = 0;
}
// Modify the time to be server time rather than user time.
if ( ! empty ( $hour ) && ( $timetype == 'T' ) ) {
  // Convert to 24 hour before subtracting TZ_OFFSET so am/pm isn't confused.
  // Note this obsoltes any code in the file below that deals with am/pm
  // so the code can be deleted
  if ( $TIME_FORMAT == '12' && $hour < 12 ) {
    if ( $ampm == 'pm' )
     $hour += 12;
  } elseif ($TIME_FORMAT == '12' && $hour == '12' && $ampm == 'am' ) {
    $hour = 0;
  }
  if ( $GLOBALS['TIMED_EVT_LEN'] == 'E') {
    if ( isset ( $endhour ) && $TIME_FORMAT == '12' ) {
      // Convert end time to a twenty-four hour time scale.
      if ( $endampm == 'pm' && $endhour < 12 ) {
        $endhour += 12;
      } elseif ( $endampm == 'am' && $endhour == 12 ) {
        $endhour = 0;
      }
    }
  }
  $TIME_FORMAT=24;
  $hour -= $TZ_OFFSET;
  if ( $hour < 0 ) {
    $hour += 24;
    // adjust date
    $date = mktime ( 3, 0, 0, $month, $day, $year );
    $date -= $ONE_DAY;
    $month = date ( "m", $date );
    $day = date ( "d", $date );
    $year = date ( "Y", $date );
  }
  if ( $hour >= 24 ) {
    $hour -= 24;
    // adjust date
    $date = mktime ( 3, 0, 0, $month, $day, $year );
    $date += $ONE_DAY;
    $month = date ( "m", $date );
    $day = date ( "d", $date );
    $year = date ( "Y", $date );
  }

  // Must adjust $endhour too
  if ($TZ_OFFSET) {
    $endhour -= $TZ_OFFSET;
    if ( $endhour < 0 )   $endhour += 24;
    if ( $endhour >= 24 ) $endhour -= 24;
  }
}

// Make sure this user is really allowed to edit this event.
// Otherwise, someone could hand type in the URL to edit someone else's
// event.
// Can edit if:
//   - new event
//   - user is admin
//   - user created event
//   - user is participant
$can_edit = false;
// value may be needed later for recreating event
$old_create_by = ( ! empty ( $user )? $user : '');
if ( empty ( $id ) ) {
  // New event...
  $can_edit = true;
} else {
  // event owner or assistant event ?
  $sql = "SELECT cal_create_by FROM webcal_entry WHERE cal_id = '$id'";
  $res = dbi_query($sql);
  if ($res) {
    $row = dbi_fetch_row ( $res );
    // value may be needed later for recreating event
    $old_create_by = $row[0];
    if (( $row[0] == $login ) || (( $user == $row[0] ) && ( $is_assistant || $is_nonuser_admin )))
      $can_edit = true;
    dbi_free_result ( $res );
  } else
    $error = translate("Database error") . ": " . dbi_error ();
}
if ( $is_admin ) {
  $can_edit = true;
}
if ( empty ( $error ) && ! $can_edit ) {
  // is user a participant of that event ?
  $sql = "SELECT cal_id FROM webcal_entry_user WHERE cal_id = '$id' " .
    "AND cal_login = '$login' AND cal_status IN ('W','A')";
  $res = dbi_query ( $sql );
  if ($res) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty( $row[0] ) )
      $can_edit = true; // is participant
    dbi_free_result ( $res );
  } else
    $error = translate("Database error") . ": " . dbi_error ();
}

if ( ! $can_edit && empty ( $error ) ) {
  $error = translate ( "You are not authorized" );
}

// If display of participants is disabled, set the participant list
// to the event creator.  This also works for single-user mode.
// Basically, if no participants were selected (because there
// was no selection list available in the form or because the user
// refused to select any participant from the list), then we will
// assume the only participant is the current user.
if ( empty ( $participants[0] ) ) {
  $participants[0] = $login;
  // There might be a better way to do this, but if Admin sets this value,
  // WebCalendar should respect it
  if ( ! empty ( $public_access_default_selected ) &&
    $public_access_default_selected == "Y" ) {
    $participants[1] = "__public__";     
  }
}
// If "all day event" was selected, then we set the event time
// to be 12AM with a duration of 24 hours.
// We don't actually store the "all day event" flag per se.  This method
// makes conflict checking much simpler.  We just need to make sure
// that we don't screw up the day view (which normally starts the
// view with the first timed event).
// Note that if someone actually wants to create an event that starts
// at midnight and lasts exactly 24 hours, it will be treated in the
// same manner.

$duration_h = getValue ( "duration_h" );
$duration_m = getValue ( "duration_m" );

if ( $timetype == "A" ) {
  $duration_h = 24;
  $duration_m = 0;
  $hour = 0;
  $minute = 0;
}

$duration = ( $duration_h * 60 ) + $duration_m;
if ( $hour > 0 && $timetype != 'U' ) {
  if ( $TIME_FORMAT == '12' ) {
    $ampmt = $ampm;
    //This way, a user can pick am and still
    //enter a 24 hour clock time.
    if ($hour > 12 && $ampm == 'am') {
      $ampmt = 'pm';
    }
    $hour %= 12;
    if ( $ampmt == 'pm' ) {
      $hour += 12;
    }
  }
}
//echo "SERVER HOUR: $hour $ampm";

if ( $GLOBALS['TIMED_EVT_LEN'] == 'E' && $timetype == "T" ) {
    if ( ! isset ( $endhour ) ) {
        $duration = 0;
    } else {
      // Calculate duration.
      $endmins = ( 60 * (int) ( $endhour ) ) + $endminute;
      $startmins = ( 60 * $hour ) + $minute;
      $duration = $endmins - $startmins;
    }
    if ( $duration < 0 ) {
        $duration = 0;
    }
}

// handle external participants
$ext_names = array ();
$ext_emails = array ();
$matches = array ();
$ext_count = 0;
if ( $single_user == "N" &&
  ! empty ( $allow_external_users ) && 
  $allow_external_users == "Y" &&
  ! empty ( $externalparticipants ) ) {
  $lines = explode ( "\n", $externalparticipants );
  if ( ! is_array ( $lines ) ) {
    $lines = array ( $externalparticipants );
  }
  if ( is_array ( $lines ) ) {
    for ( $i = 0; $i < count ( $lines ); $i++ ) {
      $ext_words = explode ( " ", $lines[$i] );
      if ( ! is_array ( $ext_words ) ) {
        $ext_words = array ( $lines[$i] );
      }
      if ( is_array ( $ext_words ) ) {
        $ext_names[$ext_count] = "";
        $ext_emails[$ext_count] = "";
        for ( $j = 0; $j < count ( $ext_words ); $j++ ) {
          // use regexp matching to pull email address out
          $ext_words[$j] = chop ( $ext_words[$j] ); // remove \r if there is one
          if ( preg_match ( "/<?\\S+@\\S+\\.\\S+>?/", $ext_words[$j],
            $matches ) ) {
            $ext_emails[$ext_count] = $matches[0];
            $ext_emails[$ext_count] = preg_replace ( "/[<>]/", "",
              $ext_emails[$ext_count] );
          } else {
            if ( strlen ( $ext_names[$ext_count] ) ) {
              $ext_names[$ext_count] .= " ";
            }
            $ext_names[$ext_count] .= $ext_words[$j];
          }
        }
        // Test for duplicate Names
        if ( $i > 0 ) {
          for ( $k = $i ; $k > 0 ; $k-- ) {
            if ( $ext_names[$i] == $ext_names[$k] ) { 
              $ext_names[$i]  .= "[$k]";     
            }
          }
        }
        if ( strlen ( $ext_emails[$ext_count] ) &&
          empty ( $ext_names[$ext_count] ) ) {
          $ext_names[$ext_count] = $ext_emails[$ext_count];
        }
        $ext_count++;
      }
    }
  }
}

// first check for any schedule conflicts
if ( empty ( $allow_conflict_override ) || $allow_conflict_override != "Y" ) {
  $confirm_conflicts = ""; // security precaution
}
if ( $allow_conflicts != "Y" && empty ( $confirm_conflicts ) &&
  strlen ( $hour ) > 0 && $timetype != 'U' ) {
  $date = mktime ( 3, 0, 0, $month, $day, $year );
  $str_cal_date = date ( "Ymd", $date );
  if ( strlen ( $hour ) > 0 ) {
    $str_cal_time = sprintf ( "%02d%02d00", $hour, $minute );
  }
  if ( ! empty ( $rpt_end_use ) ) {
    $endt = mktime ( 3, 0, 0, $rpt_month, $rpt_day,$rpt_year );
  } else {
    $endt = 'NULL';
  }

  if ($rpt_type == 'weekly') {
    $dayst = ( empty( $rpt_sun )  ? 'n' : 'y' )
      . (  empty( $rpt_mon )  ? 'n' : 'y' )
      . (  empty( $rpt_tue )  ? 'n' : 'y' )
      . (  empty( $rpt_wed )  ? 'n' : 'y' )
      . (  empty( $rpt_thu )  ? 'n' : 'y' )
      . (  empty( $rpt_fri )  ? 'n' : 'y' )
      . (  empty( $rpt_sat )  ? 'n' : 'y' );
  } else {
    $dayst = "nnnnnnn";
  }

  // Load exception days... but not for a new event (which can't have
  // exception dates yet)
  $ex_days = array ();
  if ( ! empty ( $id ) ) {
    $res = dbi_query ( "SELECT cal_date FROM webcal_entry_repeats_not " .
      "WHERE cal_id = $id" );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $ex_days[] = $row[0];
      }
      dbi_free_result ( $res );
    } else {
      $error = translate("Database error") . ": " . dbi_error ();
    }
  }

  $dates = get_all_dates ( $date, $rpt_type, $endt, $dayst,
    $ex_days, $rpt_freq );
    
  $conflicts = check_for_conflicts ( $dates, $duration, $hour, $minute,
    $participants, $login, empty ( $id ) ? 0 : $id );
}
if ( empty ( $error ) && ! empty ( $conflicts ) ) {
  $error = translate("The following conflicts with the suggested time") .
    ": <ul>$conflicts</ul>";
}
//Avoid Undefined variable message
$msg = '';
if ( empty ( $error ) ) {
  $newevent = true;
  // now add the entries
  if ( empty ( $id ) || $do_override ) {
    $res = dbi_query ( "SELECT MAX(cal_id) FROM webcal_entry" );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0] + 1;
      dbi_free_result ( $res );
    } else {
      $id = 1;
    }
  } else {
    $newevent = false;
    // save old status values of participants
    $sql = "SELECT cal_login, cal_status, cal_category FROM webcal_entry_user " .
      "WHERE cal_id = $id ";
    $res = dbi_query ( $sql );
    if ( $res ) {
      for ( $i = 0; $tmprow = dbi_fetch_row ( $res ); $i++ ) {
        $old_status[$tmprow[0]] = $tmprow[1]; 
        $old_category[$tmprow[0]] = $tmprow[2];
      }
      dbi_free_result ( $res );
    } else {
      $error = translate("Database error") . ": " . dbi_error ();
    }
    if ( empty ( $error ) ) {
      dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_entry_ext_user WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $id" );
    }
    $newevent = false;
  }

  if ( $do_override ) {
    $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date ) " .
      "VALUES ( $old_id, $override_date )";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate("Database error") . ": " . dbi_error ();
    }
  }
  $sql = "INSERT INTO webcal_entry ( cal_id, " .
    ( $old_id > 0 ? " cal_group_id, " : "" ) .
    "cal_create_by, cal_date, " .
    "cal_time, cal_mod_date, cal_mod_time, cal_duration, cal_priority, " .
    "cal_access, cal_type, cal_name, cal_description ) " .
    "VALUES ( $id, " .
    ( $old_id > 0 ? " $old_id, " : "" ) .
    "'" . ( ! empty ( $old_create_by ) && 
      ( ( $is_admin && ! $newevent ) || $is_assistant || 
      $is_nonuser_admin ) ? $old_create_by : $login ) . "', ";
    
  $date = mktime ( 3, 0, 0, $month, $day, $year );
  $sql .= date ( "Ymd", $date ) . ", ";
  if ( strlen ( $hour ) > 0 && $timetype != 'U' ) {
    $sql .= sprintf ( "%02d%02d00, ", $hour, $minute );
  } else {
    $sql .= "-1, ";
  }
  $sql .= date ( "Ymd" ) . ", " . date ( "Gis" ) . ", ";
  $sql .= sprintf ( "%d, ", $duration );
  $sql .= ! empty ( $priority ) ? sprintf ( "%d,", $priority ) : "2,";
  $sql .= empty ( $access ) ? "'P', " : "'$access', ";
  if (  ! empty ( $rpt_type ) && $rpt_type != 'none' ) {
    $sql .= "'M', ";
  } else {
    $sql .= "'E', ";
  }

  if ( strlen ( $name ) == 0 ) {
    $name = translate("Unnamed Event");
  }
  $sql .= "'" . $name .  "', ";
  if ( strlen ( $description ) == 0 ) {
    $description = $name;
  }
  $sql .= "'" . $description . "' )";
  
  if ( empty ( $error ) ) {
    if ( ! dbi_query ( $sql ) ) {
      $error = translate("Database error") . ": " . dbi_error ();
    }
  }

  // log add/update
  activity_log ( $id, $login, ($is_assistant || $is_nonuser_admin ? $user : $login),
    $newevent ? $LOG_CREATE : $LOG_UPDATE, "" );
  
  if ( $single_user == "Y" ) {
    $participants[0] = $single_user_login;
  }

  // check if participants have been removed and send out emails
  if ( ! $newevent && count ( $old_status ) > 0 ) {  // nur bei Update!!!
    while ( list ( $old_participant, $dummy ) = each ( $old_status ) ) {
      $found_flag = false;
      for ( $i = 0; $i < count ( $participants ); $i++ ) {
        if ( $participants[$i] == $old_participant ) {
          $found_flag = true;
          break;
        }
      }
     $is_nonuser_admin = user_is_nonuser_admin ( $login, $old_participant );
      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if ( !$found_flag && !$is_nonuser_admin) {
        // only send mail if their email address is filled in
        $do_send = get_pref_setting ( $old_participant, "EMAIL_EVENT_DELETED" );
        $user_TZ = get_pref_setting ( $old_participant, "TZ_OFFSET" );
        $user_language = get_pref_setting ( $old_participant, "LANGUAGE" );
        user_load_variables ( $old_participant, "temp" );
        if ( $old_participant != $login && strlen ( $tempemail ) &&
          $do_send == "Y" && $send_email != "N" ) {

          // Want date/time in user's timezone
          $user_hour = $hour + $user_TZ;
          if ( $user_hour < 0 ) {
            $user_hour += 24;
            // adjust date
            $user_date = mktime ( 3, 0, 0, $month, $day, $year );
            $user_date -= $ONE_DAY;
            $user_month = date ( "m", $date );
            $user_day = date ( "d", $date );
            $user_year = date ( "Y", $date );
          } elseif ( $user_hour >= 24 ) {
            $user_hour -= 24;
            // adjust date
            $user_date = mktime ( 3, 0, 0, $month, $day, $year );
            $user_date += $ONE_DAY;
            $user_month = date ( "m", $date );
            $user_day = date ( "d", $date );
            $user_year = date ( "Y", $date );
          } else {
            $user_month = $month;
            $user_day = $day;
            $user_year = $year;
          }
          if (($GLOBALS['LANGUAGE'] != $user_language) && 
            ! empty ( $user_language ) && ( $user_language != 'none' )){
            reset_language ( $user_language );
          }
          //do_debug($user_language);    
          $fmtdate = sprintf ( "%04d%02d%02d", $user_year, $user_month, $user_day );
          $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
            translate("An appointment has been canceled for you by") .
            " " . $login_fullname .  ". " .
            translate("The subject was") . " \"" . $name . "\"\n\n" .
            translate("The description is") . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
            ( ( empty ( $user_hour ) && empty ( $minute ) ) ? "" :
            translate("Time") . ": " .
              display_time ( ( $user_hour * 10000 ) + ( $minute * 100 ), true ) ) .
            "\n\n\n";
          // add URL to event, if we can figure it out
          if ( ! empty ( $server_url ) ) {
            $url = $server_url .  "view_entry.php?id=" .  $id;
            $msg .= $url . "\n\n";
          }
         
          if ( strlen ( $login_email ) ) {
            $extra_hdrs = "From: $login_email\r\nX-Mailer: " . translate($application_name);
          } else {
            $extra_hdrs = "From: $email_fallback_from\r\nX-Mailer: " . translate($application_name);
          }
          mail ( $tempemail,
            translate($application_name) . " " . translate("Notification") . ": " . $name,
            html_to_8bits ($msg), $extra_hdrs );
          activity_log ( $id, $login, $old_participant, $LOG_NOTIFICATION,
     "User removed from participants list" );
        }
      }
    }
  }

  // now add participants and send out notifications
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    $my_cat_id = "";
    // Is the person adding the nonuser calendar admin
    $is_nonuser_admin = user_is_nonuser_admin ( $login, $participants[$i] );

    // if public access, require approval unless
    // $public_access_add_needs_approval is set to "N"
    if ( $login == "__public__" ) {
      if ( ! empty ( $public_access_add_needs_approval ) &&
        $public_access_add_needs_approval == "N" ) {
        $status = "A"; // no approval needed
      } else {
        // Approval required
        $status = "W"; // approval required
      }
      $my_cat_id = $cat_id;
    } else if ( ! $newevent ) {
      // keep the old status if no email will be sent
        $send_user_mail = ( empty ( $old_status[$participants[$i]] ) ||
          $entry_changed ) ?  true : false;
        $tmp_status = ( ! empty ( $old_status[$participants[$i]] ) && ! $send_user_mail ) ?
          $old_status[$participants[$i]] : "W";
      $status = ( $participants[$i] != $login && boss_must_approve_event ( $login, $participants[$i] ) && $require_approvals == "Y" && ! $is_nonuser_admin ) ?
        $tmp_status : "A";
      $tmp_cat = ( ! empty ( $old_category[$participants[$i]]) ) ?
        $old_category[$participants[$i]] : 'NULL';
      $tmp_cat = ( $participants[$i] == $user ) ? $cat_id : $tmp_cat;
      // Allow cat to be changed for public access (if admin user)
      if ( $participants[$i] == "__public__" && $is_admin ) {
        $tmp_cat = $cat_id;
      }

      // If user is admin and this event was previously approved for public,
      // keep it as approved even though date/time may have changed
      // This goes against stricter security, but it confuses users to have
      // to re-approve events they already approved.
      if ( $participants[$i] == "__public__" && $is_admin &&
        $old_status['__public__'] == 'A' ) {
        $status = 'A';
      }
      $my_cat_id = ( $participants[$i] != $login ) ? $tmp_cat : $cat_id;
      // If user is admin and
      // if it's a global cat, then set it for other users as well.
      if ( $is_admin && ! empty ( $categories[$cat_id] ) &&
        empty ( $category_owners[$cat_id] ) ) {
        // found categ. and owner set to NULL; it is global
        $my_cat_id = $cat_id;
      }
    } else {  // New Event
      $send_user_mail = true;
      $status = ( $participants[$i] != $login && 
        boss_must_approve_event ( $login, $participants[$i] ) && 
        $require_approvals == "Y" && ! $is_nonuser_admin ) ?
        "W" : "A";
      // If admin, no need to approve Public Access Events
      if ( $participants[$i] == "__public__" && $is_admin ) {
        $status = "A";
      }
      if ( $participants[$i] == $login ) {
        $my_cat_id = $cat_id;
      } else {
        // if it's a global cat, then set it for other users as well.
        if ( ! empty ( $categories[$cat_id] ) &&
          empty ( $category_owners[$cat_id] ) ) {
          // found cat. and owner set to NULL; it is global
          $my_cat_id = $cat_id;
        } else {
          // not global category
          $my_cat_id = 'NULL';
        }
      }
    }
    // Some users report that they get an error on duplicate keys
    // on the following add... As a safety measure, delete any
    // existing entry with the id.  Ignore the result.
    dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id " .
      "AND cal_login = '$participants[$i]'" );
    if ( empty ( $my_cat_id ) ) $my_cat_id = 'NULL';
    $sql = "INSERT INTO webcal_entry_user " .
      "( cal_id, cal_login, cal_status, cal_category ) VALUES ( $id, '" .
      $participants[$i] . "', '$status', $my_cat_id )";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate("Database error") . ": " . dbi_error ();
      break;
    } else {
      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if (!$is_nonuser_admin) {
        $from = $user_email;
        if ( empty ( $from ) && ! empty ( $email_fallback_from ) )
          $from = $email_fallback_from;
        // only send mail if their email address is filled in
        $do_send = get_pref_setting ( $participants[$i],
           $newevent ? "EMAIL_EVENT_ADDED" : "EMAIL_EVENT_UPDATED" );
        $user_TZ = get_pref_setting ( $participants[$i], "TZ_OFFSET" );
        $user_language = get_pref_setting ( $participants[$i], "LANGUAGE" );
        user_load_variables ( $participants[$i], "temp" );
        if ( $participants[$i] != $login && 
          boss_must_be_notified ( $login, $participants[$i] ) && 
          strlen ( $tempemail ) &&
          $do_send == "Y" && $send_user_mail && $send_email != "N" ) {

          // Want date/time in user's timezone
          $user_hour = $hour + $user_TZ;
          if ( $user_hour < 0 ) {
            $user_hour += 24;
            // adjust date
            $user_date = mktime ( 3, 0, 0, $month, $day, $year );
            $user_date -= $ONE_DAY;
            $user_month = date ( "m", $date );
            $user_day = date ( "d", $date );
            $user_year = date ( "Y", $date );
          } elseif ( $user_hour >= 24 ) {
            $user_hour -= 24;
            // adjust date
            $user_date = mktime ( 3, 0, 0, $month, $day, $year );
            $user_date += $ONE_DAY;
            $user_month = date ( "m", $date );
            $user_day = date ( "d", $date );
            $user_year = date ( "Y", $date );
          } else {
            $user_month = $month;
            $user_day = $day;
            $user_year = $year;
          }
          if (($GLOBALS['LANGUAGE'] != $user_language) && 
            ! empty ( $user_language ) && ( $user_language != 'none' )) {
             reset_language ( $user_language );
          }
          //do_debug($user_language);
          $fmtdate = sprintf ( "%04d%02d%02d", $user_year, $user_month, $user_day );
          $msg = translate("Hello") . ", " . $tempfullname . ".\n\n";
          if ( $newevent || ( empty ( $old_status[$participants[$i]] ) ) ) {
            $msg .= translate("A new appointment has been made for you by");
          } else {
            $msg .= translate("An appointment has been updated by");
          }
          $msg .= " " . $login_fullname .  ". " .
            translate("The subject is") . " \"" . $name . "\"\n\n" .
            translate("The description is") . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
            ( ( empty ( $user_hour ) && empty ( $minute ) ) ? "" :
            translate("Time") . ": " .
            display_time ( ( $user_hour * 10000 ) + ( $minute * 100 ), true ) . "\n" ) .
            translate("Please look on") . " " . translate($application_name) . " " .
            ( $require_approvals == "Y" ?
            translate("to accept or reject this appointment") :
            translate("to view this appointment") ) . ".";
          // add URL to event, if we can figure it out
          if ( ! empty ( $server_url ) ) {
            $url = $server_url .  "view_entry.php?id=" .  $id;
            $msg .= "\n\n" . $url;
          }
          if ( strlen ( $from ) ) {
            $extra_hdrs = "From: $from\r\nX-Mailer: " . translate($application_name);
          } else {
            $extra_hdrs = "X-Mailer: " . translate($application_name);
          }
          mail ( $tempemail,
            translate($application_name) . " " . translate("Notification") . ": " . $name,
            html_to_8bits ($msg), $extra_hdrs );
          activity_log ( $id, $login, $participants[$i], $LOG_NOTIFICATION, "" );
        }
      }
    }
  }

  // add external participants
  // send notification if enabled.
  if ( is_array ( $ext_names ) && is_array ( $ext_emails ) ) {
    for ( $i = 0; $i < count ( $ext_names ); $i++ ) {
      if ( strlen ( $ext_names[$i] ) ) {
        $sql = "INSERT INTO webcal_entry_ext_user " .
          "( cal_id, cal_fullname, cal_email ) VALUES ( " .
          "$id, '$ext_names[$i]', ";
        if ( strlen ( $ext_emails[$i] ) ) {
          $sql .= "'$ext_emails[$i]' )";
        } else {
          $sql .= "NULL )";
        }
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
        }
        // send mail notification if enabled
        // TODO: move this code into a function...
        if ( $external_notifications == "Y" && $send_email != "N" &&
          strlen ( $ext_emails[$i] ) > 0 ) {
          $fmtdate = sprintf ( "%04d%02d%02d", $year, $month, $day );
          // Strip [\d] from duplicate Names before emailing
          $ext_names[$i] = trim(preg_replace( '/\[[\d]]/', "", $ext_names[$i]) );
          $msg = translate("Hello") . ", " . $ext_names[$i] . ".\n\n";
          if ( $newevent ) {
            $msg .= translate("A new appointment has been made for you by");
          } else {
            $msg .= translate("An appointment has been updated by");
          }
          $msg .= " " . $login_fullname .  ". " .
            translate("The subject is") . " \"" . $name . "\"\n\n" .
            translate("The description is") . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
            ( ( empty ( $hour ) && empty ( $minute ) ) ? "" :
            translate("Time") . ": " .
            display_time ( ( $hour * 10000 ) + ( $minute * 100 ) ) . "\n" ) .
            translate("Please look on") . " " . translate($application_name) .
            ".";
          // add URL to event, if we can figure it out
          if ( ! empty ( $server_url ) ) {
            $url = $server_url .  "view_entry.php?id=" .  $id;
            $msg .= "\n\n" . $url;
          }
          if ( strlen ( $from ) ) {
            $extra_hdrs = "From: $from\r\nX-Mailer: " . translate($application_name);
          } else {
            $extra_hdrs = "X-Mailer: " . translate($application_name);
          }
          mail ( $ext_emails[$i],
            translate($application_name) . " " .
            translate("Notification") . ": " . $name,
            html_to_8bits ($msg), $extra_hdrs );
        
        }
      }
    }
  }

  // add site extras
  for ( $i = 0; $i < count ( $site_extras ) && empty ( $error ); $i++ ) {
    $sql = "";
    $extra_name = $site_extras[$i][0];
    $extra_type = $site_extras[$i][2];
    $extra_arg1 = $site_extras[$i][3];
    $extra_arg2 = $site_extras[$i][4];
    $value = $$extra_name;
    //echo "Looking for $extra_name... value = " . $value . " ... type = " .
    // $extra_type . "<br />\n";
    if ( strlen ( $$extra_name ) || $extra_type == $EXTRA_DATE ) {
      if ( $extra_type == $EXTRA_URL || $extra_type == $EXTRA_EMAIL ||
        $extra_type == $EXTRA_TEXT || $extra_type == $EXTRA_USER ||
        $extra_type == $EXTRA_MULTILINETEXT ||
        $extra_type == $EXTRA_SELECTLIST  ) {
        $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_data ) VALUES ( " .
          "$id, '$extra_name', $extra_type, '$value' )";
      } else if ( $extra_type == $EXTRA_REMINDER && $value == "1" ) {
        if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_DATE ) > 0 ) {
          $yname = $extra_name . "year";
          $mname = $extra_name . "month";
          $dname = $extra_name . "day";
          $edate = sprintf ( "%04d%02d%02d", $$yname, $$mname, $$dname );
          $sql = "INSERT INTO webcal_site_extras " .
            "( cal_id, cal_name, cal_type, cal_remind, cal_date ) VALUES ( " .
            "$id, '$extra_name', $extra_type, 1, $edate )";
        } else if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
          $dname = $extra_name . "_days";
          $hname = $extra_name . "_hours";
          $mname = $extra_name . "_minutes";
          $minutes = ( $$dname * 24 * 60 ) + ( $$hname * 60 ) + $$mname;
          $sql = "INSERT INTO webcal_site_extras " .
            "( cal_id, cal_name, cal_type, cal_remind, cal_data ) VALUES ( " .
            "$id, '$extra_name', $extra_type, 1, '" . $minutes . "' )";
        } else {
          $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_remind ) VALUES ( " .
          "$id, '$extra_name', $extra_type, 1 )";
        }
      } else if ( $extra_type == $EXTRA_DATE )  {
        $yname = $extra_name . "year";
        $mname = $extra_name . "month";
        $dname = $extra_name . "day";
        $edate = sprintf ( "%04d%02d%02d", $$yname, $$mname, $$dname );
        $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_date ) VALUES ( " .
          "$id, '$extra_name', $extra_type, $edate )";
      }
    }
    if ( strlen ( $sql ) && empty ( $error ) ) {
      //echo "SQL: $sql<BR>\n";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate("Database error") . ": " . dbi_error ();
      }
    }
  }

  // clearly, we want to delete the old repeats, before inserting new...
  if ( empty ( $error ) ) {
    if ( ! dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id") ) {
      $error = translate("Database error") . ": " . dbi_error ();
    }
    // add repeating info
    if ( ! empty ( $rpt_type ) && strlen ( $rpt_type ) && $rpt_type != 'none' ) {
      $freq = ( $rpt_freq ? $rpt_freq : 1 );
      if ( ! empty ( $rpt_end_use  ) ) {
        $end = sprintf ( "%04d%02d%02d", $rpt_year, $rpt_month, $rpt_day );
      } else {
        $end = 'NULL';
      }
      if ($rpt_type == 'weekly') {
        $days = ( empty( $rpt_sun )  ? 'n' : 'y' )
          . (  empty( $rpt_mon )  ? 'n' : 'y' )
          . (  empty( $rpt_tue )  ? 'n' : 'y' )
          . (  empty( $rpt_wed )  ? 'n' : 'y' )
          . (  empty( $rpt_thu )  ? 'n' : 'y' )
          . (  empty( $rpt_fri )  ? 'n' : 'y' )
          . (  empty( $rpt_sat )  ? 'n' : 'y' );
      } else {
        $days = "nnnnnnn";
      }
  
      $sql = "INSERT INTO webcal_entry_repeats ( cal_id, " .
        "cal_type, cal_end, cal_days, cal_frequency ) VALUES " .
        "( $id, '$rpt_type', $end, '$days', $freq )";
      dbi_query ( $sql );
      $msg .= "<span style=\"font-weight:bold;\">SQL:</span> $sql<br />\n<br />";
    }
  }
}

// If we were editing this event, then go back to the last view (week, day,
// month).  If this is a new event, then go to the preferred view for
// the date range that this event was added to.
if ( empty ( $error ) ) {
  $xdate = sprintf ( "%04d%02d%02d", $year, $month, $day );
  $user_args = ( empty ( $user ) ? '' : "user=$user" );
  send_to_preferred_view ( $xdate, $user_args );
}

print_header();
if ( strlen ( $conflicts ) ) { 
?>
<h2><?php etranslate("Scheduling Conflict")?></h2>

<?php etranslate("Your suggested time of")?> <span style="font-weight:bold;">
<?php
  if ( ! empty ( $allday ) && $allday == "Y" ) {
    etranslate("All day event");
  } else {
    $time = sprintf ( "%d%02d00", $hour, $minute );
    echo display_time ( $time );
    if ( $duration > 0 )
      echo "-" . display_time ( add_duration ( $time, $duration ) );
  }
?></span> <?php etranslate("conflicts with the following existing calendar entries")?>:
<ul>
<?php echo $conflicts; ?>
</ul>

<?php
// user can confirm conflicts
  echo "<form name=\"confirm\" method=\"post\">\n";
  if ( ! is_array ( $_POST ) && is_array ( $HTTP_POST_VARS ) )
    $_POST = $HTTP_POST_VARS;
  foreach ($_POST as $xkey=>$xval ) {
    if (is_array($xval)) {
      $xkey.="[]";
      foreach ( $xval as $ykey=>$yval ) {
        if (get_magic_quotes_gpc())
          $yval = stripslashes($yval);
        $yval = htmlentities  ( $yval );
        echo "<input type=\"hidden\" name=\"$xkey\" value=\"$yval\" />\n";
      }
    } else {
      if (get_magic_quotes_gpc())
        $xval = stripslashes($xval);
      $xval = htmlentities ( $xval );
      echo "<input type=\"hidden\" name=\"$xkey\" value=\"$xval\" />\n";
    }
  }
?>
<table>
 <tr>
<?php
  // Allow them to override a conflict if server settings allow it
  if ( ! empty ( $allow_conflict_override ) &&
    $allow_conflict_override == "Y" ) {
    echo "<td><input type=\"submit\" name=\"confirm_conflicts\" " .
      "value=\"" . translate("Save") . "\" /></td>\n";
  }
?>
   <td><input type="button" value="<?php etranslate("Cancel")?>" 
onclick="history.back()" /><td>
 </tr>
</table>
</form>

<?php } else { ?>
<h2><?php etranslate("Error")?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>
<?php } ?>

<?php print_trailer(); ?>
</body>
</html>
