#!/usr/local/bin/php -q
<?php
/* $Id$
 *
 * Description:
 * This is a command-line script that will send out any email
 * reminders that are due.
 *
 * Usage:
 * php send_reminders.php
 *
 * Setup:
 * This script should be setup to run periodically on your system.
 * You could run it once every minute, but every 5-15 minutes should be
 * sufficient.
 *
 * To set this up in cron, add a line like the following in your crontab
 * to run it every 10 minutes:
 *   1,11,21,31,41,51 * * * * php /some/path/here/send_reminders.php
 * Of course, change the path to where this script lives.  If the
 * php binary is not in your $PATH, you may also need to provide
 * the full path to "php".
 * On Linux, just type crontab -e to edit your crontab.
 *
 * If you're a Windows user, you'll either need to find a cron clone
 * for Windows (they're out there) or use the Windows Task Scheduler.
 * (See docs/WebCalendar-SysAdmin.html for instructions.)
 * 
 * Comments:
 * You will need access to the PHP binary (command-line) rather than
 * the module-based version that is typically installed for use with
 * a web server.to build as a CGI (rather than an Apache module) for
 *
 * If running this script from the command line generates PHP
 * warnings, you can disable error_reporting by adding
 * "-d error_reporting=0" to the command line:
 *   php -d error_reporting=0 /some/path/here/tools/send_reminders.php
 *
 *********************************************************************/

// How many days in advance can a reminder be sent (max)
// this will affect performance, but keep in mind that someone may enter
// a reminder to be sent 60 days in advance or they may enter a specific
// date for a reminder to be sent that is more than 30 days before the
// event's date.  If you're only running this once an hour or less often,
// then you could certainly change this to look a whole 365 days ahead.
$DAYS_IN_ADVANCE = 30;
//$DAYS_IN_ADVANCE = 365;


// Load include files.
// If you have moved this script out of the WebCalendar directory,
// which you probably should do since it would be better for security
// reasons, you would need to change $includedir to point to the
// webcalendar include directory.
$basedir = '..'; // points to the base WebCalendar directory relative to
                 // current working directory
$includedir = '../includes';
$old_path = ini_get('include_path');
$delim = ( strstr ( $old_path, ';' )? ';' : ':');
ini_set('include_path', $old_path . $delim . $includedir . $delim);

require_once "$includedir/classes/WebCalendar.class";
require_once "$includedir/classes/Event.class";
require_once "$includedir/classes/RptEvent.class";
require_once "$includedir/classes/WebCalMailer.class";

$WebCalendar =& new WebCalendar ( __FILE__ );

include "$includedir/config.php";
include "$includedir/dbi4php.php";
include "$includedir/functions.php";

$WebCalendar->initializeFirstPhase();

include "$includedir/$user_inc";
include "$includedir/site_extras.php";
include "$includedir/translate.php";

$WebCalendar->initializeSecondPhase();

$debug = true; // set to true to print debug info...
$only_testing = false; // act like we're sending, but don't send -- for debugging

// Establish a database connection.
$c = dbi_connect ( $db_host, $db_login, $db_password, $db_database, true );
if ( ! $c ) {
  echo 'Error connecting to database: ' . dbi_error ();
  exit;
}

load_global_settings ();

$WebCalendar->setLanguage();

set_today();

if ( $debug )
  echo '<br />Include Path=' . ini_get('include_path') . " <br />\n";

// Get a list of people who have asked not to receive email
$res = dbi_execute ( "SELECT cal_login FROM webcal_user_pref " .
  "WHERE cal_setting = 'EMAIL_REMINDER' " .
  "AND cal_value = 'N'" );
$noemail = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $user = $row[0];
    $noemail[$user] = 1;
    if ( $debug )
      echo "User $user does not want email. <br />\n";
  }
  dbi_free_result ( $res );
}

// Get a list of the email users in the system.
// They must also have an email address.  Otherwise, we can't
// send them mail, so what's the point?
$allusers = user_get_users ();
$allusercnt = count ( $allusers );
for ( $i = 0; $i < $allusercnt; $i++ ) {
  $names[$allusers[$i]['cal_login']] = $allusers[$i]['cal_fullname'];
  $emails[$allusers[$i]['cal_login']] = $allusers[$i]['cal_email'];
}


// Get all users language settings.
$res = dbi_execute ( "SELECT cal_login, cal_value FROM webcal_user_pref " .
  "WHERE cal_setting = 'LANGUAGE'" );
$languages = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $user = $row[0];
    $user_lang = $row[1];
    $languages[$user] = $user_lang;
    if ( $debug )
      echo "Language for $user is \"$user_lang\" <br />\n";
  }
  dbi_free_result ( $res );
}
// Get all users time format settings.
$res = dbi_execute ( "SELECT cal_login, cal_value FROM webcal_user_pref " .
  "WHERE cal_setting = 'TIME_FORMAT'" );
$t_format = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $user = $row[0];
    $user_t_format = $row[1];
    $t_format[$user] = $user_t_format;
    if ( $debug )
      echo "Time Format for $user is \"$user_t_format\" <br />\n";
  }
  dbi_free_result ( $res );
}
// Just get list of users who have asked for HTML (default is plain text)
$res = dbi_execute ( "SELECT cal_login, cal_value FROM webcal_user_pref " .
  "WHERE cal_setting = 'EMAIL_HTML' AND cal_value = 'Y'" );
$htmlmail = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $user = $row[0];
    $htmlmail[$user] = true;
    if ( $debug )
      echo "User $user wants HTML mail <br />\n";
  }
  dbi_free_result ( $res );
}

// Get all users timezone settings.
$res = dbi_execute ( "SELECT cal_login, cal_value FROM webcal_user_pref " .
  "WHERE cal_setting = 'TIMEZONE'" );

$tz = array (); 
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $user = $row[0];
    $tz[$user] = $row[1];
    if ( $debug )
      echo "TIMEZONE for $user is \"$tz[$user]\"<br />\n";
  }
  dbi_free_result ( $res );
}

if ( empty ( $GENERAL_USE_GMT ) || $GENERAL_USE_GMT != 'Y' ) {
  $def_tz = $SERVER_TIMEZONE;
}

$startdateTS = gmmktime(0,0,0);
$enddateTS = $startdateTS + ( $DAYS_IN_ADVANCE * ONE_DAY );

$startdate = date ( 'Ymd', $startdateTS );
$enddate = date ( 'Ymd', $enddateTS );

// Now read events all the repeating events (for all users)
$repeated_events = query_events ( "", true, "AND (webcal_entry_repeats.cal_end >= 
  $startdate OR webcal_entry_repeats.cal_end IS NULL) " );
$repcnt = count ( $repeated_events );
// Read non-repeating events (for all users)
if ( $debug )
  echo "Checking for events from date $startdate to date $enddate <br />\n";
$events = read_events ( "", $startdateTS, $enddateTS );
$eventcnt = count ( $events );
if ( $debug )
  echo "Checking for tasks from date $startdate to date $enddate <br />\n";
$tasks = read_tasks ( '', $enddateTS );
$taskcnt = count ( $tasks );
if ( $debug ) {
  $found = 0;
  $found = $eventcnt + $taskcnt + $repcnt;
  echo 'Found ' . $found . " events in time range. <br />\n";
}
$is_task = false;
for ( $d = 0; $d < $DAYS_IN_ADVANCE; $d++ ) {
  $date = gmdate ( 'Ymd', time() + ( $d * ONE_DAY ) );
  //echo "Date: $date\n";
  // Get non-repeating events for this date.
  // An event will be included one time for each participant.
  $ev = get_entries ( $date );
  // Keep track of duplicates
  $completed_ids = array ( );
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    $id = $ev[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    process_event ( $id, $ev[$i]->getName(), $ev[$i]->getDateTimeTS(), 
      $ev[$i]->getEndDateTimeTS() ); 

  }
  // Get tasks for this date.
  // A task will be included one time for each participant.
  $tks = get_tasks ( $date );
  // Keep track of duplicates
  $completed_ids = array ( );
  $tkscnt = count ( $tks );
  for ( $i = 0; $i < $tkscnt; $i++ ) {
    $id = $tks[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    $is_task = true;  
    process_event ( $id, $tks[$i]->getName(), $tks[$i]->getDateTimeTS(), 
      $tks[$i]->getDueDateTimeTS(), $date );
  }
  $is_task = false;
  //Get repeating events...tasks are not included at this time
  $rep = get_repeating_entries ( "", $date );
  $repcnt = count ( $rep );
  for ( $i = 0; $i < $repcnt; $i++ ) {
    $id = $rep[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    process_event ( $id, $rep[$i]->getName(), $rep[$i]->getDateTimeTS(), 
      $rep[$i]->getEndDateTimeTS(), $date );  
  }
}

if ( $debug )
  echo "Done.<br />\n";
  

// Send a reminder for a single event for a single day to all
// participants in the event.
// Send to participants who have accepted as well as those who have not yet
// approved.  But, don't send to users who rejected (cal_status='R').
function send_reminder ( $id, $event_date ) {
  global $names, $emails, $site_extras, $debug, $only_testing, $htmlmail,
    $SERVER_URL, $languages, $APPLICATION_NAME;
  global $ALLOW_EXTERNAL_USERS, $EXTERNAL_REMINDERS, $LANGUAGE,
    $def_tz, $tz, $t_format, $is_task;

  $pri[1] = translate( 'Low', true);
  $pri[2] = translate( 'Medium', true);
  $pri[3] = translate( 'High', true);

  // get participants first...
  $sql = "SELECT cal_login, cal_percent FROM webcal_entry_user " .
    "WHERE cal_id = ? AND cal_status IN ('A','W') " .
    "ORDER BY cal_login";
  $res = dbi_execute ( $sql , array ( $id ) );
  $participants = array ();
  $num_participants = 0;
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$num_participants++] = $row[0];
      $percentage[$row[0]] = $row[1];
    }
  }
  $partcnt = count ( $participants );
  // get external participants
  $ext_participants = array ();
  $num_ext_participants = 0;
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == 'Y' &&
    ! empty ( $EXTERNAL_REMINDERS ) && $EXTERNAL_REMINDERS == 'Y' ) {
    $sql = "SELECT cal_fullname, cal_email FROM webcal_entry_ext_user " .
      "WHERE cal_id = ? AND cal_email IS NOT NULL " .
      "ORDER BY cal_fullname";
    $res = dbi_execute ( $sql , array ( $id ) );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $ext_participants[$num_ext_participants] = $row[0];
        $ext_participants_email[$num_ext_participants++] = $row[1];
      }
    }
  }
  $ext_partcnt = count ( $ext_participants );
  if ( ! $num_participants && ! $num_ext_participants ) {
    if ( $debug )
      echo "No participants found for event id: $id <br />\n";
    return;
  }


  // get event details
  $res = dbi_execute (
    "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
    "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
    "cal_name, cal_description, cal_due_date, cal_due_time " .
  "FROM webcal_entry WHERE cal_id = ?" , array ( $id ) );
  if ( ! $res ) {
    echo "Db error: could not find event id $id.\n";
    return;
  }


  if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
    echo "Error: could not find event id $id in database.\n";
    return;
  }

  // send mail.  we send one user at a time so that we can switch
  // languages between users if needed (as well as html vs plain text).
  $mailusers = array ();
  $recipients = array ();
  if ( isset ( $single_user ) && $single_user == 'Y' ) {
    $mailusers[] = $emails[$single_user_login];
    $recipients[] = $single_user_login;
  } else {
    for ( $i = 0; $i < $partcnt; $i++ ) {
      if ( strlen ( $emails[$participants[$i]] ) ) {
        $mailusers[] = $emails[$participants[$i]];
        $recipients[] = $participants[$i];
      } else {
        if ( $debug )
          echo "No email for user $participants[$i] <br />\n";
      }
    }
    for ( $i = 0; $i < $ext_partcnt; $i++ ) {
      $mailusers[] = $ext_participants_email[$i];
      $recipients[] = $ext_participants[$i];
    }
  }
  $mailusercnt = count ( $mailusers );
  if ( $debug )
    echo 'Found ' . $mailusercnt . " with email addresses <br />\n";
  for ( $j = 0; $j < $mailusercnt; $j++ ) {
    $recip = $mailusers[$j];
    $user = $recipients[$j];
    $isExt = ( in_array ( $user, $participants ) ? false : true );
    if ( ! empty ( $languages[$user] ) )
      $userlang = $languages[$user];
    else
      $userlang = $LANGUAGE; // system default
    if ( ! empty ( $t_format[$user] ) )
      $userTformat = $t_format[$user];
    else
      $userTformat = 24; // gotta pick something
    if ( $userlang == 'none' )
      $userlang = 'English-US'; // gotta pick something
    if ( $debug )
      echo "Setting language to \"$userlang\" <br />\n";
    reset_language ( $userlang );
    // reset timezone setting for current user
    if ( ! empty ( $tz[$user] ) ) {
      $display_tzid = 2;  // display TZ
      $user_TIMEZONE = $tz[$user];
    } else if ( ! empty ( $def_tz ) ) {
      $display_tzid = 2;
      $user_TIMEZONE = $def_tz;  
    } else {
      $display_tzid = 3; // Do not use offset & display TZ
     //I think this is the only real timezone set to UTC...since 1972 at least
      $user_TIMEZONE = 'Africa/Monrovia';
    }
    //this will allow date functions to use the proper TIMEZONE
    set_env ( 'TZ', $user_TIMEZONE );
    
    $useHtml = ( ! empty ( $htmlmail[$user] )? true : false );
    $padding = ( ! empty ( $htmlmail[$user] )? '&nbsp;&nbsp;&nbsp;' : '   ' );
    if ( $is_task == true ) {
      $body = translate( 'This is a reminder for the task detailed below.', true). "\n\n";
    } else {
      $body = translate( 'This is a reminder for the event detailed below.', true). "\n\n";
    }    
    $event_time = date_to_epoch ( $row[1] . $row[2] );
    $create_by = $row[0];
    $name = $row[9];
    $description = $row[10];

    // add trailing '/' if not found in server_url
    //Don't include link foe External users
    if ( ! empty ( $SERVER_URL ) && ! $isExt ) {
      if ( substr ( $SERVER_URL, -1, 1 ) == '/' ) {
        $eventURL = $SERVER_URL . 'view_entry.php?id=' . $id . '&em=1';
      } else {
        $eventURL = $SERVER_URL . '/view_entry.php?id=' . $id . '&em=1';
      }
      if ( $useHtml == true ) {
        $eventURL =  activate_urls ( $eventURL ); 
      }
      $body .= $eventURL . "\n\n";
    }
    $body .= strtoupper ( $name ) . "\n\n" .
        translate( 'Description', true) . ":\n" . $padding .  $description  . "\n" . 
         ( $is_task == false ? translate( 'Date', true): translate( 'Start Date', true) ).
         ': ' . date_to_str ( date ( 'Ymd', $event_date ) ) . "\n";

    if ( $row[2] >= 0 ) {
      $body .= ( $is_task == false ? translate ( 'Time', true ):
        translate ( 'Start Time', true ) ) . ': ' . 
        display_time ( '', $display_tzid, $event_time, $userTformat ) . "\n";
    }
    
    if ( $row[5] > 0 && $is_task == false ) {
      $body .= translate ( 'Duration', true ). ': ' .
        $row[5] .  " " . translate( 'minutes', true ) . "\n";
    } else if ( $is_task == true ) {
      $body .= translate ( 'Due Date', true ). ': ' .
        date_to_str ($row[11] ) . "\n";  
      $body .= translate ( 'Due Time', true ). ': ' .
        display_time ($row[12], $display_tzid, '', $userTformat ) . "\n";    
   }
   
   if (  $is_task == true && isset ( $percentage[$user] ) ) {
      $body .= translate ( 'Pecentage Complete', true ). ': ' .$percentage[$user]  . "%\n";   
   }  

    if ( empty ( $DISABLE_PRIORITY_FIELD ) || $DISABLE_PRIORITY_FIELD != 'Y' ) {
      $body .= translate ( 'Priority', true ). ': ' . $pri[$row[6]]  . "\n";
    }

    if ( empty ( $DISABLE_ACCESS_FIELD ) || $DISABLE_ACCESS_FIELD != 'Y' ) {
      $body .= translate ( 'Access', true ) . ': ';
      if ( $row[8] == 'P' ) {
        $body .= translate( 'Public', true) . "\n"; 
      } else if ( $row[8] == 'C' ) {
        $body .= translate( 'Confidential', true) . "\n";
      } else if ( $row[8] == 'R' ) {
        $body .= translate( 'Private', true) . "\n";
      }
    }

    if ( ! empty ( $single_user_login ) && $single_user_login == false ) {
      $body .= translate ( 'Created by', true ) . ': ' . $row[0]   . "\n";
    }

    $body .= translate ( 'Updated', true ). ': ' .
      date_to_str ( $row[3] ) . " " .  display_time ( $row[3] . $row[4],
        $display_tzid, '', $userTformat ) ."\n";

    // site extra fields
    $extras = get_site_extra_fields ( $id );
    $site_extracnt = count ( $site_extras );
    for ( $i = 0; $i < $site_extracnt; $i++ ) {
      $extra_name = $site_extras[$i][0];
      $extra_descr = $site_extras[$i][1];
      $extra_type = $site_extras[$i][2];
      if ( ! empty (  $extras[$extra_name]['cal_name'] ) && 
      $extras[$extra_name]['cal_name'] != "" ) {
        $val = '';
        $prompt = $extra_descr;
        if ( $extra_type == EXTRA_DATE ) {
          $body .= $prompt . ': ' . $extras[$extra_name]['cal_date'] . "\n";
        } else if ( $extra_type == EXTRA_MULTILINETEXT ) {
          $body .= $prompt . "\n" . $padding . $extras[$extra_name]['cal_data'] ."\n";
        } else {
          // default method for EXTRA_URL, EXTRA_TEXT, etc...
          $body .= $prompt . ': ' . $extras[$extra_name]['cal_data'] . "\n";
        }
      }
    }
    if ( ( empty ( $single_user ) || $single_user != 'Y' ) &&
      ( empty ( $DISABLE_PARTICIPANTS_FIELD ) ||
      $DISABLE_PARTICIPANTS_FIELD != 'N' ) ) {
        $body .= translate( 'Participants', true) . ":\n";

      for ( $i = 0; $i < $partcnt; $i++ ) {
        $body .= $padding . $names[$participants[$i]] . "\n";
      }
      for ( $i = 0; $i < $ext_partcnt; $i++ ) {
        $body .= $padding . $ext_participants[$i]  . ' (' .
            translate( 'External User', true ) . ")\n";
      }
    }

    $subject = translate( 'Reminder', true) . ': ' . stripslashes ( $name );


    if ( $debug )
      echo "Sending mail to $recip (in $userlang)\n";
    if ( $only_testing ) {
      if ( $debug )
        echo "<hr /><pre>To: $recip\nSubject: $subject\nFrom:". 
          translate ( 'Administrator', true ). "\n\n$body\n\n</pre>\n";
    } else {
      $mail = new WebCalMailer;
      loadUserVariables ( $user, "temp" );
      if ( strlen ( $GLOBALS['EMAIL_FALLBACK_FROM'] ) ) {
        $mail->From = $GLOBALS['EMAIL_FALLBACK_FROM'];
        $mail->FromName = translate ( 'Administrator', true);
      } else {
        $mail->From = translate ( 'Administrator', true );
      }
      $mail->IsHTML( $useHtml );
      $recipName = ( $isExt ? $user : $GLOBALS ['tempfullname'] );
      $mail->AddAddress( $recip, $recipName );
      $mail->Subject = $subject;
      if ( $isExt ) //send ics attachment to External Users
        $mail->IcsAttach ( $id ) ;
      $mail->Body  = ( $useHtml == true ? nl2br ( $body ) : $body );
      $mail->Send();
      $mail->ClearAll();

      $cal_text = ( $isExt ? translate( 'External User', true ) : '' );
      activity_log ( $id, 'system', $user, LOG_REMINDER, $cal_text );
    }
  }
}


// keep track of the fact that we send the reminder, so we don't
// do it again.
function log_reminder ( $id, $times_sent ) {
  global $only_testing;

  if ( ! $only_testing ) {
    dbi_execute ( "UPDATE webcal_reminders " .
      "SET cal_last_sent = ?, cal_times_sent = ? " .
      "WHERE cal_id = ?", array ( gmmktime(), $times_sent, $id ) );
  }
}


// Process an event for a single day.  Check to see if it has
// a reminder, when it needs to be sent and when the last time it
// was sent.
function process_event ( $id, $name, $start, $end, $new_date='' ) {
  global $debug, $only_testing, $is_task;
  
  $reminder = array();
  
    //get reminders array
    $reminder = getReminders ( $id );

    if ( ! empty ( $reminder ) ) {
      if ( $debug )
        echo "  Reminder set for event. <br />\n";
      $times_sent = $reminder['times_sent'];
      $repeats = $reminder['repeats'];
      $lastsent = $reminder['last_sent'];
      $related = $reminder['related'];
      //if we are working with a repeat or overdue task, and we have sent all the 
      //reminders for the basic event, then reset the counter to 0
      if ( ! empty ( $new_date ) ) {
        if ( $times_sent == $repeats + 1 ) {
          if ( $is_task == false ) {
            $times_sent = 0;
          } else if ( $related == 'E' && $new_date != gmdate('Ymd', $end ) ) { //tasks only
            $times_sent = 0;
          }
        }
        $new_offset = date_to_epoch ( $new_date ) - ( $start - ( $start % ONE_DAY) );
        $start += $new_offset;
        $end += $new_offset;
      }
      
      
      if ( $debug )
        printf ( "Event %d: \"%s\" on %s at %s GMT<br />\n",
          $id, $name, gmdate( 'Ymd', $start ), gmdate( 'H:i:s', $start ) );
      
      
      //it is pointless to send reminders after this time!
      $pointless = $end;
      if ( ! empty ( $reminder['date'] ) ) {  //we're using a date
        $remind_time =  $reminder['timestamp'];
      } else { // we're using offsets
        $offset =  $reminder['offset'] * 60; //convert to seconds
        if ( $related == 'S' ) { //relative to start
          $offset_msg = ( $reminder['before'] == 'Y'? 
            '  Mins Before Start: ': '  Mins After Start: ' ) . $reminder['offset'];
          $remind_time = ( $reminder['before'] == 'Y'? $start - $offset : $start + $offset );
        } else { //relative to end/due
          $offset_msg = ( $reminder['before'] == 'Y'? 
            '  Mins Before End: ': '  Mins After End:' ) . $reminder['offset'];
          $remind_time = ( $reminder['before'] == 'Y'? $end - $offset : $end + $offset );
          $pointless = ( $reminder['before'] == 'Y'? $end : $end + $offset );       
        }
      }
      //factor in repeats if set
      if ( $repeats > 0 && $times_sent <= $repeats ) {
        $remind_time += ( $reminder['duration'] * 60 * $times_sent );
      }

      if ( $debug ) {
        if ( ! empty ( $offset_msg ) ) {
          echo  $offset_msg . "<br />\n";
        }
        if ( $related == 'S' ) { //relative to start
          echo '  Event  start time is: ' . gmdate ( 'm/d/Y H:i', $start ) . " GMT<br />\n";
        } else {
          echo '  Event end time is: ' . gmdate ( 'm/d/Y H:i', $end ) . " GMT<br />\n";        
        }
        echo '  Remind time is: ' . gmdate ( 'm/d/Y H:i', $remind_time ) . " GMT<br />\n";
        echo 'Server Timezone: ' . $GLOBALS['SERVER_TIMEZONE'] . 
          "<br />\nServer Difference from GMT (hours) : " .
          date ('Z', $start ) / ONE_HOUR . "<br />\n";    
        echo 'Effective delivery time is: ' . 
          date ( 'm/d/Y H:i T', $remind_time ) . "<br />\n";
      }

      if ( $debug )
        echo '  Last sent on: ' .  
        ( $lastsent == 0 ? 'NEVER' : date ( 'm/d/Y H:i T', 
        $lastsent ) ). "<br /><br />\n";

      //no sense sending reminders if the event is over!
      //unless the entry is a task
      if ( $times_sent <  ( $repeats + 1 ) && 
        gmmktime() >= $remind_time &&
        $lastsent <= $remind_time &&  
        ( gmmktime() <= $pointless || $is_task == true ) ) {
        // Send a reminder
        if ( $debug )
          echo "  SENDING REMINDER! <br />\n";
        send_reminder ( $id, $start );
        // now update the db...
        if ( $debug )
          echo "<br />  LOGGING REMINDER! <br /><br />\n";
        log_reminder ( $id, $times_sent + 1 );
      }
    }
} //end function process_event

?>
