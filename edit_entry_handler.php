<?php
/* $Id$ */
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class.php' );
$mail = new WebCalMailer;

$error = '';

$do_override = false;
$old_id = -1;

$server_url = getPref ( 'SERVER_URL', 2 );

//put byday values in logical sequence
function sort_byday ( $a, $b ) {
  global $WC;
  
  $len_a = strlen ( $a );
  $len_b = strlen ( $b );
  $val_a = $WC->byday_values[substr ($a, -2 )];
  $val_b = $WC->byday_values[substr ($b, -2 )];
  if ( $len_a != $len_b  ) { 
    return ( $len_a < $len_b ? -1 : 1 );
  } else if ( $len_a == 2 ) {
    return strcmp ( $val_a, $val_b );
  } else { //they start with numeric offsets
    $offset_a = substr ( $a, 0, $len_a - 2 ); 
    $offset_b = substr ( $b, 0, $len_b - 2 );
    if ( $offset_a == $offset_b ) {
      return strcmp ( $val_a, $val_b );
    } else { //add weight to weekday value to help sort
      return strcmp ( abs($offset_a) + $val_a * 10, abs($offset_b) + $val_b * 10 );
    }
   }
}

//Pass all string values through getPostValue
$eid = $WC->getPOST ( 'eid' );
$name = $WC->getPOST ( 'name' );
$priority = $WC->getPOST ( 'priority', 5 );
$access = $WC->getPOST ( 'access', 'P' );
$percent = $WC->getPOST ( 'percent', 0 );
$timetype = $WC->getPOST ( 'timetype', 'T' );
$description = $WC->getPOST ( 'description' );
$description = ( strlen ( $description ) == 0  || 
  $description == '<br />' ? $name : $description );
$location = $WC->getPOST ( 'location' );
$entry_url = $WC->getPOST ( 'entry_url' );
$eType = $WC->getPOST ( 'eType', 'event' );
$rpt_type = $WC->getPOST ( 'rpt_type', 'none' );
$rpt_freq = $WC->getPOST ( 'rpt_freq', 1 );
$rpt_count = $WC->getPOST ( 'rpt_count' );
$reminder = $WC->getPOST ( 'reminder' );

//Pass all numeric values through getPostValue
$entry_hour = $WC->getPOST ( 'entry_hour' );
$entry_ampm = $WC->getPOST ( 'entry_ampm' );
$entry_minute = $WC->getPOST ( 'entry_minute' );
$day = $WC->getPOST ( 'day' );
$month = $WC->getPOST ( 'month' );
$year = $WC->getPOST ( 'year' );

$timed_ent_len = getPref ( 'TIMED_EVT_LEN' );

if ( ! empty ( $override ) && ! empty ( $override_date ) ) {
  // override date specified.  user is going to create an exception
  // to a repeating event.
  $do_override = true;
  $old_id = $eid;
}
// Remember previous cal_goup_id if present
$old_id = ( ! empty ( $parent ) ? $parent : $old_id );
$old_status = array();



$duration_h = $WC->getPOST ( 'duration_h', 0 );
$duration_m = $WC->getPOST ( 'duration_m', 0 );
if ( $duration_h < 0 ) $duration_h = 0;
if ( $duration_m < 0 ) $duration_m = 0;

//Reminder values could be valid as 0
$rem_days = $WC->getPOST ( 'rem_days', 0 );
$rem_hours = $WC->getPOST ( 'rem_hours', 0 );
$rem_minutes = $WC->getPOST ( 'rem_minutes', 0 );
$reminder_hour = $WC->getPOST ( 'reminder_hour', 0 );
$reminder_minute = $WC->getPOST ( 'reminder_minute', 0 );
$rem_rep_days = $WC->getPOST ( 'rem_rep_days', 0 );
$rem_rep_hours = $WC->getPOST ( 'rem_rep_hours', 0 );
$rem_rep_minutes = $WC->getPOST ( 'rem_rep_minutes', 0 );

// Timed event.
if ( $timetype == 'T' )  {
  $entry_hour += $entry_ampm;

  if ( $eType == 'task'  ) {
    $due_hour += $due_ampm; 
  }
}

// Use end times
if ( $timed_ent_len == 'E' && $eType != 'task') {
  $end_hour +=  $end_ampm;
} else {
  $end_hour = 0;
  $end_minute = 0;
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

if (  $eType == 'task') {
  // Combine all values to create event due date/time - User Time
  $eventdue = mktime ( $due_hour, $due_minute, 0, $due_month, $due_day, $due_year );


  // Combine all values to create completed date 
  if ( ! empty ( $completed_year )  && ! empty ( $completed_month ) &&
      ! empty ( $completed_day ) ) 
      $eventcomplete =  sprintf( "%04d%02d%02d", $completed_year, 
        $completed_month, $completed_day );
}

//Create event stop from event  duration/end values
// Note: for any given event, either end times or durations are 0
if ( $timed_ent_len == 'E') {
 $eventstophour= $end_hour + $duration_h;
 $eventstopmin= $end_minute + $duration_m;
} else {
 $eventstophour= $entry_hour + $duration_h;
 $eventstopmin= $entry_minute + $duration_m;
}

if ($eType != 'task' ) {
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
} else {
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
$old_create_by = $WC->userId();
if ( empty ( $eid ) ) {
  // New event...
  $can_edit = true;
} else {
  // event owner or assistant event ?
  $sql = 'SELECT cal_create_by FROM webcal_entry WHERE cal_id = ?';
  $res = dbi_execute( $sql, array( $eid ) );
  if ($res) {
    $row = dbi_fetch_row ( $res );
    // value may be needed later for recreating event
    $old_create_by = $row[0];
    if (( $WC->isLogin( $row[0] ) ) || 
	  (( $WC->userId() == $row[0] ) && $WC->isNonuserAdmin() ) )
      $can_edit = true;
    dbi_free_result ( $res );
  } else
    $error = $dberror . dbi_error ();
}

if ( $WC->isAdmin() ) {
  $can_edit = true;
} else if ( ! empty ( $old_create_by ) ) {
  $can_edit = access_user_calendar ( 'edit', $old_create_by, $WC->loginId());
} 

if ( empty ( $error ) && ! $can_edit ) {
  // is user a participant of that event ?
  $sql = 'SELECT cal_id FROM webcal_entry_user WHERE cal_id = ? ' .
    "AND cal_login_id = ? AND cal_status IN ('W','A')";
  $res = dbi_execute ( $sql, array( $eid, $WC->loginId() ) );
  if ($res) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty( $row[0] ) )
      $can_edit = true; // is participant
    dbi_free_result ( $res );
  } else
    $error = $dberror . dbi_error ();
}

if ( ! $can_edit && empty ( $error ) ) {
  $error = print_not_auth ();
}

// CAPTCHA
if ( file_exists ( 'includes/classes/captcha/captcha.php' )  && 
  getPref ('ENABLE_CAPTCHA' ) ) {
  if ( function_exists ( 'imagecreatetruecolor' ) ) {
    include_once 'includes/classes/captcha/captcha.php';
    $res = captcha::check ();
    if ( ! $res ) {
      $error = translate ( 'You must enter the anti-spam text on the previous page' );
    }
  } else {
    // Should have seen warning on edit_entry.php, so no warning here...
  }
}

// If display of participants is disabled, set the participant list
// to the event creator.  This also works for single-user mode.
// Basically, if no participants were selected (because there
// was no selection list available in the form or because the user
// refused to select any participant from the list), then we will
// assume the only participant is the current user.
if ( empty ( $participants[0] ) ) {
  $participants[0] = $WC->loginId();
}

if ( ! getPref ( 'DISABLE_REPEATING_FIELD' ) ) {
  //process only if Expert Mode or Weekly
  if ( $rpt_type == 'weekly' || ! empty ( $rptmode ) ) {
    $bydayAr = explode ( ',', $bydayList );
    if ( ! empty ( $bydayAr) && $rpt_type != 'weekly' ) {
      foreach (  $bydayAr as $bydayElement ) {
        if ( strlen ( $bydayElement ) > 2 ) 
          $bydayAll[] = $bydayElement;
       }
    }

    if ( ! empty ( $bydayAll ) ) {
      $bydayAll = array_unique( $bydayAll );
      //call special sort algorithm
      usort ($bydayAll, 'sort_byday');
      $byday = implode (',', $bydayAll );
      //strip off leading comma if present
      if ( substr ( $byday, 0, 1 ) == "," )
        $byday = substr ( $byday, 1 );
    }
  }

  //This allows users to select on weekdays if daily
  if ( $rpt_type == 'daily' && ! empty ( $weekdays_only ) ) {
   $byday = 'MO,TU,WE,TH,FR';
  }
  //process only if expert mode and MonthbyDate or Yearly
  if ( ( $rpt_type == 'monthlyByDate' || $rpt_type == 'yearly' ) 
    && ! empty ( $rptmode ) ) {
    $bymonthdayAr = explode ( ',', $bymonthdayList );
    if ( ! empty ( $bymonthdayAr) ) {
      sort ($bymonthdayAr);
      $bymonthdayAr = array_unique( $bymonthdayAr );
      $bymonthday = implode (',', $bymonthdayAr );
    }
    //strip off leading comma if present
    if ( substr ( $bymonthday, 0, 1 ) == "," )
      $bymonthday = substr ( $bymonthday, 1 );
  }

  if ( $rpt_type == 'monthlyBySetPos' ) {
    $bysetposAr = explode ( ',', $bysetposList ); 
    if ( ! empty ( $bysetposAr) ) {
      sort ($bysetposAr);
      $bysetposAr = array_unique( $bysetposAr );
      $bysetpos = implode (',', $bysetposAr );
    }
    //strip off leading comma if present
    if ( substr ( $bysetpos, 0, 1 ) == "," )
      $bysetpos = substr ( $bysetpos, 1 );
  }

  //If expert mode not selected, we need to set the basic value
  //for monthlyByDay events
  if ( $rpt_type == 'monthlyByDay' && empty ( $rptmode ) &&
    empty ( $byday ) ) {
    $byday = ceil( $day / 7 ) . $WC->byday_names[ date ( 'w', $eventstart ) ];
  }

  $bymonth = ( ! empty ( $bymonth) ? implode (',', $bymonth ) : '' );
  
  
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
if ( getPref ( 'CHECK_CONFLICTS' ) && getPref ( 'CHECK_CONFLICTS_OVERRIDE' ) &&
  strlen ( $entry_hour ) > 0 && $timetype != 'U' && $eType != 'task') {
  $conflict_until = ( ! empty ( $rpt_until ) ? $rpt_until : '');
  $conflict_count = ( ! empty ( $count ) ? $count : 999);
  $dates = get_all_dates ( $eventstart, $rpt_type, $rpt_freq, $bymonth,
   $byweekno, $byyearday, $bymonthday, $byday, $bysetpos, $conflict_count,
   $conflict_until, $wkst, $exception_list, $inclusion_list );

  //make sure at least start date is in array
  if ( empty ( $dates ) ) $dates[0] = $eventstart;
  
  //make sure %thismonth and $thisyear are set for use in query_events()
  $thismonth = $month;
  $thisyear = $year;
  $conflicts = check_for_conflicts ( $dates, $duration, $eventstart,
    $participants, $WC->loginId(), empty ( $eid ) ? 0 : $eid );
} //end  check for any schedule conflicts

if ( empty ( $error ) && ! empty ( $conflicts ) ) {
  $error = translate( 'The following conflicts with the suggested time' ) .
    ': <ul>$conflicts</ul>';
}

$msg = '';
if ( empty ( $error ) ) {
  $newevent = true;
  // now add the entries
  if ( empty ( $eid ) || $do_override ) {
    $res = dbi_execute ( 'SELECT MAX(cal_id) FROM webcal_entry' );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $eid = $row[0] + 1;
      dbi_free_result ( $res );
    } else {
      $eid = 1;
    }
  } else {
    $newevent = false;
    // save old  values of participants
    $sql = 'SELECT cal_login_id, cal_status, cal_percent ' .
      'FROM webcal_entry_user WHERE cal_id = ? ';
    $res = dbi_execute ( $sql, array( $eid ) );
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
      dbi_execute ( 'DELETE FROM webcal_entry WHERE cal_id = ?', array( $eid ) );
      dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_id = ?', array( $eid ) );
      dbi_execute ( 'DELETE FROM webcal_entry_ext_user WHERE cal_id = ?', array( $eid ) );
      dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?', array( $eid ) );
      dbi_execute ( 'DELETE FROM webcal_site_extras WHERE cal_id = ?', array( $eid ) );
    }
    $newevent = false;
  }

  if ( $do_override ) {
    $sql = 'INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date, cal_exdate ) ' .
      'VALUES ( ?, ?, ? )';
    if ( ! dbi_execute ( $sql, array( $old_id, $override_date, 1 ) ) ) {
      $error = $dberror . dbi_error ();
    }
  }
  //TODO this is the IP storage stuff. make it generic
  $sql = 'INSERT INTO webcal_entry ( cal_id, ' .
    ( $old_id > 0 ? ' cal_parent_id, ': '' ) .
    'cal_create_by, cal_date, cal_rmt_addr, ' .
    ( ! empty ( $eventcomplete)? 'cal_completed, ': '' ) .
    'cal_due_date, cal_mod_date, cal_duration, cal_priority, ' .
    'cal_access, cal_type, cal_name, cal_description ' .
    ( ! empty ( $location )? ',cal_location ': '' ) .
    ( ! empty ( $entry_url )? ',cal_url ': '' ) .
    ' ) VALUES ( ?, ' .
    ( $old_id > 0 ? '?, ': '' ) .
    '?, ?, ?, ' .
    ( ! empty ( $eventcomplete ) ? '?, ': '' ) .
    '?, ?, ?, ?, ?, ?, ?, ? ' .
    ( ! empty ( $location ) ? ',? ': '' ) .
    ( ! empty ( $entry_url ) ? ',? ': '' ) .
    ')';

  $query_params = array();
  $query_params[] = $eid;
  if ( $old_id > 0 )
    $query_params[] = $old_id;

  $query_params[] = ( ! empty ( $old_create_by ) ? 
    $old_create_by : $WC->loginId() );
  $query_params[] = $eventstart;

  $query_params[] = ip2long ( $_SERVER['REMOTE_ADDR'] );    
  if ( ! empty ( $eventcomplete ) )
    $query_params[] = $eventcomplete;
  //just set $eventstart to something
  $eventdue = ( ! empty ( $eventdue ) ? $eventdue : $eventstart );
  $query_params[] = $eventdue;
  $query_params[] = time();
  $query_params[] = sprintf ( "%d", $duration );
  $query_params[] = $priority;
  
  $query_params[] = $access;
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
  if ( ! empty ( $location ) )
    $query_params[] = $location;
  if ( ! empty ( $entry_url ) )
    $query_params[] = $entry_url;

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
  activity_log ( $eid, $WC->loginId(), $WC->isNonuserAdmin() ? 
    $WC->userId() : $WC->loginId(),
    $newevent ? $log_c : $log_u, '' );
  
  if ( _WC_SINGLE_USER ) {
    $participants[0] = _WC_SINGLE_USER_LOGIN;
  }

  //add categories
  $cat_owner =  $WC->userLoginId();
  dbi_execute ( 'DELETE FROM webcal_entry_categories WHERE cal_id = ? ' .
    'AND ( cat_owner = ? OR cat_owner IS NULL )', array( $eid, $cat_owner ) );
  if ( $WC->catId() ) {
    $categories = explode (',', $WC->catId() );
    $categorycnt = count( $categories );
    for ( $i =0; $i < $categorycnt; $i++ ) {
      $names = array();
      $values = array(); 
      $names[] = 'cal_id';
      $values[]  = $eid; 
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
      $valuecnt = count( $values );
      for ( $v_i = 0; $v_i < $valuecnt; $v_i++ ) {
        $placeholders .= '?,';
      }
      $placeholders = preg_replace( "/,$/", "", $placeholders ); // remove trailing ','
      $sql = 'INSERT INTO webcal_entry_categories ( ' . implode ( ', ', $names ) .
        " ) VALUES ( $placeholders )"; 
      if ( ! dbi_execute ( $sql, $values ) ) {
        $error = $dberror . dbi_error ();
        break;
      }
    }
  }  
  // add site extras
  $site_extracnt = count ( $site_extras );
  $extra_email_data = '';
  for ( $i = 0; $i < $site_extracnt && empty ( $error ); $i++ ) {
    $sql = '';
    if ( $site_extras[$i] == 'FIELDSET' ) continue;
    $extra_name = $site_extras[$i][0];
    $extra_type = $site_extras[$i][2];
    $extra_arg1 = $site_extras[$i][3];
    $extra_arg2 = $site_extras[$i][4];
    if ( ! empty ( $site_extras[$i][5] ) )
      $extra_email = $site_extras[$i][5] & EXTRA_DISPLAY_EMAIL;
    $value = $$extra_name;
    //echo "Looking for $extra_name... value = " . $value . " ... type = " .
    // $extra_type . "<br />\n";
    
    $sql = '';
    $query_params = array();

    if ( strlen ( $extra_name ) || $extra_type == EXTRA_DATE ) {
      if ( $extra_type == EXTRA_URL || $extra_type == EXTRA_EMAIL ||
        $extra_type == EXTRA_TEXT || $extra_type == EXTRA_USER ||
        $extra_type == EXTRA_MULTILINETEXT ||
        $extra_type == EXTRA_SELECTLIST || $extra_type == EXTRA_RADIO ||
         $extra_type == EXTRA_CHECKBOX ) {
        // We were passed an array instead of a string
        if ( $extra_type == EXTRA_SELECTLIST && $extra_arg2 > 0 )
          $value = implode ( ',', $value );

        $sql = 'INSERT INTO webcal_site_extras ' .
          '( cal_id, cal_name, cal_type, cal_data ) VALUES ( ?, ?, ?, ? )';
        $query_params = array( $eid, $extra_name, $extra_type, $value );
        if ( ! empty ( $extra_email ) ) {
          $value = ( $extra_type == EXTRA_RADIO ? $extra_arg1[$value] : $value );
          $extra_email_data .= $extra_name . ': ' . $value . "\n";
        }
      } else if ( $extra_type == EXTRA_DATE )  {
        $yname = $extra_name . 'year';
        $mname = $extra_name . 'month';
        $dname = $extra_name . 'day';
        $edate = sprintf ( "%04d%02d%02d", $$yname, $$mname, $$dname );
        $sql = 'INSERT INTO webcal_site_extras ' .
          '( cal_id, cal_name, cal_type, cal_date ) VALUES ( ?, ?, ?, ? )';
        $query_params = array( $eid, $extra_name, $extra_type, $edate );
        if ( ! empty ( $extra_email ) )
          $extra_email_data .= $extra_name . ': ' . $edate . "\n";
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
  if ( ! dbi_execute ( 'DELETE FROM webcal_reminders WHERE cal_id = ?', array( $eid ) ) )
    $error = $dberror . dbi_error ();
  if ( ! getPref ( 'DISABLE_REMINDER_FIELD' ) && $reminder == true ) {
    if ( empty ( $rem_related ) ) $rem_related = 'S';
    if ( empty ( $rem_before ) ) $rem_before = 'Y';
    if ( empty ( $rem_last_sent ) ) $rem_last_sent = '0';
    $reminder_date = $reminder_offset = $reminder_duration = $reminder_repeats = 0;
    if ( $rem_when == 'Y' ) { //use date
      $reminder_hour +=  $reminder_ampm;
      $reminder_date = gmmktime ( $reminder_hour, $reminder_minute, 0, $reminder_month,
        $reminder_day, $reminder_year ); 
    } else { //use offset
      $reminder_offset = ($rem_days * 60 * 24 ) + ( $rem_hours * 60 ) + 
        $rem_minutes;
    }
    if ( $rem_rep_count > 0 ) {
      $reminder_repeats = $rem_rep_count;
      $reminder_duration = ($rem_rep_days * 60 * 24 ) + 
        ( $rem_rep_hours * 60 ) + $rem_rep_minutes;      
    }
    $sql = 'INSERT INTO webcal_reminders ( cal_id, cal_date, cal_offset, 
	  cal_related, cal_before, cal_repeats, cal_duration, cal_action, 
	  cal_last_sent, cal_times_sent ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';
      if ( ! dbi_execute ( $sql, array( $eid, $reminder_date, $reminder_offset, 
        $rem_related, $rem_before,$reminder_repeats, $reminder_duration, $rem_action, 
        $rem_last_sent, $rem_times_sent ) ) )
        $error = $dberror . dbi_error ();    
  }
  // clearly, we want to delete the old repeats, before inserting new...
  if ( empty ( $error ) ) {
    if ( ! dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?', 
	  array( $eid ) ) ) {
      $error = $dberror . dbi_error ();
    }
  if ( ! dbi_execute ( 'DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?', 
    array( $eid ) ) ) {
      $error = $dberror . dbi_error ();
  }
    // add repeating info
  if ( ! empty ( $rpt_type ) && strlen ( $rpt_type ) && $rpt_type != 'none' ) {
    $freq = ( $rpt_freq ? $rpt_freq : 1 );      

    $names = array();
    $values = array();
    $names[] = 'cal_id';
    $values[]  = $eid;
   
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
      $values[] = $rpt_until;
    }

    $placeholders = '';
  $valuecnt = count( $values );
  for ( $v_i = 0; $v_i < $valuecnt; $v_i++ ) {
      $placeholders .= '?,';
  }
  $placeholders = preg_replace( "/,$/", '', $placeholders ); // remove trailing ','
    $sql = 'INSERT INTO webcal_entry_repeats ( ' . implode ( ', ', $names ) .
       " ) VALUES ( $placeholders )"; 
      dbi_execute ( $sql, $values );
      $msg .= "<span class=\"bold\">SQL:</span> $sql<br />\n<br />";

    } //end add repeating info
    //We manually created exceptions. This can be done without repeats
     if ( ! empty ($exceptions ) ) {
       $exceptcnt = count ( $exceptions );
       for ( $i = 0; $i < $exceptcnt; $i++ ) {
         $sql = 'INSERT INTO webcal_entry_repeats_not ( cal_id, 
		   cal_date, cal_exdate ) VALUES ( ?, ?, ? )';
         if ( ! dbi_execute ( $sql, array( $eid, substr ($exceptions[$i],1,8 ), 
           ( ( substr ($exceptions[$i],0, 1 ) == '+' ) ? 0 : 1 ) ) ) ) {
           $error = $dberror . dbi_error ();
         }
       }
      } //end exceptions    
  } 
  //EMAIL PROCESSING
  $send_email = getPref ( 'SEND_EMAIL' );
  $partcnt = count ( $participants );
  $from = $login_email;
  if ( empty ( $from ) && getPref ('EMAIL_FALLBACK_FROM' ) )
    $from = getPref ( 'EMAIL_FALLBACK_FROM' );
  $default_language = getPref ( 'LANGUAGE' );
  // check if participants have been removed and send out emails
  if ( ! $newevent && count ( $old_status ) > 0 ) {  
    while ( list ( $old_participant, $dummy ) = each ( $old_status ) ) {
      $found_flag = false;
      for ( $i = 0; $i < $partcnt; $i++ ) {
        if ( $participants[$i] == $old_participant ) {
          $found_flag = true;
          break;
        }
      }
      //check UAC
      $can_email = access_user_calendar ( 'email', 
	    $old_participant, $WC->loginId());
      $is_nonuser_admin = $WC->isNonuserAdmin ( $old_participant, 
			  $WC->loginId() );
      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if ( !$found_flag && !$is_nonuser_admin && $can_email == 'Y') {
        // only send mail if their email address is filled in
        $do_send = getPref ( 'EMAIL_EVENT_DELTED', 1, $old_participant );
        $htmlmail = getPref ( 'EMAIL_HTML', 1, $old_participant );
        $t_format = getPref ( 'TIME_FORMAT', 1, $old_participant );
        $user_TIMEZONE = getPref ( 'TIMEZONE', 1, $old_participant );
        set_env ( 'TZ', $user_TIMEZONE );
        $user_language = getPref ( 'LANGUAGE', 1, $old_participant );
        $WC->User->loadVariables ( $old_participant, 'temp' );
        
        
        if ( ! $WC->isLogin( $old_participant ) && 
		  ! empty ( $tempemail ) && $do_send == 'Y' && $send_email ) {     
       
          if ( empty ( $user_language ) || ( $user_language == 'none' )) {
             reset_language ( $default_language );
          } else {
             reset_language ( $user_language );
          }
   
          $msg = translate( 'Hello' ) . ', ' . $tempfullname . ".\n\n" .
            translate( 'An appointment has been canceled for you by' ) .
            ' ' . $login_fullname .  ".\n" .
            translate( 'The subject was' ) . ' "' . $name . "\"\n\n" .
            translate( 'The description is' ) . ' "' . $description . "\"\n" .
            translate( 'Date') . ': ' . date_to_str ( $eventstart ) . "\n" .
             ( $timetype != 'T'  ? '' :
            translate( 'Time' ) . ': ' .
            // Apply user's GMT offset and display their TZID
            smarty_modifier_display_time ( $eventstart, 2, $t_format ) . "\n\n\n");
          // add URL to event, if we can figure it out
          if ( ! empty ( $server_url ) ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $server_url .  'view_entry.php?eid=' .  $eid . '&em=1';
            if ( $htmlmail == 'Y' ) {
              $url =  activate_urls ( $url ); 
            }
            $msg .= $url . "\n\n";
          }
          $mail->WC_Send ( $login_fullname, $tempemail, 
            $tempfullname, $name, $msg, $htmlmail, $from );      
          activity_log ( $eid, $WC->loginId(), $old_participant, 
		    LOG_NOTIFICATION, 'User removed from participants list' );
        }
      }
    }
  }
  $send_own =  getPref ( 'EMAIL_EVENT_CRETE' );
  // now add participants and send out notifications
  for ( $i = 0; $i < $partcnt; $i++ ) {
    // Is the person adding the nonuser calendar admin
    $is_nonuser_admin = $WC->isNonuserAdmin ( $participants[$i],
		  $WC->loginId(), 
	   );

    if ( ! $newevent ) {
      // keep the old status if no email will be sent
      $send_user_mail = ( empty ( $old_status[$participants[$i]] ) ||
        $entry_changed ) ?  true : false;
      $tmp_status = ( ! empty ( $old_status[$participants[$i]] ) && 
        ! $send_user_mail ? $old_status[$participants[$i]] : 'W' );
      $status = ( ! $WC->isLogin( $participants[$i] ) && 
        boss_must_approve_event ( $WC->loginId(), $participants[$i] ) && 
        getPref ( 'REQUIRE_APPROVALS' ) && ! $is_nonuser_admin ) ?
        $tmp_status : 'A';
      
      //set percentage to old_percent if not owner
      $tmp_percent = ( ! empty ( $old_percent[$participants[$i]] ) ? 
        $old_percent[$participants[$i]] : 0 );
      //TODO this logic needs work 
      $new_percent = ( ! $WC->isLogin( $participants[$i] ) ) ?
        $tmp_percent : $percent;

      //TODO Add check for approvals required for all NUCs
    } else {  // New Event
      $send_user_mail = true;
      $status = ( ! $WC->isLogin( $participants[$i] ) && 
        boss_must_approve_event ( $WC->loginId(), $participants[$i] ) && 
        getPref ( 'REQUIRE_APPROVALS' ) && ! $is_nonuser_admin ) ?
        'W' : 'A';
      $new_percent = ( ! $WC->isLogin( $participants[$i] ) ) ? 
	    0 : $percent;
    } //end new/old event
  
    // Some users report that they get an error on duplicate keys
    // on the following add... As a safety measure, delete any
    // existing entry with the id.  Ignore the result.
    dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_id = ? ' .
      'AND cal_login_id = ?', array( $eid, $participants[$i] ) );
    $sql = 'INSERT INTO webcal_entry_user ' .
      '( cal_id, cal_login_id, cal_status, cal_percent ) VALUES ( ?, ?, ?, ? )';
    if ( ! dbi_execute ( $sql, array( $eid, $participants[$i], $status, $new_percent ) ) ) {
      $error = $dberror . dbi_error ();
      break;

    } else {
      //check UAC
      $can_email = access_user_calendar ( 'email', 
	    $participants[$i], $WC->loginId());
      // Don't send mail if we are editing a non-user calendar
      // and we are the admin
      if ( ! $WC->isNonuserAdmin() && $can_email == 'Y') {
        // only send mail if their email address is filled in
        $do_send = getPref ( $newevent ? 'EMAIL_EVENT_ADED' : 
		  'EMAIL_EVENT_UPDTED', 1, $participants[$i] );
        $htmlmail = getPref ( 'EMAIL_HTML', 1, $participants[$i] );
        $t_format = getPref ( 'TIME_FORMAT', 1, $participants[$i] );
        $user_TIMEZONE = getPref ( 'TIMEZONE', 1, $participants[$i] );
        set_env ( 'TZ', $user_TIMEZONE );
        $user_language = getPref ( 'LANGUAGE', 1, $participants[$i] );
        $WC->User->loadVariables ( $participants[$i], 'temp' );
        if ( boss_must_be_notified ( $WC->loginId(), $participants[$i] ) && 
          ! empty ( $tempemail ) &&
          $do_send == 'Y' && $send_user_mail && $send_email ) {
          // We send to creator if they want it
          if ( $send_own != 'Y' && ( $WC->login( $participants[$i] ) ) )
            continue; 
          if ( empty ( $user_language ) || ( $user_language == 'none' )) {
             reset_language ( $default_language );
          } else {
             reset_language ( $user_language );
          }
 
          $msg = translate( 'Hello' ) . ', ' . $tempfullname . ".\n\n";
          if ( $newevent || ( empty ( $old_status[$participants[$i]] ) ) ) {
            $msg .= translate( 'A new appointment has been made for you by' );
          } else {
            $msg .= translate( 'An appointment has been updated by' );
          }
          $msg .= ' ' . $login_fullname .  ".\n" .
            translate( 'The subject is' ) . ' "' . $name . "\"\n\n" .
            translate( 'The description is' ) . ' "' . $description . "\"\n" .
            translate( 'Date' ) . ': ' . date_to_str ( $eventstart ) . "\n" .
            ( $timetype != 'T' ? '' :
            translate( 'Time' ) . ': ' .
            // Apply user's GMT offset and display their TZID
            smarty_modifier_display_time ( $eventstart, 2, $t_format ) . "\n" ) .
            // Add Site Extra Date if permitted
            $extra_email_data .
            translate( 'Please look on' ) . ' ' . generate_application_name () . 
            ' ' . ( getPref ( 'REQUIRE_APPROVALS' ) ?
            translate( 'to accept or reject this appointment' ) :
            translate( 'to view this appointment' ) ) . '.';
          // add URL to event, if we can figure it out
          if ( $server_url ) {
            //DON'T change & to &amp; here. email will handle it
            $url = $server_url .  'view_entry.php?eid=' .  $eid . '&em=1';
            if ( $htmlmail == 'Y' ) {
              $url =  activate_urls ( $url ); 
            }
            $msg .= "\n\n" . $url;
          }
          //use WebCalMailer class
          $mail->WC_Send ( $login_fullname, $tempemail, 
            $tempfullname, $name, $msg, $htmlmail, $from );          
          activity_log ( $eid, $WC->loginId(), 
		    $participants[$i], LOG_NOTIFICATION, '' );
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
if ( !_WC_SINGLE_USER && getPref ( 'ALLOW_EXTERNAL_USERS' ) &&
  ! empty ( $externalparticipants ) ) {
  $lines = explode ( "\n", $externalparticipants );
  if ( ! is_array ( $lines ) ) {
    $lines = array ( $externalparticipants );
  }
  if ( is_array ( $lines ) ) {
    $linecnt = count ( $lines );
    for ( $i = 0; $i < $linecnt; $i++ ) {
      $ext_words = explode ( ' ', $lines[$i] );
      if ( ! is_array ( $ext_words ) ) {
        $ext_words = array ( $lines[$i] );
      }
      if ( is_array ( $ext_words ) ) {
        $ext_wordscnt = count ( $ext_words );
        $ext_names[$ext_count] = '';
        $ext_emails[$ext_count] = '';
        for ( $j = 0; $j < $ext_wordscnt; $j++ ) {
          // use regexp matching to pull email address out
          $ext_words[$j] = chop ( $ext_words[$j] ); // remove \r if there is one
          if ( preg_match ( "/<?\\S+@\\S+\\.\\S+>?/", $ext_words[$j],
            $matches ) ) {
            $ext_emails[$ext_count] = $matches[0];
            $ext_emails[$ext_count] = preg_replace ( "/[<>]/", '',
              $ext_emails[$ext_count] );
          } else {
            if ( strlen ( $ext_names[$ext_count] ) ) {
              $ext_names[$ext_count] .= ' ';
            }
            $ext_names[$ext_count] .= $ext_words[$j];
          }
        }
        // Test for duplicate Names
        if ( $i > 0 ) {
          for ( $k = $i; $k > 0; $k-- ) {
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
    $ext_namescnt = count ( $ext_names );
    for ( $i = 0; $i < $ext_namescnt; $i++ ) {
      if ( strlen ( $ext_names[$i] ) ) {
        $sql = 'INSERT INTO webcal_entry_ext_user ' .
          '( cal_id, cal_fullname, cal_email ) VALUES ( ?, ?, ? )';
        if ( ! dbi_execute ( $sql, array( $eid, $ext_names[$i], 
          ( strlen ( $ext_emails[$i] ) ? $ext_emails[$i] : NULL ) ) ) ) {
          $error = $dberror . dbi_error ();
        }
        // send mail notification if enabled
        // TODO: move this code into a function...
        if ( getPref ( 'EXTERNAL_NOTIFICATIONS' ) && $send_email &&
          strlen ( $ext_emails[$i] ) > 0 ) {          
          if ( ( ! $newevent && getpref ( 'EXTERNAL_UPDATES' ) ) || $newevent ) { 
            // Strip [\d] from duplicate Names before emailing
            $ext_names[$i] = trim(preg_replace( '/\[[\d]]/', '', $ext_names[$i]) );
            $msg = translate( 'Hello' ) . ', ' . $ext_names[$i] . ".\n\n";
            if ( $newevent ) {
              $msg .= translate( 'A new appointment has been made for you by' );
            } else {
              $msg .= translate( 'An appointment has been updated by' );
            }
            $msg .= ' ' . $login_fullname .  ".\n" .
              translate( 'The subject is' ) . ' "' . $name . "\"\n\n" .
              translate( 'The description is' ) . ' "' . $description . "\"\n\n" .
              translate( 'Date') . ': ' . date_to_str ( $eventstart ) . "\n";
              if ( $timetype == 'T')  {
                $msg .= translate( 'Time' ) . ': ';
                if ( getpref ( 'GENERAL_USE_GMT' ) ) {
                  // Do not apply TZ offset & display TZID, which is GMT
                  $msg .= smarty_modifier_display_time ( $eventstart, 3 );
                } else {
                  // Display time in server's timezone
                  $msg .= smarty_modifier_display_time ( $eventstart, 6 );              
                }
              } 
              // Add Site Extra Date if permitted
              $msg .= $extra_email_data;         
            //don't send HTML to external adresses  
            $mail->WC_Send ( $login_fullname, $ext_emails[$i], 
              $ext_names[$i], $name, $msg, 'N', $from, $eid );       
          }
        } 
      }
    }
  } //end external mail

} //end empty error

// If we were editing this event, then go back to the last view (week, day,
// month).  If this is a new event, then go to the preferred view for
// the date range that this event was added to.
if ( empty ( $error )  && empty ( $mailerError ) ) {
  $return_view = get_last_view ();
  if ( ! empty ( $return_view ) ) {
    do_redirect ( $return_view );
  } else {
  $xdate = sprintf ( "%04d%02d%02d", $year, $month, $day );
  $user_args = ( ! $WC->userId() ? '' : 'user=' . $WC->userId() );
  send_to_preferred_view ( $xdate, $user_args );
  }
}

if ( ! empty ( $conflicts ) ) { 
build_header ();
?>
<h2><?php etranslate( 'Scheduling Conflict' )?></h2>

<?php etranslate( 'Your suggested time of' )?> <span class="bold">
<?php
  if (  $timetype == 'A' ) {
    etranslate( 'All day event' );
  } else {
    $time = sprintf ( "%d%02d00", $entry_hour, $entry_minute );
    // Pass the adjusted timestamp in case the date changed due to GMT offset 
    echo smarty_modifier_display_time ( $eventstart, 0 );
    if ( $duration > 0 ) {
      echo "-" . smarty_modifier_display_time ( $eventstart + ( $duration * 60 ), 0 );
    }
  }
?></span> <?php etranslate( 'conflicts with the following existing calendar entries' )?>:
<ul>
<?php echo $conflicts; ?>
</ul>

<?php
// user can confirm conflicts
  echo '<form name="confirm" method="post">' . "\n";
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
  if ( getPref ( 'ALLOW_CONFLICT_OVERRIDE' ) ) {
    echo '<td><input type="submit" name="confirm_conflicts" value="' . 
      translate( 'Save' ) . "\" /></td>\n";
  }
?>
   <td><input type="button" value="<?php etranslate( 'Cancel' )?>" 
onclick="history.back()" /><td>
 </tr>
</table>
</form>

<?php } else {
//process errors
$mail->MailError ( $mailerError, $error );
}
 ?>
