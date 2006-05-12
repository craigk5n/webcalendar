<?php
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class' );
$mail = new WebCalMailer;

load_user_categories ();

$error = "";
$dberror = translate( 'Database error' ) . ': ';
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
$old_status = array();
//Pass all string values through getPostValue
$name = getPostValue ( 'name' );
$cat_id = getPostValue ( 'cat_id' );
$timetype = getPostValue ( 'timetype' );
$description = getPostValue ( 'description' );
$description = ( strlen ( $description ) == 0  || 
  $description == '<br />' ? $name : $description );

// Ensure  variables are not empty
if ( empty ( $percent ) ) $percent = 0;

if ( empty ( $timetype ) ) $timetype = 'T';

$duration_h = getValue ( 'duration_h' );
$duration_m = getValue ( 'duration_m' );
if ( empty ( $duration_h ) || $duration_h < 0 ) $duration_h = 0;
if ( empty ( $duration_m ) || $duration_m < 0 ) $duration_m = 0;

//Reminder values could be valid as 0
if ( empty ( $rem_days ) ) $rem_days = 0;
if ( empty ( $reminder_hour ) ) $reminder_hour = 0;
if ( empty ( $reminder_minute ) ) $reminder_minute = 0;
if ( empty ( $rem_rep_days ) ) $rem_rep_days = 0;
if ( empty ( $rem_rep_hours ) ) $rem_rep_hours = 0;
if ( empty ( $rem_rep_minute ) ) $rem_rep_minute = 0;

// Timed event.
if ( $timetype == 'T' )  {
  $entry_hour += $entry_ampm;
  
  if ( $eType == 'task'  ) {
    $start_hour += $start_ampm;
    $due_hour += $due_ampm; 
  }

  // Use end times
  if ( $TIMED_EVT_LEN == 'E') {
    $end_hour +=  $end_ampm;
  } else {
    $end_hour = 0;
    $end_minute = 0;
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

// All Day Event
if ( $timetype == 'A' ) {
  $duration_h = 24;
  $duration_m = 0;
  $entry_hour = 0;
  $entry_minute = 0;
  $end_hour = 0;
  $end_minute = 0;  
}

// Untimed Event
if ( $timetype == 'U' ) {
  $duration_h = 0;
  $duration_m = 0;
  $entry_hour = 0;
  $entry_minute = 0;
  $end_hour = 0;
  $end_minute = 0;
}


// Combine all values to create event start date/time
if ( $timetype != 'T' ) { 
  $eventstart = gmmktime ( $entry_hour, $entry_minute, 0, $month, $day, $year );
} else {
  $eventstart = mktime ( $entry_hour, $entry_minute, 0, $month, $day, $year );
}

if ( $eType == 'task' ) {
  // Combine all values to create event due date/time - User Time
  if ( $timetype != 'T' ) { 
    $eventdue = gmmktime ( $due_hour, $due_minute, 0, $due_month, $due_day, $due_year );
  } else {
    $eventdue = mktime ( $due_hour, $due_minute, 0, $due_month, $due_day, $due_year );
  }


// Combine all values to create completed date 
if ( ! empty ( $completed_year )  && ! empty ( $completed_month ) &&
    ! empty ( $completed_day ) ) 
    $eventcomplete =  sprintf( "%04d%02d%02d" , $completed_year, 
      $completed_month, $completed_day );
} else {
  $eventdue = $eventstart; //just keeps things simple later on 
}

//Create event stop from event  duration/end values
// Note: for any given event, either end times or durations are 0
if ( $TIMED_EVT_LEN == 'E') {
 $eventstophour= $end_hour + $duration_h;
 $eventstopmin= $end_minute + $duration_m;
} else {
 $eventstophour= $entry_hour + $duration_h;
 $eventstopmin= $entry_minute + $duration_m;
}

if ( $timetype != 'T' ) { 
  $eventstop = gmmktime ( $eventstophour, $eventstopmin, 0, $month, $day, $year );
} else {
  $eventstop = mktime ( $eventstophour, $eventstopmin, 0, $month, $day, $year );
}


// Calculate event duration
if ( $timetype == 'T' ) {
  $duration = ( $eventstop - $eventstart ) / 60;
  if (  $duration < 0 ) $duration = 0;
} else if ( $timetype == 'A' ) {
 $duration = 1440;
} else if ( $timetype == 'U' ) {
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
$can_edit = $can_doall = false;
// value may be needed later for recreating event
$old_create_by = ( ! empty ( $user )? $user : '');
if ( empty ( $id ) ) {
  // New event...
  $can_edit = true;
} else {
  // event owner or assistant event ?
  $sql = "SELECT cal_create_by FROM webcal_entry WHERE cal_id = ?";
  $res = dbi_execute( $sql, array( $id ) );
  if ($res) {
    $row = dbi_fetch_row ( $res );
    // value may be needed later for recreating event
    $old_create_by = $row[0];
    if (( $row[0] == $login ) || (( $user == $row[0] ) && 
      ( $is_assistant || $is_nonuser_admin )))
      $can_edit = true;
    dbi_free_result ( $res );
  } else
    $error = $dberror . dbi_error ();
}

if ( $is_admin ) {
  $can_edit = true;
}
if ( empty ( $error ) && ! $can_edit ) {
  // is user a participant of that event ?
  $sql = "SELECT cal_id FROM webcal_entry_user WHERE cal_id = ? " .
    "AND cal_login = ? AND cal_status IN ('W','A')";
  $res = dbi_execute ( $sql, array( $id, $login ) );
  if ($res) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty( $row[0] ) )
      $can_edit = true; // is participant
    dbi_free_result ( $res );
  } else
    $error = $dberror . dbi_error ();
}
//check UAC
if ( access_is_enabled () && ! empty ( $old_create_by ) ) {
  $can_edit = access_user_calendar ( 'edit', $old_create_by, $login);
} 
if ( ! $can_edit && empty ( $error ) ) {
  $error = translate ( 'You are not authorized' );
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
    $PUBLIC_ACCESS_DEFAULT_SELECTED == 'Y' ) {
    $participants[1] = '__public__';     
  }
}

if ( empty ( $DISABLE_REPEATING_FIELD ) ||
  $DISABLE_REPEATING_FIELD == 'N' ) {
  //Convert $byxx arrays from form
  rsort ($bydayext2);
  
  for ( $i=0; $i<35;$i++) {
   if ( strlen ($bydayext2[$i]) < 2 || 
     $bydayext2[$i] == '        ') unset  ($bydayext2[$i]);
  }
  if ( ! empty ( $bydayext1 ) ) {
    $bydayext = array_merge($bydayext1,$bydayext2);
    $byday = implode (',', $bydayext );
  } else {
    $byday = implode (',', $bydayext2 );
  }
  rsort ($bymonthday);
  for ( $i=0; $i<31;$i++) {
   if ( strlen ($bymonthday[$i] < 1 || 
     $bymonthday[$i] == '      ' ) ) unset  ($bymonthday[$i]);
  }
  $bymonthday = implode (',', $bymonthday );
  
  rsort ($bysetpos2);
  for ( $i=0; $i<31;$i++) {
   if ( strlen ($bysetpos2[$i]) < 1 || 
     $bysetpos2[$i] == '      ') unset  ($bysetpos2[$i]);
  }
  if ( ! empty ( $bysetpos2) ) $bysetpos = implode (',', $bysetpos2 );
  
  $bymonth = ( ! empty ( $bymonth) ? implode (',', $bymonth ) : '' );
  
  //This allows users to select on weekdays if daily
  if ( $rpt_type == 'daily' && ! empty ( $weekdays_only ) ) {
   $dayst = 'MO,TU,WE,TH,FR';
  }
  
  if ( ! empty ( $rpt_year ) ) {
    $rpt_hour +=  $rpt_ampm;  
    $rpt_until = mktime ( $rpt_hour, $rpt_minute, 0, $rpt_month, $rpt_day,$rpt_year );
  }

  $inclusion_list = array();
  $exception_list = array();
  if ( empty ( $exceptions ) ) { 
   $exceptions = array();
  } else {
   foreach ( $exceptions as $exception ) {
     if ( substr ( $exception, 0, 1 ) == '+' ) {
       $inclusion_list[] = substr ( $exception, 1, 8);
     } else {
       $exception_list[] = substr ( $exception, 1, 8);     
     }
   }
  }
} // end test for $DISABLE_REPEATING_FIELD

//make sure we initialize this variables
if ( empty ( $bymonth ) ) $bymonth = '';
if ( empty ( $byweekno ) ) $byweekno = '';
if ( empty ( $byyearday ) ) $byyearday = '';
if ( empty ( $bymonthday ) ) $bymonthday = '';
if ( empty ( $byday ) ) $byday = '';
if ( empty ( $bysetpos ) ) $bysetpos = ''; 
if ( empty ( $count ) ) $count = '';
if ( empty ( $rpt_type ) ) $rpt_type = '';
if ( empty ( $rpt_freq ) ) $rpt_freq = 1;
if ( empty ( $wkst ) ) $wkst = 'MO';
  
// first check for any schedule conflicts
if ( empty ( $ALLOW_CONFLICT_OVERRIDE ) || $ALLOW_CONFLICT_OVERRIDE != 'Y' ) {
  $confirm_conflicts = ''; // security precaution
}

if ( $ALLOW_CONFLICTS != 'Y' && empty ( $confirm_conflicts ) &&
  strlen ( $entry_hour ) > 0 && $timetype != 'U' ) {
  
  $dates = get_all_dates ( $eventstart, $rpt_type, $rpt_freq, $bymonth,
   $byweekno, $byyearday, $bymonthday, $byday, $bysetpos, $count,
   $rpt_until, $wkst, $exception_list, $inclusion_list );
  
  //make sure at least start date is in array
  if ( empty ( $dates ) ) $dates[0] = $eventstart;

  $conflicts = check_for_conflicts ( $dates, $duration, $eventstart,
    $participants, $login, empty ( $id ) ? 0 : $id );
} //end  check for any schedule conflicts

if ( empty ( $error ) && ! empty ( $conflicts ) ) {
  $error = translate( 'The following conflicts with the suggested time' ) .
    ': <ul>$conflicts</ul>';
}

$msg = '';
if ( empty ( $error ) ) {
  $newevent = true;
  // now add the entries
  if ( empty ( $id ) || $do_override ) {
    $res = dbi_execute ( "SELECT MAX(cal_id) FROM webcal_entry" );
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
      "FROM webcal_entry_user WHERE cal_id = ? ";
    $res = dbi_execute ( $sql, array( $id ) );
    if ( $res ) {
      for ( $i = 0; $tmprow = dbi_fetch_row ( $res ); $i++ ) {
        $old_status[$tmprow[0]] = $tmprow[1]; 
        $old_percent[$tmprow[0]] = $tmprow[2];
      }
      dbi_free_result ( $res );
    } else {
      $error = $dberror . dbi_error ();
    }

    if ( empty ( $error ) ) {
      dbi_execute ( "DELETE FROM webcal_entry WHERE cal_id = ?", array( $id ) );
      dbi_execute ( "DELETE FROM webcal_entry_user WHERE cal_id = ?", array( $id ) );
      dbi_execute ( "DELETE FROM webcal_entry_ext_user WHERE cal_id = ?", array( $id ) );
      dbi_execute ( "DELETE FROM webcal_entry_repeats WHERE cal_id = ?", array( $id ) );
      dbi_execute ( "DELETE FROM webcal_site_extras WHERE cal_id = ?", array( $id ) );
    }
    $newevent = false;
  }

  if ( $do_override ) {
    $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) " .
      "VALUES ( ?, ?, ? )";
    if ( ! dbi_execute ( $sql, array( $old_id, $override_date, 1 ) ) ) {
      $error = $dberror . dbi_error ();
    }
  }

  $sql = "INSERT INTO webcal_entry ( cal_id, " .
    ( $old_id > 0 ? " cal_group_id, " : "" ) .
    "cal_create_by, cal_date, cal_time, " .
    ( ! empty ( $eventcomplete)? "cal_completed, ": "" ) .
    "cal_due_date, cal_due_time, cal_mod_date, cal_mod_time, cal_duration, cal_priority, " .
    "cal_access, cal_type, cal_name, cal_description, cal_location ) " .
    "VALUES ( ?, " .
    ( $old_id > 0 ? "?, " : "" ) .
    "?, ?, ?, " . 
  ( ! empty ( $eventcomplete ) ? "?, ": "" ) .
  "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";

  $query_params = array();
  $query_params[] = $id;
  if ( $old_id > 0 )
    $query_params[] = $old_id;

  $query_params[] = ( ! empty ( $old_create_by ) ? $old_create_by : $login );
  $query_params[] = gmdate ( 'Ymd', $eventstart );
  $query_params[] = ( strlen ( $entry_hour ) > 0 && $timetype != 'U' ) ? 
    gmdate ('His', $eventstart ) : "-1";
  
  if ( ! empty ( $eventcomplete ) )
    $query_params[] = $eventcomplete;

  $query_params[] = gmdate ( 'Ymd', $eventdue );
  $query_params[] = gmdate ('His', $eventdue );
  $query_params[] = gmdate ( 'Ymd' );
  $query_params[] = gmdate ( 'Gis' );
  $query_params[] = sprintf ( "%d", $duration );
  $query_params[] = ( ! empty ( $priority ) ) ? sprintf ( "%d", $priority ) : '2';
  
  $query_params[] = empty ( $access ) ? 'P' : "$access";
  if ( ! empty ( $rpt_type ) && $rpt_type != 'none' && $eType == 'event' ) {
  $query_params[] = 'M';
  } else if ( $eType == 'event' ) {
  $query_params[] = 'E';
  }  else if ( ! empty ( $rpt_type ) && $rpt_type != 'none' && $eType == 'task' ) {
  $query_params[] = 'N';
  } else if ( $eType == 'task' ) {
  $query_params[] = 'T';
  }  else if ( ! empty ( $rpt_type ) && $rpt_type != 'none' && $eType == 'journal' ) {
  $query_params[] = 'O';
  } else if ( $eType == 'journal' ) {
  $query_params[] = 'J';
  }
  $query_params[] = ( strlen ( $name ) == 0 ) ? 'Unnamed Event' : $name;
  $query_params[] = $description;
  $query_params[] = ( ! empty ( $location ) ) ? $location : '' ;

  if ( empty ( $error ) ) {
    if ( ! dbi_execute ( $sql, $query_params ) ) {
      $error = $dberror . dbi_error ();
    }
  }

  // log add/update
  if ( $eType == 'task' ) {
   $log_c = LOG_CREATE_T;
   $log_u = LOG_UPDATE_T;
  } else if ( $eType == 'journal' ) {
   $log_c = LOG_CREATE_J;
   $log_u = LOG_UPDATE_J;
  }else {
   $log_c = LOG_CREATE;
   $log_u = LOG_UPDATE;
  }
  activity_log ( $id, $login, ($is_assistant || $is_nonuser_admin ? $user : $login),
    $newevent ? $log_c : $log_u, '' );
  
  if ( $single_user == 'Y' ) {
    $participants[0] = $single_user_login;
  }

  //add categories
  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
    $is_admin ) ) ? $user : $login;
  dbi_execute ( "DELETE FROM webcal_entry_categories WHERE cal_id = ? " .
    "AND ( cat_owner = ? OR cat_owner IS NULL )", array( $id, $cat_owner ) );
  if ( ! empty ( $cat_id ) ) {
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
        $values[]  = $cat_owner;
        $names[] = 'cat_order';
        $values[]  = ($i+1);
      } else {
        $names[] = 'cat_order';
        $values[]  = 99; //forces global categories to appear at the end of lists 
      }
      // build the variable placeholders - ?-s, comma-separated
      $placeholders = '';
      for ( $v_i = 0; $v_i < count( $values ); $v_i++ ) {
        $placeholders .= '?,';
      }
      $placeholders = preg_replace( "/,$/", "", $placeholders ); // remove trailing ','
      $sql = "INSERT INTO webcal_entry_categories ( " . implode ( ", ", $names ) .
        " ) VALUES ( $placeholders )"; 
      if ( ! dbi_execute ( $sql, $values ) ) {
        $error = $dberror . dbi_error ();
        break;
      }
    }
  }     
  // add site extras
  for ( $i = 0; $i < count ( $site_extras ) && empty ( $error ); $i++ ) {
    $sql = '';
    $extra_name = $site_extras[$i][0];
    $extra_type = $site_extras[$i][2];
    $extra_arg1 = $site_extras[$i][3];
    $extra_arg2 = $site_extras[$i][4];
    $value = $$extra_name;
    //echo "Looking for $extra_name... value = " . $value . " ... type = " .
    // $extra_type . "<br />\n";
    
  $sql = '';
  $query_params = array();

  if ( strlen ( $extra_name ) || $extra_type == EXTRA_DATE ) {
      if ( $extra_type == EXTRA_URL || $extra_type == EXTRA_EMAIL ||
        $extra_type == EXTRA_TEXT || $extra_type == EXTRA_USER ||
        $extra_type == EXTRA_MULTILINETEXT ||
        $extra_type == EXTRA_SELECTLIST  ) {

        $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_data ) VALUES ( ?, ?, ?, ? )";
    $query_params = array( $id, $extra_name, $extra_type, $value );
      } else if ( $extra_type == EXTRA_DATE )  {
        $yname = $extra_name . 'year';
        $mname = $extra_name . 'month';
        $dname = $extra_name . 'day';
        $edate = sprintf ( "%04d%02d%02d", $$yname, $$mname, $$dname );
        $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_date ) VALUES ( ?, ?, ?, ? )";
    $query_params = array( $id, $extra_name, $extra_type, $edate );
      }
    }
    if ( strlen ( $sql ) && empty ( $error ) ) {
      //echo "SQL: $sql<br />\n";
      if ( ! dbi_execute ( $sql, $query_params ) ) {
        $error = $dberror . dbi_error ();
      }
    }
  } //end for site_extras loop

  //process reminder
  if ( ! dbi_execute ( "DELETE FROM webcal_reminders WHERE cal_id = ?", array( $id ) ) )
    $error = $dberror . dbi_error ();
  if ( $DISABLE_REMINDER_FIELD != 'Y' && $reminder == true ) {
    if ( empty ( $rem_related ) ) $rem_related = 'S';
    if ( empty ( $rem_before ) ) $rem_before = 'Y';
    $reminder_date = $reminder_offset = $reminder_duration = $reminder_repeats = 0;
    if ( $rem_when == 'Y' ) { //use date
      $reminder_hour +=  $reminder_ampm;
      $reminder_date = mktime ( $reminder_hour, $reminder_minute, 0, $reminder_month,
        $reminder_day, $reminder_year ); 
    } else { //use offset
      $reminder_offset = ($rem_days * 60 * 24 ) + ( $reminder_hour * 60 ) + 
        $reminder_minute;
    }
    if ( $rem_rep_count > 0 ) {
      $reminder_repeats = $rem_rep_count;
      $reminder_duration = ($rem_rep_days * 60 * 24 ) + 
        ( $rem_rep_hours * 60 ) + $rem_rep_minute;      
    }
    $sql = "INSERT INTO webcal_reminders ( cal_id, cal_date, cal_offset, cal_related, " .
      "cal_before, cal_repeats, cal_duration, cal_action, cal_last_sent, cal_times_sent ) " .
      " VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
      if ( ! dbi_execute ( $sql, array( $id, $reminder_date, $reminder_offset, 
        $rem_related, $rem_before,$reminder_repeats, $reminder_duration, $rem_action, 
        $rem_last_sent, $rem_times_sent ) ) )
        $error = $dberror . dbi_error ();    
  }
  // clearly, we want to delete the old repeats, before inserting new...
  if ( empty ( $error ) ) {
    if ( ! dbi_execute ( "DELETE FROM webcal_entry_repeats WHERE cal_id = ?", array( $id ) ) ) {
      $error = $dberror . dbi_error ();
    }
  if ( ! dbi_execute ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?", array( $id ) ) ) {
      $error = $dberror . dbi_error ();
  }
    // add repeating info
  if ( ! empty ( $rpt_type ) && strlen ( $rpt_type ) && $rpt_type != 'none' ) {
    $freq = ( $rpt_freq ? $rpt_freq : 1 );      

    $names = array();
    $values = array();
    $names[] = 'cal_id';
    $values[]  = $id;
   
    $names[]  = 'cal_type';
    $values[] = $rpt_type;
        
    $names[] = 'cal_frequency';
    $values[] = $freq;
 
    if (! empty ( $bymonth ) ){
      $names[] = 'cal_bymonth';
      $values[]  = $bymonth;
    } 
    
    if (! empty ( $bymonthday ) ){
      $names[] = 'cal_bymonthday';
      $values[]  = $bymonthday;
    } 
    if ( ! empty ( $byday ) ){
      $names[] = 'cal_byday';
      $values[] =  $byday;
    }
    if (! empty ( $bysetpos ) ){
      $names[] = 'cal_bysetpos';
      $values[] = $bysetpos;
    }
    if (! empty ( $byweekno ) ){
      $names[] = 'cal_byweekno';
      $values[] = $byweekno;
    }
    if (! empty ( $byyearday ) ) {
      $names[] = 'cal_byyearday';
      $values[] = $byyearday;
    }
    if (! empty ( $wkst ) ) {
      $names[] = 'cal_wkst';
      $values[] = $wkst;
    }
    
    if (! empty ( $rpt_count ) && is_numeric ( $rpt_count )  ) {
      $names[] = 'cal_count';
      $values[] = $rpt_count;
    } 

    if ( ! empty($rpt_until) ) {
      $names[] = 'cal_end';
      $values[] = gmdate ( 'Ymd', $rpt_until );
      $names[] = 'cal_endtime';         
      $values[] = gmdate ('His', $rpt_until );
    }

    $placeholders = '';
  for ( $v_i = 0; $v_i < count( $values ); $v_i++ ) {
      $placeholders .= '?,';
  }
  $placeholders = preg_replace( "/,$/", "", $placeholders ); // remove trailing ','
    $sql = "INSERT INTO webcal_entry_repeats ( " . implode ( ", ", $names ) .
       " ) VALUES ( $placeholders )"; 
      dbi_execute ( $sql, $values );
      $msg .= "<span style=\"font-weight:bold;\">SQL:</span> $sql<br />\n<br />";

    } //end add repeating info
    //We manually created exceptions. This can be done without repeats
     if ( ! empty ($exceptions ) ) {
       for ( $i = 0; $i < count ( $exceptions ); $i++ ) {
         $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) " .
           "VALUES ( ?, ?, ? )";
         if ( ! dbi_execute ( $sql, array( $id, substr ($exceptions[$i],1,8 ), 
           ( ( substr ($exceptions[$i],0, 1 ) == "+" ) ? 0 : 1 ) ) ) ) {
           $error = $dberror . dbi_error ();
         }
       }
      } //end exceptions    
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
      //check UAC
      $can_email = 'Y'; 
      if ( access_is_enabled () ) {
        $can_email = access_user_calendar ( 'email', $old_participant, $login);
      }
      $is_nonuser_admin = user_is_nonuser_admin ( $login, $old_participant );
      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if ( !$found_flag && !$is_nonuser_admin && $can_email == 'Y') {
        // only send mail if their email address is filled in
        $do_send = get_pref_setting ( $old_participant, 'EMAIL_EVENT_DELETED' );
        $htmlmail = get_pref_setting ( $old_participant, 'EMAIL_HTML' );
        $t_format = get_pref_setting ( $old_participant, 'TIME_FORMAT' );
        $user_TIMEZONE = get_pref_setting ( $old_participant, 'TIMEZONE' );
        set_env ( 'TZ', $user_TIMEZONE );
        $user_language = get_pref_setting ( $old_participant, 'LANGUAGE' );
        user_load_variables ( $old_participant, 'temp' );
        
        
        if ( $old_participant != $login && ! empty ( $tempemail ) &&
          $do_send == 'Y' && $SEND_EMAIL != 'N' ) {     
       
          if ( empty ( $user_language ) || ( $user_language == 'none' )) {
             reset_language ( $LANGUAGE );
          } else {
             reset_language ( $user_language );
          }
  
          $fmtdate = ( $timetype == 'T' ? 
            date ( 'Ymd', $eventstart ): gmdate ( 'Ymd', $eventstart ) ); 
          $msg = translate( 'Hello', true) . ', ' .
            unhtmlentities( $tempfullname ) . ".\n\n" .
            translate( 'An appointment has been canceled for you by', true) .
            " " . $login_fullname .  ".\n" .
            translate( 'The subject was', true) . ' "' . $name . "\"\n\n" .
            translate( 'The description is', true) . ' "' . $description . "\"\n" .
            translate( 'Date') . ': ' . date_to_str ( $fmtdate ) . "\n" .
             ( $timetype != 'T'  ? "" :
            translate( 'Time' ) . ': ' .
            // Apply user's GMT offset and display their TZID
            display_time ( '', 2, $eventstart, $t_format ) . "\n\n\n");
          $msg = stripslashes ( $msg );
          // add URL to event, if we can figure it out
          if ( ! empty ( $SERVER_URL ) ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $SERVER_URL .  'view_entry.php?id=' .  $id . '&em=1';
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
          $mail->Body  = ( $htmlmail == 'Y' ? nl2br ( $msg ) : $msg );
          $mail->Send();
          $mail->ClearAll();          
          activity_log ( $id, $login, $old_participant, LOG_NOTIFICATION,
            'User removed from participants list' );
        }
      }
    }
  }

  // now add participants and send out notifications
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    // Is the person adding the nonuser calendar admin
    $is_nonuser_admin = user_is_nonuser_admin ( $login, $participants[$i] );

    // if public access, require approval unless
    // $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL is set to 'N'
    if ( $login == '__public__' ) {
      if ( ! empty ( $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL ) &&
        $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'N' ) {
        $status = 'A'; // no approval needed
      } else {
        // Approval required
        $status = 'W'; // approval required
      }
    } else if ( ! $newevent ) {
      // keep the old status if no email will be sent
      $send_user_mail = ( empty ( $old_status[$participants[$i]] ) ||
        $entry_changed ) ?  true : false;
      $tmp_status = ( ! empty ( $old_status[$participants[$i]] ) && 
        ! $send_user_mail ? $old_status[$participants[$i]] : 'W' );
      $status = ( $participants[$i] != $login && 
        boss_must_approve_event ( $login, $participants[$i] ) && 
        $REQUIRE_APPROVALS == 'Y' && ! $is_nonuser_admin ) ?
        $tmp_status : 'A';
      
      //set percentage to old_percent if not owner
      $tmp_percent = ( ! empty ( $old_percent[$participants[$i]] ) ? 
        $old_percent[$participants[$i]] : 0 );
      //TODO this logic needs work 
      $new_percent = ( $participants[$i] != $login ) ?
        $tmp_percent : $percent;

      // If user is admin and this event was previously approved for public,
      // keep it as approved even though date/time may have changed
      // This goes against stricter security, but it confuses users to have
      // to re-approve events they already approved.
      if ( $participants[$i] == '__public__' && $is_admin &&
        ( empty ( $old_status['__public__'] ) || $old_status['__public__'] == 'A' ) ) {
        $status = 'A';
      }
    } else {  // New Event
      $send_user_mail = true;
      $status = ( $participants[$i] != $login && 
        boss_must_approve_event ( $login, $participants[$i] ) && 
        $REQUIRE_APPROVALS == 'Y' && ! $is_nonuser_admin ) ?
        'W' : 'A';
      $new_percent = ( $participants[$i] != $login ) ? 0 : $percent;
      // If admin, no need to approve Public Access Events
      if ( $participants[$i] == '__public__' && $is_admin ) {
        $status = 'A';
      }
    } //end new/old event
  
    // Some users report that they get an error on duplicate keys
    // on the following add... As a safety measure, delete any
    // existing entry with the id.  Ignore the result.
    dbi_execute ( "DELETE FROM webcal_entry_user WHERE cal_id = ? " .
      "AND cal_login = ?", array( $id, $participants[$i] ) );
    $sql = "INSERT INTO webcal_entry_user " .
      "( cal_id, cal_login, cal_status, cal_percent ) VALUES ( ?, ?, ?, ? )";
    if ( ! dbi_execute ( $sql, array( $id, $participants[$i], $status, $new_percent ) ) ) {
      $error = $dberror . dbi_error ();
      break;

    } else {
      //check UAC
      $can_email = 'Y'; 
      if ( access_is_enabled () ) {
        $can_email = access_user_calendar ( 'email', $participants[$i], $login);
      }
      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if (!$is_nonuser_admin && $can_email == 'Y') {
        // only send mail if their email address is filled in
        $do_send = get_pref_setting ( $participants[$i],
        $newevent ? 'EMAIL_EVENT_ADDED' : 'EMAIL_EVENT_UPDATED' );
        $htmlmail = get_pref_setting ( $participants[$i], 'EMAIL_HTML' );
        $t_format = get_pref_setting ( $participants[$i], 'TIME_FORMAT' );
        $user_TIMEZONE = get_pref_setting ( $participants[$i], 'TIMEZONE' );
        set_env ( 'TZ', $user_TIMEZONE );
        $user_language = get_pref_setting ( $participants[$i], 'LANGUAGE' );
        user_load_variables ( $participants[$i], 'temp' );
        if ( $participants[$i] != $login && 
          boss_must_be_notified ( $login, $participants[$i] ) && 
          ! empty ( $tempemail ) &&
          $do_send == 'Y' && $send_user_mail && $SEND_EMAIL != 'N' ) {


          if ( empty ( $user_language ) || ( $user_language == 'none' )) {
             reset_language ( $LANGUAGE );
          } else {
             reset_language ( $user_language );
          }

          $fmtdate = ( $timetype == 'T' ? 
            date ( 'Ymd', $eventstart ): gmdate ( 'Ymd', $eventstart ) ); 
          $msg = translate( 'Hello', true) . ', ' .
            unhtmlentities ( $tempfullname ) . ".\n\n";
          if ( $newevent || ( empty ( $old_status[$participants[$i]] ) ) ) {
            $msg .= translate( 'A new appointment has been made for you by', true);
          } else {
            $msg .= translate( 'An appointment has been updated by', true);
          }
          $msg .= ' ' . $login_fullname .  ".\n" .
            translate( 'The subject is', true) . ' "' . $name . "\"\n\n" .
            translate( 'The description is', true) . ' "' . $description . "\"\n" .
            translate( 'Date' ) . ': ' . date_to_str ( $fmtdate ) . "\n" .
            ( $timetype != 'T' ? '' :
            translate( 'Time' ) . ': ' .
            // Apply user's GMT offset and display their TZID
            display_time ( '', 2, $eventstart, $t_format ) . "\n" ) .
            translate( 'Please look on', true) . ' ' . translate($APPLICATION_NAME) . 
            ' ' . ( $REQUIRE_APPROVALS == 'Y' ?
            translate( 'to accept or reject this appointment', true) :
            translate( 'to view this appointment', true) ) . '.';
          $msg = stripslashes ( $msg );
          // add URL to event, if we can figure it out
          if ( ! empty ( $SERVER_URL ) ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $SERVER_URL .  'view_entry.php?id=' .  $id . '&em=1';
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
if ( $single_user == 'N' &&
  ! empty ( $ALLOW_EXTERNAL_USERS ) && 
  $ALLOW_EXTERNAL_USERS == 'Y' &&
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
          "( cal_id, cal_fullname, cal_email ) VALUES ( ?, ?, ? )";
        if ( ! dbi_execute ( $sql, array( $id, $ext_names[$i], 
          ( strlen ( $ext_emails[$i] ) ? $ext_emails[$i] : NULL ) ) ) ) {
          $error = $dberror . dbi_error ();
        }
        // send mail notification if enabled
        // TODO: move this code into a function...
        if ( $EXTERNAL_NOTIFICATIONS == 'Y' && $SEND_EMAIL != 'N' &&
          strlen ( $ext_emails[$i] ) > 0 ) {          
          if ( ( ! $newevent &&  $EXTERNAL_UPDATES == 'Y' ) || $newevent ) {
            $fmtdate = ( $timetype == 'T' ? 
              date ( 'Ymd', $eventstart ): gmdate ( 'Ymd', $eventstart ) ); 
            // Strip [\d] from duplicate Names before emailing
            $ext_names[$i] = trim(preg_replace( '/\[[\d]]/', "", $ext_names[$i]) );
            $msg = translate( 'Hello', true) . ", " . $ext_names[$i] . ".\n\n";
            if ( $newevent ) {
              $msg .= translate( 'A new appointment has been made for you by', true);
            } else {
              $msg .= translate( 'An appointment has been updated by', true);
            }
            $msg .= " " . $login_fullname .  ".\n" .
              translate( 'The subject is', true) . " \"" . $name . "\"\n\n" .
              translate( 'The description is', true) . ' "' . $description . "\"\n\n" .
              translate( 'Date') . ': ' . date_to_str ( $fmtdate ) . "\n";
              if ( $timetype == 'T')  {
                $msg .= translate( 'Time' ) . ': ';
                if ( ! empty ( $GENERAL_USE_GMT ) && $GENERAL_USE_GMT == 'Y' ) {
                  // Do not apply TZ offset & display TZID, which is GMT
                  $msg .= display_time ( '', 3, $eventstart );
                } else {
                  // Display time in server's timezone
                  $msg .= display_time ( '', 6, $eventstart);              
                }
              }
            $msg = stripslashes ( $msg );          
            //don't send HTML to external adresses
            $htmlmail = false;
            if ( strlen ( $from ) ) {
              $mail->From = $from;
              $mail->FromName = $login_fullname;
            } else {
              $mail->From = $login_fullname;
            }  
            $mail->IsHTML($htmlmail == 'Y');
            $mail->AddAddress( $ext_emails[$i], $ext_names[$i] );
            $mail->WCSubject ( $name );                     
            $mail->IcsAttach ( $id ) ;
            $mail->Body  = ( $htmlmail == 'Y' ? nl2br ( $msg ) : $msg );
            $mail->Send();
            $mail->ClearAll();          
          }
        } 
      }
    }
  } //end external mail

} //end empty error

// If we were editing this event, then go back to the last view (week, day,
// month).  If this is a new event, then go to the preferred view for
// the date range that this event was added to.
if ( empty ( $error ) ) {
  $return_view = get_last_view ();
  if ( ! empty ( $return_view ) ) {
    do_redirect ( $return_view );
  } else {
  $xdate = sprintf ( "%04d%02d%02d", $year, $month, $day );
  $user_args = ( empty ( $user ) ? '' : "user=$user" );
  send_to_preferred_view ( $xdate, $user_args );
  }
}

print_header();
if ( ! empty ( $conflicts ) ) { 
?>
<h2><?php etranslate( 'Scheduling Conflict' )?></h2>

<?php etranslate( 'Your suggested time of' )?> <span style="font-weight:bold;">
<?php
  if (  $timetype == 'A' ) {
    etranslate( 'All day event' );
  } else {
    $time = sprintf ( "%d%02d00", $entry_hour, $entry_minute );
    // Pass the adjusted timestamp in case the date changed due to GMT offset 
    echo display_time ( $time, 1, $eventstart );
    if ( $duration > 0 ) {
      echo "-" . display_time ( add_duration ( $time, $duration ), 1, $eventstart );
    }
  }
?></span> <?php etranslate( 'conflicts with the following existing calendar entries' )?>:
<ul>
<?php echo $conflicts; ?>
</ul>

<?php
// user can confirm conflicts
  echo '<form name="confirm" method="post">' . "\n";
  if ( ! is_array ( $_POST ) && is_array ( $HTTP_POST_VARS ) )
    $_POST = $HTTP_POST_VARS;
  foreach ($_POST as $xkey=>$xval ) {
    if (is_array($xval)) {
      $xkey.="[]";
      foreach ( $xval as $ykey=>$yval ) {
        if (get_magic_quotes_gpc())
          $yval = stripslashes($yval);
        //$yval = htmlentities  ( $yval );
        echo "<input type=\"hidden\" name=\"$xkey\" value=\"$yval\" />\n";
      }
    } else {
      if (get_magic_quotes_gpc())
        $xval = stripslashes($xval);
      //$xval = htmlentities ( $xval );
      echo "<input type=\"hidden\" name=\"$xkey\" value=\"$xval\" />\n";
    }
  }
?>
<table>
 <tr>
<?php
  // Allow them to override a conflict if server settings allow it
  if ( ! empty ( $ALLOW_CONFLICT_OVERRIDE ) &&
    $ALLOW_CONFLICT_OVERRIDE == 'Y' ) {
    echo '<td><input type="submit" name="confirm_conflicts" value="' . 
      translate( 'Save' ) . "\" /></td>\n";
  }
?>
   <td><input type="button" value="<?php etranslate( 'Cancel' )?>" 
onclick="history.back()" /><td>
 </tr>
</table>
</form>

<?php } else { ?>
<h2><?php etranslate( 'Error' )?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>
<?php }
print_trailer(); ?>
</body>
</html>
