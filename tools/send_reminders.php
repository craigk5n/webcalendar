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
 * This script should be setup to run periodically on your system. You could run
 * it once every minute, but every 5-15 minutes should be sufficient.
 *
 * To set this up in cron, add a line like the following in your crontab
 * to run it every 10 minutes:
 *   1,11,21,31,41,51 * * * * php /some/path/here/send_reminders.php
 * Of course, change the path to where this script lives. If the php binary is
 * not in your $PATH, you may also need to provide the full path to "php".
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

// How many days in advance can a reminder be sent (max)?
// This will affect performance, but keep in mind that someone may enter
// a reminder to be sent 60 days in advance or they may enter a specific
// date for a reminder to be sent that is more than 30 days before the
// event's date. If you're only running this once an hour or less often,
// then you could certainly change this to look a whole 365 days ahead.
$DAYS_IN_ADVANCE = 30;
// $DAYS_IN_ADVANCE = 365;

// If you have moved this script out of the WebCalendar directory, which you
// probably should do since it would be better for security reasons, you would
// need to change _WC_BASE_DIR to point to the webcalendar include directory.

// _WC_BASE_DIR points to the base WebCalendar directory relative to
// current working directory

define ( '_WC_BASE_DIR', '..' );
define ( '_WC_INCLUDE_DIR', _WC_BASE_DIR . '/includes/' );

// Load include files.
$old_path = ini_get ( 'include_path' );
$delim = ( strstr ( $old_path, ';' ) ? ';' : ':' );
ini_set ( 'include_path', $old_path . $delim . _WC_INCLUDE_DIR . $delim );

require_once _WC_INCLUDE_DIR . 'classes/WebCalendar.class.php';
require_once _WC_INCLUDE_DIR . 'classes/Event.class.php';
require_once _WC_INCLUDE_DIR . 'classes/RptEvent.class.php';
require_once _WC_INCLUDE_DIR . 'classes/WebCalMailer.class.php';

$WC =& new WebCalendar ( __FILE__ );

include _WC_INCLUDE_DIR . 'translate.php';
include _WC_INCLUDE_DIR . 'config.php';
include _WC_INCLUDE_DIR . 'dbi4php.php';
include _WC_INCLUDE_DIR . 'functions.php';

$WC->initializeFirstPhase ();

include _WC_INCLUDE_DIR . 'site_extras.php';

$WC->initializeSecondPhase ();

$debug = false;// Set to true to print debug info...
$only_testing = false; // Just pretend to send -- for debugging.

// Establish a database connection.
$c = dbi_connect ( _WC_DB_HOST, _WC_DB_LOGIN, 
  _WC_DB_PASSWORD, _WC_DB_DATABASE, true );
if ( ! $c ) {
  echo translate ( 'Error connecting to database' ) . ': ' . dbi_error ();
  exit;
}


$WC->setLanguage ();

$WC->setToday ();

if ( $debug )
  echo '<br />Include Path=' . ini_get ( 'include_path' ) . "<br />\n";

// Get a list of the email users in the system.
// They must also have an email address. Otherwise, we can't
// send them mail, so what's the point?
$allusers = $WC->User->getUsers ();
$allusercnt = count ( $allusers );
for ( $i = 0; $i < $allusercnt; $i++ ) {
  $names[$allusers[$i]['cal_login']] = $allusers[$i]['cal_fullname'];
  $emails[$allusers[$i]['cal_login']] = $allusers[$i]['cal_email'];
}

$htmlmail = $languages = $noemail = $t_format = $tz = array ();
$default_language = getPref ( 'LANGUAGE', 2 );
if ( $default_language == 'Browser-defined' )
  $default_language = 'English-US';
$res = dbi_execute ( 'SELECT cal_login_id, cal_value, cal_setting
  FROM webcal_user_pref
  WHERE ( cal_setting = \'EMAIL_HTML\' AND cal_value = \'Y\' )
  OR ( cal_setting = \'EMAIL_REMINDER\' AND cal_value = \'N\' )
  OR cal_setting = \'LANGUAGE\'
  OR cal_setting = \'TIME_FORMAT\'
  OR cal_setting = \'TIMEZONE\'
  ORDER BY cal_login_id, cal_setting' );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $user = $row[0];

    switch ( $row[2] ) {
      case 'EMAIL_HTML':
        // Users who have asked for HTML (default is plain text).
        $htmlmail[$user] = true;
        if ( $debug )
          echo "User $user wants HTML mail.<br />\n";
        break;
      case 'EMAIL_REMINDER':
        // Users who have asked not to receive email.
        $noemail[$user] = 1;
        if ( $debug )
          echo "User $user does not want email.<br />\n";
        break;
      case 'LANGUAGE':
        // Users language preference.
        $languages[$user] = $row[1];
        if ( $debug )
          echo "Language for $user is $row[1].<br />\n";
        break;
      case 'TIME_FORMAT':
        // Users time format settings.
        $t_format[$user] = $row[1];
        if ( $debug )
          echo "Time Format for $user is $row[1].<br />\n";
        break;
      case 'TIMEZONE':
        // Users TIMEZONE settings.
        $tz[$user] = $row[1];
        if ( $debug )
          echo "TIMEZONE for $user is $row[1].<br />\n";
        break;
    } // switch
  }
  dbi_free_result ( $res );
}

if ( ! getPref ( 'GENERAL_USE_GMT' ) )
  $def_tz = $_SERVER_TIMEZONE;

$startdateTS = time ( 0, 0, 0 );
$enddateTS = $startdateTS + ( $DAYS_IN_ADVANCE * ONE_DAY );

$startdate = gmdate ( 'Ymd', $startdateTS );
$enddate = gmdate ( 'Ymd', $enddateTS );

// Now read events all the repeating events (for all users).
$repeated_events = query_events ( '', true,
  'AND ( wer.cal_end >= ' . $startdate
   . ' OR wer.cal_end IS NULL ) ' );
$repcnt = count ( $repeated_events );
// Read non-repeating events (for all users).
if ( $debug )
  echo "Checking for events from date $startdate to date $enddate.<br />\n";
$events = read_events ( '', $startdateTS, $enddateTS );
$eventcnt = count ( $events );
if ( $debug )
  echo "Checking for tasks from date $startdate to date $enddate.<br />\n";
$tasks = read_tasks ( '', $enddateTS );
$taskcnt = count ( $tasks );
if ( $debug )
  echo 'Found ' . 0 + $eventcnt + $taskcnt + $repcnt
   . " events in time range.<br />\n";

$is_task = false;
for ( $d = 0; $d < $DAYS_IN_ADVANCE; $d++ ) {
  $date = gmdate ( 'Ymd', time () + ( $d * ONE_DAY ) );
  // echo "Date: $date\n";
  // Get non-repeating events for this date.
  // An event will be included one time for each participant.
  $ev = get_entries ( $date );
  // Keep track of duplicates.
  $completed_ids = array ();
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    $eid = $ev[$i]->getId ();
    if ( ! empty ( $completed_ids[$eid] ) )
      continue;
    $completed_ids[$eid] = 1;
    process_event ( $eid, $ev[$i]->getName (), $ev[$i]->getDate (),
      $ev[$i]->getEndDate () );
  }
  // Get tasks for this date.
  // A task will be included one time for each participant.
  $tks = get_tasks ( $date );
  // Keep track of duplicates.
  $completed_ids = array ();
  $tkscnt = count ( $tks );
  for ( $i = 0; $i < $tkscnt; $i++ ) {
    $eid = $tks[$i]->getId ();
    if ( ! empty ( $completed_ids[$eid] ) )
      continue;
    $completed_ids[$eid] = 1;
    $is_task = true;
    process_event ( $eid, $tks[$i]->getName (), $tks[$i]->getDate (),
      $tks[$i]->getDueDate (), $date );
  }
  $is_task = false;
  // Get repeating events...tasks are not included at this time.
  $rep = get_repeating_entries ( '', $date );
  $repcnt = count ( $rep );
  for ( $i = 0; $i < $repcnt; $i++ ) {
    $eid = $rep[$i]->getId ();
    if ( ! empty ( $completed_ids[$eid] ) )
      continue;
    $completed_ids[$eid] = 1;
    process_event ( $eid, $rep[$i]->getName (), $rep[$i]->getDate (),
      $rep[$i]->getEndDate (), $date );
  }
}

if ( $debug )
  echo "Done.<br />\n";


// Send a reminder for a single event for a single day to all participants in
// the event who have accepted as well as those who have not yet approved.
// But, don't send to users who rejected (cal_status='R' ).
function send_reminder ( $eid, $event_date ) {
  global $debug, $def_tz, $emails, $htmlmail, $is_task, 
  $languages, $names,
  $only_testing, $site_extras, $t_format, $tz;

  $ext_participants = $participants = array ();
  $num_ext_participants = $num_participants = 0;
  $server_url = getPref ( 'SERVER_URL', 2 );
  $pri[1] = translate ( 'High' );
  $pri[2] = translate ( 'Medium' );
  $pri[3] = translate ( 'Low' );

  // get participants first...
  $res = dbi_execute ( 'SELECT cal_login_id, cal_percent FROM webcal_entry_user
    WHERE cal_id = ? AND cal_status IN ( \'A\',\'W\' ) ORDER BY cal_login_id',
    array ( $eid ) );

  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$num_participants++] = $row[0];
      $percentage[$row[0]] = $row[1];
    }
  }
  $partcnt = count ( $participants );
  // Get external participants.
  if ( getPref ( '_ALLOW_EXTERNAL_USERS' ) && getPref ( '_EXTERNAL_REMINDERS' ) ) {
    $res = dbi_execute ( 'SELECT cal_fullname, cal_email
      FROM webcal_entry_ext_user WHERE cal_id = ? AND cal_email IS NOT NULL
      ORDER BY cal_fullname', array ( $eid ) );
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
      echo 'No participants found for event id' . ": $eid<br />\n";
    return;
  }


  // Get event details.
  $res = dbi_execute ( 'SELECT cal_create_by, cal_date, cal_mod_date,
    cal_duration, cal_priority, cal_type, cal_access, cal_name,
    cal_description, cal_due_date FROM webcal_entry
    WHERE cal_id = ?', array ( $eid ) );
  if ( $res ) {
    if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
      echo translate ( 'Error' ) . ': ' . str_replace ( 'XXX', $eid,
      translate ( 'could not find event id XXX in database.' ) ) . "\n";
      return;
    }
  }

  // send mail. We send one user at a time so that we can switch
  // languages between users if needed (as well as HTML vs plain text).
  $mailusers = $recipients = array ();
  if ( _WC_SINGLE_USER ) {
    $mailusers[] = $emails[_WC_SINGLE_USER_LOGIN];
    $recipients[] = _WC_SINGLE_USER_LOGIN;
  } else {
    for ( $i = 0; $i < $partcnt; $i++ ) {
      if ( strlen ( $emails[$participants[$i]] ) ) {
        $mailusers[] = $emails[$participants[$i]];
        $recipients[] = $participants[$i];
      } else {
        if ( $debug )
          echo "No email for user $participants[$i].<br />\n";
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
    $userlang = ( ! empty ( $languages[$user] )
      ? $languages[$user]
      : $default_language ); // system default
    $userTformat = ( ! empty ( $t_format[$user] )
      ? $t_format[$user]
      : 24 ); // gotta pick something
    if ( $debug )
      echo "Setting language to \"$userlang\".<br />\n";
    reset_language ( $userlang );
    $adminStr = translate ( 'Administrator' );
    // reset timezone setting for current user
    if ( ! empty ( $tz[$user] ) ) {
      $display_tzid = 2; // display TZ
      $user_TIMEZONE = $tz[$user];
    } else
    if ( ! empty ( $def_tz ) ) {
      $display_tzid = 2;
      $user_TIMEZONE = $def_tz;
    } else {
      $display_tzid = 3; // Do not use offset & display TZ
      // I think this is the only real timezone set to UTC...since 1972 at least
      $user_TIMEZONE = 'Africa/Monrovia';
    }
    // this will allow date functions to use the proper TIMEZONE
    set_env ( 'TZ', $user_TIMEZONE );

    $useHtml = ( ! empty ( $htmlmail[$user] ) ? 'Y' : 'N' );
    $padding = ( ! empty ( $htmlmail[$user] ) ? '&nbsp;&nbsp;&nbsp;' : '   ' );
    $body = str_replace ( 'XXX',
      ( $is_task == true ? translate ( 'task' ) : translate ( 'event' ) ),
      translate ( 'This is a reminder for the XXX detailed below.') )
     . "\n\n";

    $create_by = $row[0];
    $event_time = $row[1];
    $name = $row[9];
    $description = $row[10];

    // add trailing '/' if not found in server_url
    // Don't include link foe External users
    if ( ! empty ( $server_url ) && ! $isExt ) {
      $eventURL = $server_url
       . ( substr ( $server_url, -1, 1 ) == '/' ? '' : '/' )
       . 'view_entry.php?eid=' . $eid . '&em=1';

      if ( $useHtml == 'Y' )
        $eventURL = activate_urls ( $eventURL );

      $body .= $eventURL . "\n\n";
    }
    $body .= strtoupper ( $name ) . "\n\n" . translate ( 'Description' )
     . ":\n" . $padding . $description . "\n" . ( $is_task == false
      ? translate ( 'Date' )
      : translate ( 'Start Date' ) )
     . ': ' . date_to_str ( date ( 'Ymd', $event_date ) ) . "\n"
     . ( $row[2] >= 0
      ? ( $is_task == false
        ? translate ( 'Time')
        : translate ( 'Start Time' ) )
       . ': ' . smarty_modifier_display_time ( '', $display_tzid, $event_time, $userTformat )
       . "\n"
      : '' )
     . ( $row[5] > 0 && $is_task == false
      ? translate ( 'Duration' ) . ': ' . $row[5] . " "
       . translate ( 'minutes' ) . "\n"
      : ( $is_task == true ? translate ( 'Due Date') . ': '
         . date_to_str ( $row[11] ) . "\n" . translate ( 'Due Time' )
         . ': ' . smarty_modifier_display_time ( $row[12], $display_tzid, '', $userTformat )
         . "\n" : '' ) )
     . ( $is_task == true && isset ( $percentage[$user] )
      ? translate ( 'Pecentage Complete' ) . ': ' . $percentage[$user]
       . "%\n"
      : '' )
     . ( getPref ( '_ENABLE_PRIORITY_FIELD' )
      ? translate ( 'Priority' ) . ': ' . $row[6] . '-'
      . $pri[ceil($row[6]/3 )]  . "\n" : '' );

    if ( getPref ( '_ENABLE_ACCESS_FIELD' ) ) {
      $body .= translate ( 'Access' ) . ': ';
      if ( $row[8] == 'C' )
        $body .= translate ( 'Confidential' ) . "\n";
      elseif ( $row[8] == 'P' )
        $body .= translate ( 'Public' ) . "\n";
      elseif ( $row[8] == 'R' )
        $body .= translate ( 'Private' ) . "\n";
    }

    $body .= ( ! _WC_SINGLE_USER 
      ? translate ( 'Created by' ) . ': ' . $row[0] . "\n" : '' )
     . translate ( 'Updated' ) . ': ' . date_to_str ( $row[3] ) . ' '
     . smarty_modifier_display_time ( $row[3] , $display_tzid, '',
      $userTformat ) . "\n";

    // site extra fields
    $extras = get_site_extra_fields ( $eid );
    $site_extracnt = count ( $site_extras );
    for ( $i = 0; $i < $site_extracnt; $i++ ) {
      if ( $site_extras[$i] == 'FIELDSET' ) continue;
      $extra_name = $site_extras[$i][0];
      $extra_descr = $site_extras[$i][1];
      $extra_type = $site_extras[$i][2];
      $extra_arg1 = $site_extras[$i][3];
      $extra_arg2 = $site_extras[$i][4];
      if ( ! empty ( $site_extras[$i][5] ) )
        $extra_view = $site_extras[$i][5] & EXTRA_DISPLAY_REMINDER;
      if ( ! empty ( $extras[$extra_name]['cal_name'] ) &&
          $extras[$extra_name]['cal_name'] != '' && ! empty ( $extra_view ) ) {
        $val = '';
        $body .= $extra_descr;
        if ( $extra_type == EXTRA_DATE )
          $body .= ': ' . $extras[$extra_name]['cal_date'] . "\n";
        elseif ( $extra_type == EXTRA_MULTILINETEXT )
          $body .= "\n" . $padding . $extras[$extra_name]['cal_data'] . "\n";
        elseif ( $extra_type == EXTRA_RADIO  )
          $body.= ': ' . $extra_arg1[$extras[$extra_name]['cal_data']] . "\n";
        else
          // default method for EXTRA_URL, EXTRA_TEXT, etc...
          $body .= ': ' . $extras[$extra_name]['cal_data'] . "\n";
      }
    }
    if ( ! _WC_SINGLE_USER && getPref ( '_ENABLE_PARTICIPANTS_FIELD' ) ) {
      $body .= translate ( 'Participants') . ":\n";

      for ( $i = 0; $i < $partcnt; $i++ ) {
        $body .= $padding . $names[$participants[$i]] . "\n";
      }
      for ( $i = 0; $i < $ext_partcnt; $i++ ) {
        $body .= $padding . $ext_participants[$i] . ' ( '
         . translate ( 'External User' ) . ")\n";
      }
    }

    $subject = translate ( 'Reminder' ) . ': ' . stripslashes ( $name );

    if ( $debug )
      echo "Sending mail to $recip (in $userlang).<br />\n";
    if ( $only_testing ) {
      if ( $debug )
        echo '<hr />
<pre>
To: ' . $recip . '
Subject: ' . $subject . '
From:' . $adminStr . '

' . $body . '

</pre>
';
    } else {
      $mail = new WebCalMailer;
      $WC->User->loadVariables ( $user, 'temp' );
      $recipName = ( $isExt ? $user : $GLOBALS ['tempfullname'] );
      // send ics attachment to External Users
      $attach = ( $isExt ? $eid : '' );
      $mail->WC_Send ( $adminStr, $recip, $recipName, $subject,
        $body, $useHtml, $GLOBALS['_EMAIL_FALLBACK_FROM'], $attach  );
      $cal_text = ( $isExt ? translate ( 'External User' ) : '' );
      activity_log ( $eid, 'system', $user, LOG_REMINDER, $cal_text );
    }
  }
}


// Keep track of the fact that we send the reminder, so we don't do it again.
function log_reminder ( $eid, $times_sent ) {
  global $only_testing;

  if ( ! $only_testing )
    dbi_execute ( 'UPDATE webcal_reminders
      SET cal_last_sent = ?, cal_times_sent = ? WHERE cal_id = ?',
      array ( time (), $times_sent, $eid ) );
}


// Process an event for a single day. Check to see if it has a reminder, when it
// needs to be sent and when the last time it was sent.
function process_event ( $eid, $name, $start, $end, $new_date = '' ) {
  global $debug, $is_task, $only_testing;

  $reminder = array ();

  // get reminders array
  $reminder = getReminders ( $eid );

  if ( ! empty ( $reminder ) ) {
    if ( $debug )
      echo "  Reminder set for event.<br />\n";
    $times_sent = $reminder['times_sent'];
    $repeats = $reminder['repeats'];
    $lastsent = $reminder['last_sent'];
    $related = $reminder['related'];
    // If we are working with a repeat or overdue task, and we have sent all the
    // reminders for the basic event, then reset the counter to 0.
    if ( ! empty ( $new_date ) ) {
      if ( $times_sent == $repeats + 1 ) {
        if ( $is_task == false ||
          ( $related == 'E' && $new_date != gmdate ( 'Ymd', $end ) ) ) // tasks only
          $times_sent = 0;
      }
      $new_offset = date_to_epoch ( $new_date ) - ( $start - ( $start % ONE_DAY ) );
      $start += $new_offset;
      $end += $new_offset;
    }

    if ( $debug )
      printf ( "Event %d: \"%s\" on %s at %s GMT<br />\n",
        $eid, $name, gmdate ( 'Ymd', $start ), gmdate ( 'H:i:s', $start ) );


    // It is pointless to send reminders after this time!
    $pointless = $end;
    if ( ! empty ( $reminder['date'] ) ) // We're using a date.
      $remind_time = $reminder['timestamp'];
    else { // We're using offsets.
      $offset = $reminder['offset'] * 60; // Convert to seconds.
      if ( $related == 'S' ) { // Relative to start.
        $offset_msg = ( $reminder['before'] == 'Y'
          ? '  Mins Before Start: ' : '  Mins After Start: ' )
         . $reminder['offset'];
        $remind_time = ( $reminder['before'] == 'Y'
          ? $start - $offset : $start + $offset );
      } else { // Relative to end/due.
        $offset_msg = ( $reminder['before'] == 'Y'
          ? '  Mins Before End: ': '  Mins After End:' ) . $reminder['offset'];
        $remind_time = ( $reminder['before'] == 'Y'
          ? $end - $offset : $end + $offset );
        $pointless = ( $reminder['before'] == 'Y' ? $end : $end + $offset );
      }
    }
    // Factor in repeats if set.
    if ( $repeats > 0 && $times_sent <= $repeats )
      $remind_time += ( $reminder['duration'] * 60 * $times_sent );

    if ( $debug )
      echo ( ! empty ( $offset_msg ) ? $offset_msg . '<br />' : '' ) . '
  Event ' . ( $related == 'S' // relative to start
        ? 'start time is: ' . gmdate ( 'm/d/Y H:i', $start )
        : 'end time is: ' . gmdate ( 'm/d/Y H:i', $end ) ) . ' GMT<br />
  Remind time is: ' . gmdate ( 'm/d/Y H:i', $remind_time ) . ' GMT<br />
  Effective delivery time is: ' . date ( 'm/d/Y H:i T', $remind_time ) . '<br />
  Last sent on: '
       . ( $lastsent == 0 ? 'NEVER' : date ( 'm/d/Y H:i T', $lastsent ) )
       . "<br /><br />\n";
    // No sense sending reminders if the event is over!
    // Unless the entry is a task.
    if ( $times_sent < ( $repeats + 1 ) &&
        time () >= $remind_time && $lastsent <= $remind_time &&
        ( time () <= $pointless || $is_task == true ) ) {
      // Send a reminder
      if ( $debug )
        echo "  SENDING REMINDER!<br />\n";
      send_reminder ( $eid, $start );
      // now update the db...
      if ( $debug )
        echo "<br /> LOGGING REMINDER!<br /><br />\n";
      log_reminder ( $eid, $times_sent + 1 );
    }
  }
} //end function process_event

?>
