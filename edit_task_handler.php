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

//Pass all string values through getPostValue
$name = getPostValue ( 'name' );
$description = getPostValue ( 'description' );
$cat_id = getPostValue ( 'cat_id' );
// Ensure all time variables are not empty
if ( empty ( $cal_hour ) ) $cal_hour = 0;
if ( empty ( $cal_minute ) ) $cal_minute = 0;

if ( empty ( $due_hour ) ) $due_hour = 0;
if ( empty ( $due_minute ) ) $due_minute = 0;

if ( empty ( $percent ) ) $percent = 0;
// Always a Timed event.
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
  if ( $TIME_FORMAT == '12' && $due_hour < 12 ) {
    if ( $dampm == 'pm' )
     $due_hour += 12;
  } elseif ($TIME_FORMAT == '12' && $due_hour == '12' && $dampm == 'am' ) {
    $due_hour = 0;
  }
  if ( $due_hour > 0  &&  $TIME_FORMAT == '12' ) {
    $dampmt = $dampm;
    //This way, a user can pick am and still
    //enter a 24 hour clock time.
    if ($due_hour > 12 && $dampm == 'am') {
      $dampmt = 'pm';
    }
    $due_hour %= 12;
    if ( $dampmt == 'pm' ) {
      $due_hour += 12;
    }
  }

$TIME_FORMAT=24;


// If "all day event" was selected, then we set the event time
// to be 12AM with a duration of 24 hours.
// We don't actually store the "all day event" flag per se.  This method
// makes conflict checking much simpler.  We just need to make sure
// that we don't screw up the day view (which normally starts the
// view with the first timed event).
// Note that if someone actually wants to create an event that starts
// at midnight and lasts exactly 24 hours, it will be treated in the
// same manner.


// Combine all values to create event start date/time - User Time
$eventstart = mktime ( $hour, $minute, 0, $month, $day, $year );

// Combine all values to create event due date/time - User Time
$eventdue = mktime ( $due_hour, $due_minute, 0, $due_month, $due_day, $due_year );

// Combine all values to create completed date 
if ( ! empty ( $complete_year )  && ! empty ( $complete_month ) && ! empty ( $complete_day ) ) 
$eventcomplete =  sprintf( "%04d%02d%02d" , $complete_year, $complete_month, $complete_day );

// Get this user's Timezone offset for this date/time  
$tz_offset = get_tz_offset ( $TIMEZONE, $eventstart );
// Adjust eventstart  by Timezone offset to get GMT
$eventstart -= ( $tz_offset[0] * 3600 );
  
// Adjust eventstop  by Timezone offset to get GMT
$eventdue -= ( $tz_offset[0] * 3600 );

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
    if (( $row[0] == $login ) || (( $user == $row[0] ) && $is_assistant ))
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
}

// We don't do conflict checked for tasks

  if ( ! empty ( $rpt_end_use ) ) {
    $endt = mktime ( 0, 0, 0, $rpt_month, $rpt_day,$rpt_year );
  } else {
    $endt = 'NULL';
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
    // save old  values of participants
    $sql = "SELECT cal_login, cal_status, cal_percent " .
      "FROM webcal_entry_user WHERE cal_id = $id ";
    $res = dbi_query ( $sql );
    if ( $res ) {
      for ( $i = 0; $tmprow = dbi_fetch_row ( $res ); $i++ ) {
        $old_status[$tmprow[0]] = $tmprow[1]; 
        $old_percent[$tmprow[0]] = $tmprow[2];
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
    "cal_create_by, cal_date, cal_time, cal_completed, " .
    "cal_due_date, cal_due_time, cal_mod_date, cal_mod_time, cal_priority, " .
    "cal_access, cal_type, cal_name, cal_description, cal_location ) " .
    "VALUES ( $id, " .
    ( $old_id > 0 ? " $old_id, " : "" ) .
    "'" . ( ! empty ( $old_create_by ) && 
      ( ( $is_admin && ! $newevent ) || $is_assistant  ) ? $old_create_by : $login ) . "', ";
    
  $sql .= date ( "Ymd", $eventstart ) . ", ";
  $sql .= date ( "His", $eventstart ) . ", ";
 
 if ( ! empty ( $eventcomplete ) ) {
   $sql .= $eventcomplete . ", ";
 } else {
   $sql .= "NULL, "; 
 }
  
  $sql .= date ( "Ymd", $eventdue ) . ", ";
  $sql .= date ( "His", $eventdue ) . ", ";
 
  $sql .= gmdate ( "Ymd" ) . ", " . gmdate ( "Gis" ) . ", ";
  $sql .= sprintf ( "%d, ", $priority );
  $sql .= empty ( $access ) ? "'P', " : "'$access', ";
  if (  ! empty ( $rpt_type ) && $rpt_type != 'none' ) {
    $sql .= "'N', ";
  } else {
    $sql .= "'T', ";
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
  activity_log ( $id, $login, ($is_assistant ? $user : $login),
    $newevent ? LOG_CREATE_T : LOG_UPDATE_T, "" );
  
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

      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if ( !$found_flag ) {
        // only send mail if their email address is filled in
        $do_send = get_pref_setting ( $old_participant, "EMAIL_EVENT_DELETED" );
        $htmlmail = get_pref_setting ( $old_participant, "EMAIL_HTML" );
        $t_format = get_pref_setting (  $old_participant, "TIME_FORMAT" );
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
          $msg = translate("Hello", true) . ", " . unhtmlentities ( $tempfullname ) . ".\n\n" .
            translate("A task has been canceled for you by", true) .
            " " . $login_fullname .  ".\n" .
            translate("The subject was", true) . " \"" . $name . "\"\n\n" .
            translate("The description is", true) . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
             ( empty ( $timetype ) || $timetype != 'T'  ? "" :
            translate("Time") . ": " .
            // Apply user's GMT offset and display their TZID
            display_time ( date ( "YmdHis", $eventstart ), 2, '', 
              $user_TIMEZONE, $t_format ) . "\n\n\n");
          $msg = stripslashes ( $msg );          
          // add URL to event, if we can figure it out
          if ( ! empty ( $SERVER_URL ) ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $SERVER_URL .  "view_task.php?id=" .  $id . "&em=1";
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
          $mail->AddAddress( $tempemail, unhtmlentities ( $tempfullname ) );
          $mail->WCSubject ( $name );
          $mail->Body  = $htmlmail == 'Y' ? nl2br ( $msg ) : $msg;
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
    $my_percent = 0;
     if ( ! $newevent ) {
      // keep the old status if no email will be sent
        $send_user_mail = ( empty ( $old_status[$participants[$i]] ) ||
          $task_changed ) ?  true : false;
        $tmp_status = ( ! empty ( $old_status[$participants[$i]] ) && ! $send_user_mail ) ?
          $old_status[$participants[$i]] : "W";
      $status = ( $participants[$i] != $login && 
        boss_must_approve_event ( $login, $participants[$i] ) && 
         $REQUIRE_APPROVALS == "Y"  ) ?
        $tmp_status : "A";
      $tmp_percent = ( ! empty ( $old_percent[$participants[$i]]) ) ?
        $old_percent[$participants[$i]] : 0;
      $my_percent = ( $participants[$i] != $login ) ? $tmp_percent : $percent;
    } else {  // New Event
      $send_user_mail = true;
      $status = ( $participants[$i] != $login && 
        boss_must_approve_event ( $login, $participants[$i] ) && 
        $REQUIRE_APPROVALS == "Y"  ) ? "W" : "A";
    } //end new/old event
  
    // Some users report that they get an error on duplicate keys
    // on the following add... As a safety measure, delete any
    // existing entry with the id.  Ignore the result.
    dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id " .
      "AND cal_login = '$participants[$i]'" );
    $sql = "INSERT INTO webcal_entry_user " .
      "( cal_id, cal_login, cal_status, cal_percent ) VALUES ( $id, '" .
      $participants[$i] . "', '$status', $my_percent )";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate("Database error") . ": " . dbi_error ();
      break;
    } else {



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
          $msg = translate("Hello", true) . ", " . unhtmlentities ( $tempfullname ) . ".\n\n";
          if ( $newevent || ( empty ( $old_status[$participants[$i]] ) ) ) {
            $msg .= translate("A new task has been assigned to you by", true);
          } else {
            $msg .= translate("A task has been updated by", true);
          }
          $msg .= " " . $login_fullname .  ".\n" .
            translate("The subject is", true) . " \"" . $name . "\"\n\n" .
            translate("The description is", true) . " \"" . $description . "\"\n" .
            translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
            ( empty ( $timetype ) || $timetype != 'T' ? "" : translate("Time") . ": " .
            // Apply user's GMT offset and display their TZID
            display_time ( date ( "YmdHis", $eventstart ), 2, '', 
              $user_TIMEZONE, $t_format ) . "\n" ) .
            translate("Please look on", true) . " " . translate($APPLICATION_NAME) . " " .
            ( $REQUIRE_APPROVALS == "Y" ?
            translate("to accept or reject this task", true) :
            translate("to view this task", true) ) . ".";
          $msg = stripslashes ( $msg );
          // add URL to event, if we can figure it out
          if ( ! empty ( $SERVER_URL ) ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $SERVER_URL .  "view_task.php?id=" .  $id . "&em=1";
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
          $mail->AddAddress( $tempemail, unhtmlentities ( $tempfullname ) );
          $mail->WCSubject ( $name );
          $mail->Body  = $htmlmail == 'Y' ? nl2br ( $msg ) : $msg;;                    
          $mail->Send();
          $mail->ClearAll();
          
          activity_log ( $id, $login, $participants[$i], LOG_NOTIFICATION, "" );
      }
    }
  }
}

  //add categories
  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
   $is_admin ) ) ? $user : $login;
 dbi_query ( "DELETE FROM webcal_entry_categories WHERE cal_id = $id " .
    "AND ( cat_owner = '$cat_owner' OR cat_owner IS NULL )" );
 $categories = explode (",", $cat_id );
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

// If we were editing this task, then go back to the last view (week, day,
// month) on the due date.  If this is a new event, then go to the preferred 
//view for the date range that this event was added to.
if ( empty ( $error ) ) {
  $xdate = sprintf ( "%04d%02d%02d", $due_year, $due_month, $due_day );
  $user_args = ( empty ( $user ) ? '' : "user=$user" );
  send_to_preferred_view ( $xdate, $user_args );
}

print_header();
if ( ! empty ( $error ) ) { ?>

<h2><?php etranslate("Error")?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>
<?php } ?>

<?php print_trailer(); ?>
</body>
</html>
