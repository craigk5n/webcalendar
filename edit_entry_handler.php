<?php
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class' );
$mail = new WebCalMailer;

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

//Pass all string values through getPostValue
$name = getPostValue ( 'name' );
$description = getPostValue ( 'description' );
$cat_id = getPostValue ( 'cat_id' );
// Ensure all time variables are not empty
if ( empty ( $ampm ) ) $ampm = 'pm';
if ( empty ( $hour ) ) $hour = 0;
if ( empty ( $minute ) ) $minute = 0;
if  ( empty ( $endminute ) && !empty ( $endhour ) ||
  ( ! empty ( $endminute ) && $endminute < 0 ) ) {
  $endminute = 0;
} else if ( empty ( $endminute ) && empty ( $endhour ) ) {
  $endminute = $minute;
  $endampm = $ampm;
}
if ( empty ( $endhour ) || $endhour < 0 ) $endhour = $hour;


$duration_h = getValue ( "duration_h" );
$duration_m = getValue ( "duration_m" );
if ( empty ( $duration_h ) || $duration_h < 0 ) $duration_h = 0;
if ( empty ( $duration_m ) || $duration_m < 0 ) $duration_m = 0;

// Timed event.
if ( $timetype == 'T' )  {
  // Convert to 24 hour before subtracting tz_offset so am/pm isn't confused.
  // Note this obsoltes any code in the file below that deals with am/pm
  // so the code can be deleted
  if ( $TIME_FORMAT == '12' && $hour < 12 ) {
    if ( $ampm == 'pm' )
     $hour += 12;
  } elseif ($TIME_FORMAT == '12' && $hour == '12' && $ampm == 'am' ) {
    $hour = 0;
  }
  if ( $hour > 0  &&  $TIME_FORMAT == '12' ) {
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

  // Use end times
  if ( $TIMED_EVT_LEN == 'E') {
    if ( isset ( $endhour ) && $TIME_FORMAT == '12' ) {
      // Convert end time to a twenty-four hour time scale.
      if ( $endampm == 'pm' && $endhour < 12 ) {
        $endhour += 12;
      } elseif ( $endampm == 'am' && $endhour == 12 ) {
        $endhour = 0;
      }
    }
  } else {
    $endhour = 0;
    $endminute = 0;
  }
  $TIME_FORMAT=24;
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

// All Day Event
if ( $timetype == "A" ) {
  $duration_h = 24;
  $duration_m = 0;
  $hour = 0;
  $minute = 0;
  $endhour = 0;
  $endminute = 0;  
}

// Untimed Event
if ( $timetype == "U" ) {
  $duration_h = 0;
  $duration_m = 0;
  $hour = 0;
  $minute = 0;
  $endhour = 0;
  $endminute = 0;
}



// Combine all values to create event start date/time - User Time
$eventstart = mktime ( $hour, $minute, 0, $month, $day, $year );


//Create event stop from event  duration/end values
// Note: for any given event, either end times or durations are 0
if ( $TIMED_EVT_LEN == 'E') {
 $eventstophour= $endhour + $duration_h;
 $eventstopmin= $endminute + $duration_m;
} else {
 $eventstophour= $hour + $duration_h;
 $eventstopmin= $minute + $duration_m;
}
$eventstop = mktime ( $eventstophour, $eventstopmin, 0, $month, $day, $year );
 
  // Get this user's Timezone offset for this date/time  
  $tz_offset = get_tz_offset ( $TIMEZONE, $eventstart );
if ( $timetype == "T" ) { // All other types are time independent
  // Adjust eventstart  by Timezone offset to get GMT
  $eventstart -= ( $tz_offset[0] * 3600 );
  
  // Adjust eventstop  by Timezone offset to get GMT
  $eventstop -= ( $tz_offset[0] * 3600 );
}

 
// Calculate event duration
$duration = ( $eventstop - $eventstart ) / 60;
if ( $timetype == "T" && $duration < 0 ) {
 $duration = 0;
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
  if ( ! empty ( $PUBLIC_ACCESS_DEFAULT_SELECTED ) &&
    $PUBLIC_ACCESS_DEFAULT_SELECTED == "Y" ) {
    $participants[1] = "__public__";     
  }
}

//Convert $byxx arrays from form
rsort ($bydayext2);

for ( $i=0; $i<35;$i++) {
 if ( strlen ($bydayext2[$i]) < 2 || 
   $bydayext2[$i] == "        ") unset  ($bydayext2[$i]);
}
if ( ! empty ( $bydayext1 ) ) {
  $bydayext = array_merge($bydayext1,$bydayext2);
  $byday = implode (",", $bydayext );
} else {
  $byday = implode (",", $bydayext2 );
}
rsort ($bymonthday);
for ( $i=0; $i<31;$i++) {
 if ( strlen ($bymonthday[$i] < 1 || 
   $bymonthday[$i] == "      " ) ) unset  ($bymonthday[$i]);
}
$bymonthday = implode (",", $bymonthday );

rsort ($bysetpos2);
for ( $i=0; $i<31;$i++) {
 if ( strlen ($bysetpos2[$i]) < 1 || 
   $bysetpos2[$i] == "      ") unset  ($bysetpos2[$i]);
}
if ( ! empty ( $bysetpos2) ) $bysetpos = implode (",", $bysetpos2 );

if ( ! empty ( $bymonth) ) $bymonth = implode (",", $bymonth );

//This allows users to select on weekdays if daily
if ( $rpt_type == 'daily' && ! empty ( $weekdays_only ) ) {
 $dayst = "MO,TU,WE,TH,FR";
}
// first check for any schedule conflicts
if ( empty ( $ALLOW_CONFLICT_OVERRIDE ) || $ALLOW_CONFLICT_OVERRIDE != "Y" ) {
  $confirm_conflicts = ""; // security precaution
}
if ( $ALLOW_CONFLICTS != "Y" && empty ( $confirm_conflicts ) &&
  strlen ( $hour ) > 0 && $timetype != 'U' ) {

  if ( ! empty ( $rpt_year ) ) {
    $endt = mktime ( 0, 0, 0, $rpt_month, $rpt_day,$rpt_year );
  } else {
    $endt = 'NULL';
  }

  //We can now just use the $exceptions array from the form post
 if ( empty ( $exceptions ) ) $exceptions = array();
 if ( empty ( $inclusions ) ) $inclusions = array();
 if ( empty ( $bymonth ) ) $bymonth = '';
 if ( empty ( $byweekno ) ) $byweekno = '';
 if ( empty ( $byyearday ) ) $byyearday = '';
 if ( empty ( $bymonthday ) ) $bymonthday = '';
 if ( empty ( $byday ) ) $byday = '';
 if ( empty ( $bysetpos ) ) $bysetpos = ''; 
 if ( empty ( $count ) ) $count = '';

  $dates = get_all_dates ( $eventstart, $rpt_type, $rpt_freq, $bymonth,
   $byweekno, $byyearday, $bymonthday, $byday, $bysetpos, $count,
  $endt, $wkst, $exceptions, $inclusions );

  $conflicts = check_for_conflicts ( $dates, $duration, $eventstart,
    $participants, $login, empty ( $id ) ? 0 : $id );
}
if ( empty ( $error ) && ! empty ( $conflicts ) ) {
  $error = translate("The following conflicts with the suggested time") .
    ": <ul>$conflicts</ul>";
}

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
    $sql = "SELECT cal_login, cal_status FROM webcal_entry_user " .
      "WHERE cal_id = $id ";
    $res = dbi_query ( $sql );
    if ( $res ) {
      for ( $i = 0; $tmprow = dbi_fetch_row ( $res ); $i++ ) {
        $old_status[$tmprow[0]] = $tmprow[1]; 
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
    $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) " .
      "VALUES ( $old_id, $override_date, 1 )";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate("Database error") . ": " . dbi_error ();
    }
  }

  $sql = "INSERT INTO webcal_entry ( cal_id, " .
    ( $old_id > 0 ? " cal_group_id, " : "" ) .
    "cal_create_by, cal_date, " .
    "cal_time, cal_mod_date, cal_mod_time, cal_duration, cal_priority, " .
    "cal_access, cal_type, cal_name, cal_description, cal_location ) " .
    "VALUES ( $id, " .
    ( $old_id > 0 ? " $old_id, " : "" ) .
    "'" . ( ! empty ( $old_create_by ) && 
      ( ( $is_admin && ! $newevent ) || $is_assistant || 
      $is_nonuser_admin ) ? $old_create_by : $login ) . "', ";
    
  $sql .= date ( "Ymd", $eventstart ) . ", ";
  if ( strlen ( $hour ) > 0 && $timetype != 'U' ) {
    $sql .= date ( "His", $eventstart ) . ", ";
  } else {
    $sql .= "-1, ";
  }
  $sql .= gmdate ( "Ymd" ) . ", " . gmdate ( "Gis" ) . ", ";
  $sql .= sprintf ( "%d, ", $duration );
  $sql .= sprintf ( "%d, ", $priority );
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
  if ( strlen ( $description ) == 0  || $description == "<br />" ) {
    $description = $name;
  }
 $sql .= "'" . $description . "',";
 
 $location = ( ! empty ( $location )? $location:'');
  $sql .= "'" . $location . "' )";
  
 
 
  if ( empty ( $error ) ) {
    if ( ! dbi_query ( $sql ) ) {
      $error = translate("Database error") . ": " . dbi_error ();
    }
  }

  // log add/update
  activity_log ( $id, $login, ($is_assistant || $is_nonuser_admin ? $user : $login),
    $newevent ? LOG_CREATE : LOG_UPDATE, "" );
  
  if ( $single_user == "Y" ) {
    $participants[0] = $single_user_login;
  }

  $from = $login_email;
  if ( empty ( $from ) && ! empty ( $EMAIL_FALLBACK_FROM ) )
    $from = $EMAIL_FALLBACK_FROM;
  // check if participants have been removed and send out emails
  if ( ! $newevent && count ( $old_status ) > 0 ) {  
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
        $htmlmail = get_pref_setting ( $old_participant, "EMAIL_HTML" );
        $t_format = get_pref_setting ( $old_participant, "TIME_FORMAT" );
        $user_TIMEZONE = get_pref_setting ( $old_participant, "TIMEZONE" );
        $user_TZ = get_tz_offset ( $user_TIMEZONE, $eventstart );
        $user_language = get_pref_setting ( $old_participant, "LANGUAGE" );
        user_load_variables ( $old_participant, "temp" );
        
        
        if ( $old_participant != $login && strlen ( $tempemail ) &&
          $do_send == "Y" && $SEND_EMAIL != "N" ) {

          // Want date/time in user's timezone
          $user_eventstart = $eventstart  + ( $user_TZ[0] * 3600 );
       
       
          if ( empty ( $user_language ) || ( $user_language == 'none' )) {
             reset_language ( $LANGUAGE );
          } else {
             reset_language ( $user_language );
          }
  
          $fmtdate = date ( "Ymd", $user_eventstart ); 
          $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
            translate("An appointment has been canceled for you by") .
            " " . $login_fullname .  ".\n" .
            translate("The subject was") . " \"" . $name . "\"\n\n" .
            translate("The description is") . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
             ( $timetype != 'T'  ? "" :
            translate("Time") . ": " .
            // Apply user's GMT offset and display their TZID
            display_time ( date ( "YmdHis", $eventstart ), 2, '', 
						  $user_TIMEZONE, $t_format ) . "\n\n\n");
          // add URL to event, if we can figure it out
          if ( ! empty ( $SERVER_URL ) ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $SERVER_URL .  "view_entry.php?id=" .  $id . "&em=1";
            if ( $htmlmail == 'Y' ) {
              $url =  activate_urls ( $url ); 
            }
            $msg .= $url . "\n\n";
          }
          if ( strlen ( $from ) ) {
            $mail->From = $from;
            $mail->FromName = $login_fullname;
          } else {
            $mail->From = $login_fullname;
          }
          $mail->IsHTML( $htmlmail == 'Y' ? true : false );
          $mail->AddAddress( $tempemail, $tempfullname );
          $mail->Subject = translate($APPLICATION_NAME) . " " .
            translate("Notification") . ": " . $name;
          $mail->Body  = ( $htmlmail == 'Y' ? nl2br ( $msg ) : $msg );
          $mail->Send();
          $mail->ClearAll();          
          activity_log ( $id, $login, $old_participant, LOG_NOTIFICATION,
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
    // $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL is set to "N"
    if ( $login == "__public__" ) {
      if ( ! empty ( $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL ) &&
        $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == "N" ) {
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
      $status = ( $participants[$i] != $login && 
        boss_must_approve_event ( $login, $participants[$i] ) && 
        $REQUIRE_APPROVALS == "Y" && ! $is_nonuser_admin ) ?
        $tmp_status : "A";
      $tmp_cat = ( $participants[$i] == $user ) ? $cat_id : '';
      // Allow cat to be changed for public access (if admin user)
      if ( $participants[$i] == "__public__" && $is_admin ) {
        $tmp_cat = $cat_id;
      }

      // If user is admin and this event was previously approved for public,
      // keep it as approved even though date/time may have changed
      // This goes against stricter security, but it confuses users to have
      // to re-approve events they already approved.
      if ( $participants[$i] == "__public__" && $is_admin &&
        ( empty ( $old_status['__public__'] ) || $old_status['__public__'] == 'A' ) ) {
        $status = 'A';
      }
      $my_cat_id = ( $participants[$i] != $login ) ? $tmp_cat : $cat_id;
    } else {  // New Event
      $send_user_mail = true;
      $status = ( $participants[$i] != $login && 
        boss_must_approve_event ( $login, $participants[$i] ) && 
        $REQUIRE_APPROVALS == "Y" && ! $is_nonuser_admin ) ?
        "W" : "A";
      // If admin, no need to approve Public Access Events
      if ( $participants[$i] == "__public__" && $is_admin ) {
        $status = "A";
      }
      if ( $participants[$i] == $login ) {
        $my_cat_id = $cat_id;
      } else {
        $my_cat_id = 'NULL';
      }
    } //end new/old event
  
    // Some users report that they get an error on duplicate keys
    // on the following add... As a safety measure, delete any
    // existing entry with the id.  Ignore the result.
    dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id " .
      "AND cal_login = '$participants[$i]'" );
    $sql = "INSERT INTO webcal_entry_user " .
      "( cal_id, cal_login, cal_status ) VALUES ( $id, '" .
      $participants[$i] . "', '$status' )";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate("Database error") . ": " . dbi_error ();
      break;

    } else {
      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if (!$is_nonuser_admin) {
        // only send mail if their email address is filled in
        $do_send = get_pref_setting ( $participants[$i],
        $newevent ? "EMAIL_EVENT_ADDED" : "EMAIL_EVENT_UPDATED" );
        $htmlmail = get_pref_setting ( $participants[$i], "EMAIL_HTML" );
        $t_format = get_pref_setting ( $participants[$i], "TIME_FORMAT" );
        $user_TIMEZONE = get_pref_setting ( $participants[$i], "TIMEZONE" );
        $user_TZ = get_tz_offset ( $user_TIMEZONE, $eventstart );
        $user_language = get_pref_setting ( $participants[$i], "LANGUAGE" );
        user_load_variables ( $participants[$i], "temp" );
        if ( $participants[$i] != $login && 
          boss_must_be_notified ( $login, $participants[$i] ) && 
          strlen ( $tempemail ) &&
          $do_send == "Y" && $send_user_mail && $SEND_EMAIL != "N" ) {

          // Want date/time in user's timezone
          $user_eventstart = $eventstart  + ( $user_TZ[0] * 3600 );
         
          if ( empty ( $user_language ) || ( $user_language == 'none' )) {
             reset_language ( $LANGUAGE );
          } else {
             reset_language ( $user_language );
          }

          $fmtdate = date ( "Ymd", $user_eventstart ); 
          $msg = translate("Hello") . ", " . $tempfullname . ".\n\n";
          if ( $newevent || ( empty ( $old_status[$participants[$i]] ) ) ) {
            $msg .= translate("A new appointment has been made for you by");
          } else {
            $msg .= translate("An appointment has been updated by");
          }
          $msg .= " " . $login_fullname .  ".\n" .
            translate("The subject is") . " \"" . $name . "\"\n\n" .
            translate("The description is") . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
            ( $timetype != 'T' ? "" :
            translate("Time") . ": " .
            // Apply user's GMT offset and display their TZID
            display_time ( date ( "YmdHis", $eventstart ), 2, '', 
						  $user_TIMEZONE, $t_format ) . "\n" ) .
            translate("Please look on") . " " . translate($APPLICATION_NAME) . " " .
            ( $REQUIRE_APPROVALS == "Y" ?
            translate("to accept or reject this appointment") :
            translate("to view this appointment") ) . ".";
          // add URL to event, if we can figure it out
          if ( ! empty ( $SERVER_URL ) ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $SERVER_URL .  "view_entry.php?id=" .  $id . "&em=1";
            if ( $htmlmail == 'Y' ) {
              $url =  activate_urls ( $url ); 
            }
            $msg .= "\n\n" . $url;
          }
          //use WebCalMailer class
          if ( strlen ( $from ) ) {
            $mail->From = $from;
            $mail->FromName = $login_fullname;
          } else {
            $mail->From = $login_fullname;
          }
          $mail->IsHTML( $htmlmail == 'Y' ? true : false );
          $mail->AddAddress( $tempemail, $tempfullname );
          $mail->Subject = translate($APPLICATION_NAME) . " " .
            translate("Notification") . ": " . $name;
          $mail->Body  = ( $htmlmail == 'Y' ? nl2br ( $msg ) : $msg );                    
          $mail->Send();
          $mail->ClearAll();
          
          activity_log ( $id, $login, $participants[$i], LOG_NOTIFICATION, "" );
        }
      }
    }
  } //end for loop participants

  // add external participants
// handle external participants
$ext_names = array ();
$ext_emails = array ();
$matches = array ();
$ext_count = 0;
if ( $single_user == "N" &&
  ! empty ( $ALLOW_EXTERNAL_USERS ) && 
  $ALLOW_EXTERNAL_USERS == "Y" &&
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
        if ( $EXTERNAL_NOTIFICATIONS == "Y" && $SEND_EMAIL != "N" &&
          strlen ( $ext_emails[$i] ) > 0 ) {
          $fmtdate = date ( "Ymd", $eventstart ); 
          // Strip [\d] from duplicate Names before emailing
          $ext_names[$i] = trim(preg_replace( '/\[[\d]]/', "", $ext_names[$i]) );
          $msg = translate("Hello") . ", " . $ext_names[$i] . ".\n\n";
          if ( $newevent ) {
            $msg .= translate("A new appointment has been made for you by");
          } else {
            $msg .= translate("An appointment has been updated by");
          }
          $msg .= " " . $login_fullname .  ".\n" .
            translate("The subject is") . " \"" . $name . "\"\n\n" .
            translate("The description is") . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
            ( $timetype != 'T' ? "" :
            translate("Time") . ": " .
            // Do not apply TZ offset & display TZID, which is GMT
            display_time ( date ("YmdHis", $eventstart ), 3 ) . "\n" ) .
            translate("Please look on") . " " . translate($APPLICATION_NAME) .
            ".";
          // add URL to event, if we can figure it out
          //don't send HTML to external adresses
          $htmlmail = false;
          if ( ! empty ( $SERVER_URL )  ) {
            $url = $SERVER_URL .  "view_entry.php?id=" .  $id;
            if ( $htmlmail == 'Y' ) {
              $url =  activate_urls ( $url ); 
            }
            $msg .= "\n\n" . $url;
          }
          if ( strlen ( $from ) ) {
            $mail->From = $from;
            $mail->FromName = $login_fullname;
          } else {
            $mail->From = $login_fullname;
          }  
          $mail->IsHTML($htmlmail == "Y");
          $mail->AddAddress( $ext_emails[$i], $ext_names[$i] );
          $mail->Subject = translate($APPLICATION_NAME) . " " .
            translate("Notification") . ": " . $name;
          $mail->Body  = ( $htmlmail == 'Y' ? nl2br ( $msg ) : $msg );
          $mail->Send();
          $mail->ClearAll();
                  
        }
      }
    }
  } //end external mail

  //add categories
  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
   $is_admin ) ) ? $user : $login;
 dbi_query ( "DELETE FROM webcal_entry_categories WHERE cal_id = $id " .
    "AND ( cat_owner = '$cat_owner' OR cat_owner IS NULL )" );
 $categories = explode (",", $my_cat_id );
 sort ( $categories);
 for ( $i =0; $i < count( $categories ); $i++ ) {
   $names = array();
    $values = array(); 
  $names[] = 'cal_id';
  $values[]  = $id; 
  $names[] = 'cat_id';
  $values[]  = abs($categories[$i]);
  //we set cat_id negative in form if global
  if ( $categories[$i] > 0 ) {
    $names[] = 'cat_owner';
    $values[]  = "'$cat_owner'";
   $names[] = 'cat_order';
   $values[]  = ($i+1);
  } else {
   $names[] = 'cat_order';
   $values[]  = 99; //forces global categories to apear at the end of lists 
  } 
  $sql = "INSERT INTO webcal_entry_categories ( " . implode ( ", ", $names ) .
       " ) VALUES ( " . implode ( ", ", $values ) . " )"; 
  if ( ! dbi_query ( $sql ) ) {
   $error = translate("Database error") . ": " . dbi_error ();
   break;
  }
 }     
  // add site extras
 //we'll ignore the site_extra settings and use the form values
  if ( ! empty ( $serial_site_extras ) ) {
   $site_extras_additions = unserialize ( base64_decode ($serial_site_extras ) );
    $site_extras = array_merge ( $site_extras, $site_extras_additions );
  }
  for ( $i = 0; $i < count ( $site_extras ) && empty ( $error ); $i++ ) {
    $sql = "";
    $extra_name = $site_extras[$i][0];
    $extra_type = $site_extras[$i][2];
    $extra_arg1 = $site_extras[$i][3];
    $extra_arg2 = $site_extras[$i][4];
    $value = $$extra_name;
    //echo "Looking for $extra_name... value = " . $value . " ... type = " .
    // $extra_type . "<br />\n";
    if ( strlen ( $extra_name ) || $extra_type == EXTRA_DATE ) {
      if ( $extra_type == EXTRA_URL || $extra_type == EXTRA_EMAIL ||
        $extra_type == EXTRA_TEXT || $extra_type == EXTRA_USER ||
        $extra_type == EXTRA_MULTILINETEXT ||
        $extra_type == EXTRA_SELECTLIST  ) {

        $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_data ) VALUES ( " .
          "$id, '$extra_name', $extra_type, '$value' )";
      } else if ( $extra_type == EXTRA_REMINDER && $value == "1" ) {
        if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_DATE ) > 0 ) {
          $yname = $extra_name . "year";
          $mname = $extra_name . "month";
          $dname = $extra_name . "day";
          $edate = sprintf ( "%04d%02d%02d", $$yname, $$mname, $$dname );
          $sql = "INSERT INTO webcal_site_extras " .
            "( cal_id, cal_name, cal_type, cal_remind, cal_date ) VALUES ( " .
            "$id, '$extra_name', $extra_type, 1, $edate )";
        } else if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
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
      } else if ( $extra_type == EXTRA_DATE )  {
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
      //echo "SQL: $sql<br />\n";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate("Database error") . ": " . dbi_error ();
      }
    }
  } //end for site_extras loop

  // clearly, we want to delete the old repeats, before inserting new...
  if ( empty ( $error ) ) {
    if ( ! dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id") ) {
      $error = translate("Database error") . ": " . dbi_error ();
    }
  if ( ! dbi_query ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = $id") ) {
      $error = translate("Database error") . ": " . dbi_error ();
  }
    // add repeating info
    if ( ! empty ( $rpt_type ) && strlen ( $rpt_type ) && $rpt_type != 'none' ) {
      $freq = ( $rpt_freq ? $rpt_freq : 1 );
      if ( ! empty ( $rpt_year  ) ) {
        $end = sprintf ( "%04d%02d%02d", $rpt_year, $rpt_month, $rpt_day );
      } else {
        $end = 'NULL';
      }

     $names = array();
    $values = array();
    $names[] = 'cal_id';
    $values[]  = $id;
   
    $names[]  = 'cal_type';
       $values[] = "'" . $rpt_type . "'";
        
    $names[] = 'cal_frequency';
    $values[] = $freq;
 
   if (! empty ( $bymonth ) ){
     $names[] = 'cal_bymonth';
     $values[]  = "'" . $bymonth . "'";
   } 
    
   if (! empty ( $bymonthday ) ){
     $names[] = 'cal_bymonthday';
     $values[]  = "'" . $bymonthday . "'";
   } 
   if ( ! empty ( $byday ) ){
    $names[] = 'cal_byday';
    $values[] =  "'" . $byday . "'";
   }
   if (! empty ( $bysetpos ) ){
    $names[] = 'cal_bysetpos';
    $values[] = "'" . $bysetpos . "'";
   }
   if (! empty ( $byweekno ) ){
    $names[] = 'cal_byweekno';
    $values[] = "'" . $byweekno . "'";
   }
   if (! empty ( $byyearday ) ) {
    $names[] = 'cal_byyearday';
    $values[] = "'" . $byyearday . "'";
   }
   if (! empty ( $wkst ) ) {
    $names[] = 'cal_wkst';
    $values[] = "'" . $wkst . "'";
   }

   if (! empty ( $rpt_count ) && is_numeric ( $rpt_count )  ) {
    $names[] = 'cal_count';
    $values[] = $rpt_count;
   } 

   $names[] = 'cal_end';
      $values[] = $end;
   if ( $timetype == "T" && ! empty($eventstop) ) {
     $names[] = 'cal_endtime';         
        $values[] = date("His", $eventstop);
      }

   $sql = "INSERT INTO webcal_entry_repeats ( " . implode ( ", ", $names ) .
       " ) VALUES ( " . implode ( ", ", $values ) . " )"; 
      dbi_query ( $sql );
      $msg .= "<span style=\"font-weight:bold;\">SQL:</span> $sql<br />\n<br />";

   //We manually created exceptions
     if ( ! empty ($exceptions ) ) {
       for ( $i = 0; $i < count ( $exceptions ); $i++ ) {
            $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) " .
              "VALUES ( $id," . substr ($exceptions[$i],1,8 ) . ",". 
       ( substr ($exceptions[$i],0, 1 ) == "+"? 0 : 1 ) . " )";
            if ( ! dbi_query ( $sql ) ) {
              $error = translate("Database error") . ": " . dbi_error ();
            }
       }
    } //end exceptions
     //We manually created inclusions
     if ( ! empty ($inclusions ) ) {
       for ( $i = 0; $i < count ( $inclusions ); $i++ ) {
          $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) " .
            "VALUES ( $id, $exceptions[$i], 0 )";
          if ( ! dbi_query ( $sql ) ) {
              $error = translate("Database error") . ": " . dbi_error ();
          }
      }
     } //end inclusions
    } //end add repeating info
  } //end empty error
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
    // Pass the adjusted timestamp in case the date changed due to GMT offset 
    echo display_time ( $time, 1, $eventstart );
    if ( $duration > 0 ) {
      echo "-" . display_time ( add_duration ( $time, $duration ), 1, $eventstart );
    }
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
  while (list($xkey, $xval)=each($_POST)) {
    if (is_array($xval)) {
      $xkey.="[]";
      while (list($ykey, $yval)=each($xval)) {
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
  if ( ! empty ( $ALLOW_CONFLICT_OVERRIDE ) &&
    $ALLOW_CONFLICT_OVERRIDE == "Y" ) {
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
