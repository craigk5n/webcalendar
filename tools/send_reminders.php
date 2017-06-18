#!/usr/local/bin/php -q
<?php
/* $Id: send_reminders.php,v 1.81.2.11 2011/04/27 00:27:35 rjones6061 Exp $
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

// Load include files.
// If you have moved this script out of the WebCalendar directory, which you
// probably should do since it would be better for security reasons, you would
// need to change __WC_INCLUDEDIR to point to the webcalendar include directory.
define ( '__WC_BASEDIR', '..' ); // Points to the base WebCalendar directory
                          // relative to current working directory.
define ( '__WC_INCLUDEDIR', '../includes' );
$old_path = ini_get ( 'include_path' );
$delim = ( strstr ( $old_path, ';' ) ? ';' : ':' );
ini_set ( 'include_path', $old_path . $delim . __WC_INCLUDEDIR . $delim );

require_once __WC_INCLUDEDIR . '/classes/WebCalendar.class';
require_once __WC_INCLUDEDIR . '/classes/Event.class';
require_once __WC_INCLUDEDIR . '/classes/RptEvent.class';
require_once __WC_INCLUDEDIR . '/classes/WebCalMailer.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include __WC_INCLUDEDIR . '/translate.php';
include __WC_INCLUDEDIR . '/config.php';
include __WC_INCLUDEDIR . '/dbi4php.php';
include __WC_INCLUDEDIR . '/formvars.php';
include __WC_INCLUDEDIR . '/functions.php';

$WebCalendar->initializeFirstPhase ();

include __WC_INCLUDEDIR . '/' .$user_inc;
include __WC_INCLUDEDIR . '/site_extras.php';

$WebCalendar->initializeSecondPhase ();

$debug = false;// Set to true to print debug info...
$only_testing = false; // Just pretend to send -- for debugging.

// Establish a database connection.
$c = dbi_connect ( $db_host, $db_login, $db_password, $db_database, true );
if ( ! $c ) {
  echo translate ( 'Error connecting to database' ) . ': ' . dbi_error ();
  exit;
}

load_global_settings ();

$WebCalendar->setLanguage ();

set_today ();

if ( $debug )
  echo '<br />Include Path=' . ini_get ( 'include_path' ) . "<br />\n";

// Get a list of the email users in the system.
// They must also have an email address.
// Otherwise, we can't send them mail, so what's the point?
$allusers = user_get_users ();
$allusercnt = count ( $allusers );
for ( $i = 0; $i < $allusercnt; $i++ ) {
  $names[$allusers[$i]['cal_login']] = $allusers[$i]['cal_fullname'];
  $emails[$allusers[$i]['cal_login']] = $allusers[$i]['cal_email'];
}

$attachics = $htmlmail = $languages = $noemail = $t_format = $tz = array ();

$res = dbi_execute ( 'SELECT cal_login, cal_value, cal_setting
  FROM webcal_user_pref
  WHERE ( cal_setting = \'EMAIL_HTML\' AND cal_value = \'Y\' )
  OR ( cal_setting = \'EMAIL_REMINDER\' AND cal_value = \'N\' )
  OR ( cal_setting = \'EMAIL_REMINDER_ATTACH_ICS\' AND cal_value = \'Y\')
  OR cal_setting = \'LANGUAGE\'
  OR cal_setting = \'TIME_FORMAT\'
  OR cal_setting = \'TIMEZONE\'
  ORDER BY cal_login, cal_setting' );
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
      case 'EMAIL_REMINDER_ATTACH_ICS':
        // Users who have asked receive an ICS-attachment with their email.
        $attachics[$user] = 1;
        if ( $debug )
          echo "User $user does want ICS-attachment with email.<br />\n";
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

if ( empty ( $GENERAL_USE_GMT ) || $GENERAL_USE_GMT != 'Y' )
  $def_tz = $SERVER_TIMEZONE;

$startdateTS = time ( 0, 0, 0 );
$enddateTS = $startdateTS + ( $DAYS_IN_ADVANCE * 86400 );

$startdate = date ( 'Ymd', $startdateTS );
$enddate = date ( 'Ymd', $enddateTS );

// Now read all the repeating events (for all users).
$repeated_events = query_events ( '', true,
  'AND ( wer.cal_end >= ' . $startdate . ' OR wer.cal_end IS NULL ) ' );
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
  $dateTS = time () + ( $d * 86400 );
  $date = date ( 'Ymd', $dateTS );

  // Get non-repeating events for this date.
  // An event will be included one time for each participant.
  $ev = get_entries ( $date );

  // Keep track of duplicates.
  $completed_ids = array ();
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    $id = $ev[$i]->getID ();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;

    $completed_ids[$id] = 1;
    process_event ( $id, $ev[$i]->getName (), $ev[$i]->getDateTimeTS (),
      $ev[$i]->getEndDateTimeTS () );
  }
  // Get tasks for this date.
  // A task will be included one time for each participant.
  $tks = get_tasks ( $date );
  // Keep track of duplicates.
  $completed_ids = array ();
  $tkscnt = count ( $tks );
  for ( $i = 0; $i < $tkscnt; $i++ ) {
    $id = $tks[$i]->getID ();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;

    $completed_ids[$id] = 1;
    $is_task = true;
    process_event ( $id, $tks[$i]->getName (), $tks[$i]->getDateTimeTS (),
      $tks[$i]->getDueDateTimeTS (), $dateTS );
  }
  $is_task = false;
  // Get repeating events...tasks are not included at this time.
  if ( $debug )
    echo "Getting repeating events for $date<br />";
  $rep = my_get_repeating_entries ( '', $date );
  $repcnt = count ( $rep );
  if ( $debug )
    echo "found $repcnt repeating events for $date<br />";
  for ( $i = 0; $i < $repcnt; $i++ ) {
    $id = $rep[$i]->getID ();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;

    $completed_ids[$id] = 1;
    process_event ( $id, $rep[$i]->getName (), $rep[$i]->getDateTimeTS (),
      $rep[$i]->getEndDateTimeTS (), $date );
  }
}

if ( $debug )
  echo "Done.<br />\n";

// Send a reminder for a single event for a single day to all participants in
// the event who have accepted as well as those who have not yet approved.
// But, don't send to users who rejected (cal_status='R' ).
function send_reminder ( $id, $event_date ) {
  global $ALLOW_EXTERNAL_USERS, $debug, $def_tz, $emails,
  $EXTERNAL_REMINDERS, $attachics, $htmlmail, $is_task, $LANGUAGE, $languages, $names,
  $only_testing, $SERVER_URL, $site_extras, $t_format, $tz;

  $ext_participants = $participants = array ();
  $num_ext_participants = $num_participants = 0;

  $pri[1] = translate ( 'High' );
  $pri[2] = translate ( 'Medium' );
  $pri[3] = translate ( 'Low' );

  // Get participants first...
  $res = dbi_execute ( 'SELECT cal_login, cal_percent FROM webcal_entry_user
    WHERE cal_id = ? AND cal_status IN ( \'A\',\'W\' ) ORDER BY cal_login',
    array ( $id ) );

  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$num_participants++] = $row[0];
      $percentage[$row[0]] = $row[1];
    }
  }
  $partcnt = count ( $participants );
  // Get external participants.
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == 'Y' && !
      empty ( $EXTERNAL_REMINDERS ) && $EXTERNAL_REMINDERS == 'Y' ) {
    $res = dbi_execute ( 'SELECT cal_fullname, cal_email
      FROM webcal_entry_ext_user WHERE cal_id = ? AND cal_email IS NOT NULL
      ORDER BY cal_fullname', array ( $id ) );

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
      echo 'No participants found for event id' . ": $id<br />\n";
    return;
  }

  // Get event details.
  $res = dbi_execute ( 'SELECT cal_create_by, cal_date, cal_time, cal_mod_date,
    cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, cal_name,
    cal_description, cal_due_date, cal_due_time FROM webcal_entry
    WHERE cal_id = ?', array ( $id ) );
  if ( ! $res ) {
    echo translate ( 'Database error' ) . ': '
     . translate ( 'could not find event id' ) . " $id.\n";
    return;
  }

  if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
    echo translate ( 'Error' ) . ': ' . str_replace ( 'XXX', $id,
      translate ( 'could not find event id XXX in database.' ) ) . "\n";
    return;
  }

  // Send mail. We send one user at a time so that we can switch
  // languages between users if needed (as well as HTML vs plain text).
  $mailusers = $recipients = array ();
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
    echo 'Found ' . $mailusercnt . " with email addresses<br />\n";
  for ( $j = 0; $j < $mailusercnt; $j++ ) {
    $recip = $mailusers[$j];
    $user = $recipients[$j];
    $isExt = ( ! in_array ( $user, $participants ) );
    $userlang = ( empty ( $languages[$user] )
      ? $LANGUAGE // System default.
      : $languages[$user] );
    $userTformat = ( ! empty ( $t_format[$user] )
      ? $t_format[$user]
      : 24 ); // Gotta pick something.
    if ( $userlang == 'none' )
      $userlang = 'English-US'; // Gotta pick something.
    if ( $debug )
      echo "Setting language to \"$userlang\".<br />\n";

    reset_language ( $userlang );
    $adminStr = translate ( 'Administrator' );
    // Reset timezone setting for current user.
    if ( ! empty ( $tz[$user] ) ) {
      $display_tzid = 2; // Display TZ.
      $user_TIMEZONE = $tz[$user];
    } else
    if ( ! empty ( $def_tz ) ) {
      $display_tzid = 2;
      $user_TIMEZONE = $def_tz;
    } else {
      $display_tzid = 3; // Do not use offset & display TZ.
      // I think this is the only real timezone set to UTC...since 1972 at least.
      $user_TIMEZONE = 'Africa/Monrovia';
    }
    // This will allow date functions to use the proper TIMEZONE.
    set_env ( 'TZ', $user_TIMEZONE );

    $useHtml = ( ! empty ( $htmlmail[$user] ) ? 'Y' : 'N' );
    $padding = ( ! empty ( $htmlmail[$user] ) ? '&nbsp;&nbsp;&nbsp;' : '   ' );
    $body = str_replace ( 'XXX',
      ( $is_task ? translate ( 'task' ) : translate ( 'event' ) ),
      translate ( 'This is a reminder for the XXX detailed below.' ) ) . "\n\n";

    $create_by = $row[0];
    $event_time = date_to_epoch ( $row[1] . ( $row[2] != -1 ? sprintf ( "%06d", $row[2] ): '' ) );
    $name = $row[9];
    $description = $row[10];

    // Add trailing '/' if not found in server_url.
    // Don't include link for External users.
    if ( ! empty ( $SERVER_URL ) && ! $isExt ) {
      $eventURL = $SERVER_URL
       . ( substr ( $SERVER_URL, -1, 1 ) == '/' ? '' : '/' )
       . 'view_entry.php?id=' . $id . '&em=1';

      if ( $useHtml == 'Y' )
        $eventURL = activate_urls ( $eventURL );

      $body .= $eventURL . "\n\n";
    }
    $body .= strtoupper ( $name ) . "\n\n" . translate ( 'Description' )
     . ":\n" . $padding . $description . "\n"
     . ( $is_task ? translate ( 'Start Date' ) : translate ( 'Date' ) )
     . ': ' . date_to_str ( ( $row[2] > 0? date ( 'Ymd', $event_date ) : gmdate ( 'Ymd', $event_date ) ) ) . "\n"
     . ( $row[2] > 0 
      ? ( $is_task ? translate ( 'Start Time' ) : translate ( 'Time' ) ) . ': '
       . display_time ( '', $display_tzid, $event_time, $userTformat ) . "\n"
      : ( ( $row[2] == 0 &&  $row[5] = 1440) ? translate ( 'Time' ) . ': ' 
	  . translate ( 'All day event' ). "\n" : '' ) )
     . ( $row[5] > 0 && ! $is_task
      ? translate ( 'Duration' ) . ': ' . $row[5] . ' '
       . translate ( 'minutes' ) . "\n"
      : ( $is_task ? translate ( 'Due Date' ) . ': ' . date_to_str ( $row[11] )
       . "\n" . translate ( 'Due Time' ) . ': ' . display_time ( $row[12],
         $display_tzid, '', $userTformat ) . "\n" : '' ) )
     . ( $is_task && isset ( $percentage[$user] )
      ? translate ( 'Pecentage Complete' ) . ': ' . $percentage[$user] . "%\n" : '' )
     . ( empty ( $DISABLE_PRIORITY_FIELD ) || $DISABLE_PRIORITY_FIELD != 'Y'
      ? translate ( 'Priority' ) . ': ' . $row[6] . '-'
       . $pri[ceil( $row[6] / 3 )] . "\n" : '' );

    if ( empty ( $DISABLE_ACCESS_FIELD ) || $DISABLE_ACCESS_FIELD != 'Y' ) {
      $body .= translate ( 'Access' ) . ': ';
      if ( $row[8] == 'C' )
        $body .= translate ( 'Confidential' ) . "\n";
      elseif ( $row[8] == 'P' )
        $body .= translate ( 'Public' ) . "\n";
      elseif ( $row[8] == 'R' )
        $body .= translate ( 'Private' ) . "\n";
    }

    $body .= ( ! empty ( $single_user_login ) && ! $single_user_login
      ? translate ( 'Created by' ) . ': ' . $row[0] . "\n" : '' )
     . translate ( 'Updated' ) . ': ' . date_to_str ( $row[3] ) . ' '
     . display_time ( $row[3] . sprintf ( "%06d", $row[4] ), $display_tzid, '',
      $userTformat ) . "\n";

    // Site extra fields.
    $extras = get_site_extra_fields ( $id );
    $site_extracnt = count ( $site_extras );
    for ( $i = 0; $i < $site_extracnt; $i++ ) {
      if ( $site_extras[$i] == 'FIELDSET' )
        continue;

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
        elseif ( $extra_type == EXTRA_RADIO )
          $body .= ': ' . $extra_arg1[$extras[$extra_name]['cal_data']] . "\n";
        else
          // Default method for EXTRA_URL, EXTRA_TEXT, etc...
          $body .= ': ' . $extras[$extra_name]['cal_data'] . "\n";
      }
    }
    if ( ( empty ( $single_user ) || $single_user != 'Y' ) &&
        ( empty ( $DISABLE_PARTICIPANTS_FIELD ) ||
          $DISABLE_PARTICIPANTS_FIELD != 'N' ) ) {
      $body .= translate ( 'Participants' ) . ":\n";

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
      user_load_variables ( $user, 'temp' );
      $recipName = ( $isExt ? $user : $GLOBALS ['tempfullname'] );
      // Send ics attachment to External Users or
      // or users who explicitly chose to receive it.
      $attach = ( ($isExt || isset($attachics[$user])) ? $id : '' );
      $mail->WC_Send ( $adminStr, $recip, $recipName, $subject,
        $body, $useHtml, $GLOBALS['EMAIL_FALLBACK_FROM'], $attach );
      $cal_text = ( $isExt ? translate ( 'External User' ) : '' ) . $recipName;
      activity_log ( $id, 'system', $user, LOG_REMINDER, $cal_text );
    }
  }
}

// Keep track of the fact that we sent the reminder, so we don't do it again.
function log_reminder ( $id, $times_sent ) {
  global $debug, $only_testing;

  if ( ! $only_testing )
    dbi_execute ( 'UPDATE webcal_reminders
      SET cal_last_sent = ?, cal_times_sent = ? WHERE cal_id = ?',
      array ( time (), $times_sent, $id ) );
}

// Process an event for a single day. Check to see if it has a reminder,
// when it needs to be sent and when the last time it was sent.
function process_event ( $id, $name, $start, $end, $new_date = '' ) {
  global $debug, $is_task, $only_testing;

  // Get reminders array.
  $reminder = getReminders ( $id );

  if ( ! empty ( $reminder ) ) {
    if ( $debug )
      echo " Reminder set for event.<br />\n";

    $times_sent = $reminder['times_sent'];
    $repeats = $reminder['repeats'];
    $lastsent = $reminder['last_sent'];
    $related = $reminder['related'];
    // If we are working with a repeat or overdue task, and we have sent all the
    // reminders for the basic event, then reset the counter to 0.
    if ( ! empty ( $new_date ) ) {
      if ( $times_sent == $repeats + 1 ) {
        if ( ! $is_task ||
          ( $related == 'E' && date ( 'Ymd', $new_date ) 
            != date ( 'Ymd', $end ) ) ) // Tasks only.
          $times_sent = 0;
      }
      $new_offset = date_to_epoch ( $new_date ) - ( $start - ( $start % 86400 ) );
      $start += $new_offset;
      $end += $new_offset;
    }

    if ( $debug )
      printf ( "Event %d: \"%s\" on %s at %s GMT<br />\n",
        $id, $name, gmdate ( 'Ymd', $start ), gmdate ( 'H:i:s', $start ) );

    // It is pointless to send reminders after this time!
    $pointless = $end;
    $remB4 = ( $reminder['before'] == 'Y' );
    if ( ! empty ( $reminder['date'] ) ) // We're using a date.
      $remind_time = $reminder['timestamp'];
    else { // We're using offsets.
      $offset = $reminder['offset'] * 60; // Convert to seconds.
      if ( $related == 'S' ) { // Relative to start.
        $offset_msg = ( $remB4 ? ' Mins Before Start: ' : ' Mins After Start: ' )
         . $reminder['offset'];
        $remind_time = ( $remB4 ? $start - $offset : $start + $offset );
      } else { // Relative to end/due.
        $offset_msg = ( $remB4 ? ' Mins Before End: ' : ' Mins After End: ' )
         . $reminder['offset'];
        $remind_time = ( $remB4 ? $end - $offset : $end + $offset );
        $pointless = ( $remB4 ? $end : $end + $offset );
      }
    }
    // Factor in repeats if set.
    if ( $repeats > 0 && $times_sent <= $repeats )
      $remind_time += ( $reminder['duration'] * 60 * $times_sent );

    if ( $debug )
      echo ( empty ( $offset_msg ) ? '' : $offset_msg . '<br />' ) . '
  Event ' . ( $related == 'S' // Relative to start.
        ? 'start time is: ' . gmdate ( 'm/d/Y H:i', $start )
        : 'end time is: ' . gmdate ( 'm/d/Y H:i', $end ) ) . ' GMT<br />
  Remind time is: ' . gmdate ( 'm/d/Y H:i', $remind_time ) . ' GMT<br />
  Effective delivery time is: ' . date ( 'm/d/Y H:i T', $remind_time ) . '<br />
  Last sent on: '
       . ( $lastsent == 0 ? 'NEVER' : date ( 'm/d/Y H:i T', $lastsent ) )
    // No sense sending reminders if the event is over!
    // Unless the entry is a task.
       . '<br /><br />
  times_sent = ' . $times_sent . '
  repeats = ' . $repeats . '
  time = ' . date ( 'His', time () ). ' 
  remind_time = ' . date ( 'His', $remind_time ). ' 
  lastsent = ' . ( $lastsent > 0 ? date ( 'Ymd His', $lastsent  ): " NEVER " ) . ' 
  pointless = ' .date ( 'Ymd His',  $pointless  ). ' 
  is_task = ' . ( $is_task ? 'true' : 'false' ) . '<br />';

    if ( $times_sent < ( $repeats + 1 ) &&
        time () >= $remind_time && $lastsent <= $remind_time &&
        ( time () <= $pointless || $is_task ) ) {
      // Send a reminder.
      if ( $debug )
        echo ' SENDING REMINDER!<br />' . "\n";
      send_reminder ( $id, $start );
      // Now update the db...
      if ( $debug )
        echo '<br /> LOGGING REMINDER!<br /><br />' . "\n";
      log_reminder ( $id, $times_sent + 1 );
    }
  }
} //end function process_event

function my_get_repeating_entries ( $user, $dateYmd, $get_unapproved = true ) {
  global $debug, $repeated_events;

  $n = 0;
  $ret = array ();
  if ( $debug )
     echo "Getting repeating entries for $dateYmd<br />";

  for ( $i = 0, $cnt = count ( $repeated_events ); $i < $cnt; $i++ ) {
    $list = $repeated_events[$i]->getRepeatAllDates ();
    for ( $j = 0, $cnt_j = count ( $list ); $j < $cnt_j; $j++ ) {
	    if ( $debug )
	       echo "     checking $list[$j] = " . date ( 'Ymd', $list[$j]) . '<br />';

      if ( $dateYmd == date ( 'Ymd', $list[$j] ) ) {
        $ret[$n++] = $repeated_events[$i];
        if ( $debug )
           echo 'Added!<br />';
      }
    }
  }
  return $ret;
}

?>
