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
$basedir = ".."; // points to the base WebCalendar directory relative to
                 // current working directory
$includedir = "../includes";

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

$debug = false; // set to true to print debug info...
$only_testing = false; // act like we're sending, but don't send -- for debugging

// Establish a database connection.
$c = dbi_connect ( $db_host, $db_login, $db_password, $db_database );
if ( ! $c ) {
  echo "Error connecting to database: " . dbi_error ();
  exit;
}

load_global_settings ();

$WebCalendar->setLanguage();

set_today();

if ( $debug )
  echo "<br />\n";

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
for ( $i = 0; $i < count ( $allusers ); $i++ ) {
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

$startdate = date ( "Ymd" );
$enddate = date ( "Ymd", time() + ( $DAYS_IN_ADVANCE * 24 * 3600 ) );

// Now read events all the repeating events (for all users)
$repeated_events = query_events ( "", true, "AND (webcal_entry_repeats.cal_end >= 
  $startdate OR webcal_entry_repeats.cal_end IS NULL) " );

// Read non-repeating events (for all users)
if ( $debug )
  echo "Checking for events from date $startdate to date $enddate <br />\n";
$events = read_events ( "", $startdate, $enddate );
if ( $debug )
  echo "Found " . count ( $events ) . " events in time range. <br />\n";

function indent ( $str ) {
  return "  " . str_replace ( "\n", "\n  ", $str );
}


// A convenience to avoid all the if/else associated with html vs text
// output.
function gen_output ( $useHtml, $prompt, $data ) {
  $ret = '';

  if ( $useHtml ) {
    $ret = '<tr><td valign="top">' . $prompt . ':</td><td valign="top">';
    if ( ! empty ( $GLOBALS['ALLOW_HTML_DESCRIPTION'] ) &&
      $GLOBALS['ALLOW_HTML_DESCRIPTION'] == 'Y' ) {
      $str = str_replace ( '&', '&amp;', $data );
      $str = str_replace ( '&amp;amp;', '&amp;', $str );
      // If there is no html found, then go ahead and replace
      // the line breaks ("\n") with the html break.
      if ( strstr ( $str, "<" ) && strstr ( $str, ">" ) ) {
        // found some html...
        $ret .= $str;
      } else {
        $ret .= nl2br ( activate_urls ( $str ) );
      }
    } else {
      // HTML not allowed...
      $ret .= nl2br ( activate_urls ( htmlspecialchars ( $data ) ) );
    }
    $ret .= "</td></tr>\n";
  } else {
    // Use plain text
    $ret = $prompt . ": " . $data . "\n";
  }

  return $ret;
}

// Send a reminder for a single event for a single day to all
// participants in the event.
// Send to participants who have accepted as well as those who have not yet
// approved.  But, don't send to users who rejected (cal_status='R').
function send_reminder ( $id, $event_date ) {
  global $names, $emails, $site_extras, $debug, $only_testing, $htmlmail,
    $SERVER_URL, $languages, $APPLICATION_NAME;
  global $ALLOW_EXTERNAL_USERS, $EXTERNAL_REMINDERS, $LANGUAGE,
    $def_tz, $tz;

  $pri[1] = translate("Low");
  $pri[2] = translate("Medium");
  $pri[3] = translate("High");

  // get participants first...
 
  $sql = "SELECT cal_login FROM webcal_entry_user " .
    "WHERE cal_id = ? AND cal_status IN ('A','W') " .
    "ORDER BY cal_login";
  $res = dbi_execute ( $sql , array ( $id ) );
  $participants = array ();
  $num_participants = 0;
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$num_participants++] = $row[0];
    }
  }

  // get external participants
  $ext_participants = array ();
  $num_ext_participants = 0;
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == "Y" &&
    ! empty ( $EXTERNAL_REMINDERS ) && $EXTERNAL_REMINDERS == "Y" ) {
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
  if ( isset ( $single_user ) && $single_user == "Y" ) {
    $mailusers[] = $emails[$single_user_login];
    $recipients[] = $single_user_login;
  } else {
    for ( $i = 0; $i < count ( $participants ); $i++ ) {
      if ( strlen ( $emails[$participants[$i]] ) ) {
        $mailusers[] = $emails[$participants[$i]];
        $recipients[] = $participants[$i];
      } else {
        if ( $debug )
          echo "No email for user $participants[$i] <br />\n";
      }
    }
    for ( $i = 0; $i < count ( $ext_participants ); $i++ ) {
      $mailusers[] = $ext_participants_email[$i];
      $recipients[] = $ext_participants[$i];
    }
  }
  if ( $debug )
    echo "Found " . count ( $mailusers ) . " with email addresses <br />\n";
  for ( $j = 0; $j < count ( $mailusers ); $j++ ) {
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
    if ( $userlang == "none" )
      $userlang = "English-US"; // gotta pick something
    if ( $debug )
      echo "Setting language to \"$userlang\" <br />\n";
    reset_language ( $userlang );
    // reset timezone setting for current user
    if ( ! empty ( $tz[$user] ) ) {
      $display_tzid = 2;  // display TZ
      $user_timezone = $tz[$user];
    } else if ( ! empty ( $def_tz ) ) {
      $display_tzid = 2;
      $user_timezone = $def_tz;  
    } else {
      $display_tzid = 3; // Do not use offset & display TZ
      $user_timezone = "";
    }
    $useHtml = ( ! empty ( $htmlmail[$user] )? true : false );

    if ( $useHtml ) {
      $body = "<html><head><title>" .
        $APPLICATION_NAME . "</title></head><body>\n<p>" .
        translate("This is a reminder for the event detailed below.") .
        "\n</p>\n";
    } else {
      $body = translate("This is a reminder for the event detailed below.") .
        "\n\n";
    }

    $create_by = $row[0];
    $name = $row[9];
    $description = $row[10];

    // add trailing '/' if not found in server_url
    //Don't include link foe External users
    if ( ! empty ( $SERVER_URL ) && ! $isExt ) {
      if ( substr ( $SERVER_URL, -1, 1 ) == "/" ) {
        $eventURL = $SERVER_URL . "view_entry.php?id=" . $id . "&em=1";
      } else {
        $eventURL = $SERVER_URL . "/view_entry.php?id=" . $id . "&em=1";
      }
      if ( $useHtml ) {
        $body .= "<p><a href=\"" . $eventURL . "\">" . $eventURL . "</a></p>\n";
      } else {
        $body .= $SERVER_URL . "view_entry.php?id=" . $id . "&em=1\n\n";
      }
    }

    if ( $useHtml ) {
      $body .= "<h3>" . strtoupper ( $name ) . "</h3>\n" .
        "<table><tr><td valign=\"top\">" .
        translate("Description") . ":</td><td valign=\"top\">" .
        $description . "</td></tr>\n" .
        "<tr><td>" . translate("Date") . ":</td><td>" .
        date_to_str ( $event_date ) . "</td></tr>\n";
    } else {
      $body .= strtoupper ( $name ) . "\n\n" .
        translate("Description") . ":\n" .
        indent ( $description ) . "\n" .
        translate("Date") . ": " . date_to_str ( $event_date ) . "\n";
    }

    if ( $row[2] >= 0 ) {
      $body .= gen_output ( $useHtml, translate ( "Time" ),
        display_time ( $row[1] .  $row[2], $display_tzid, '' ,
          $user_timezone, $userTformat ) );
    }
    if ( $row[5] > 0 ) {
      $body .= gen_output ( $useHtml, translate ( "Duration" ),
        $row[5] .  " " . translate("minutes") );
    }

    if ( empty ( $DISABLE_PRIORITY_FIELD ) || $DISABLE_PRIORITY_FIELD != 'Y' ) {
      $body .= gen_output ( $useHtml, translate ( "Priority" ),
        $pri[$row[6]] );
    }

    if ( empty ( $DISABLE_ACCESS_FIELD ) || $DISABLE_ACCESS_FIELD != 'Y' ) {
      $body .= gen_output ( $useHtml, translate ( "Access" ),
        ( $row[8] == "P" ? translate("Public") : translate("Confidential") ) );
    }

    if ( ! empty ( $single_user_login ) && $single_user_login == false ) {
      $body .= gen_output ( $useHtml, translate ( "Created by" ),
        $row[0] );
    }

    $body .= gen_output ( $useHtml, translate ( "Updated" ),
      date_to_str ( $row[3] ) . " " .  display_time ( $row[3] . $row[4],
        $display_tzid, '', $user_timezone, $userTformat ) );

    // site extra fields
    $extras = get_site_extra_fields ( $id );
    for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
      $extra_name = $site_extras[$i][0];
      $extra_descr = $site_extras[$i][1];
      $extra_type = $site_extras[$i][2];
      if ( ! empty (  $extras[$extra_name]['cal_name'] ) && 
      $extras[$extra_name]['cal_name'] != "" ) {
        $val = '';
        $prompt = $extra_descr;
        if ( $extra_type == EXTRA_DATE ) {
          $body .= gen_output ( $useHtml, $prompt,
            $extras[$extra_name]['cal_date'] );
        } else if ( $extra_type == EXTRA_MULTILINETEXT ) {
          $body .= gen_output ( $useHtml, $prompt,
            "\n" . indent ( $extras[$extra_name]['cal_data'] ) );
        } else {
          // default method for EXTRA_URL, EXTRA_TEXT, etc...
          $body .= gen_output ( $useHtml, $prompt,
            $extras[$extra_name]['cal_data'] );
        }
      }
    }
    if ( ( empty ( $single_user ) || $single_user != 'Y' ) &&
      ( empty ( $DISABLE_PARTICIPANTS_FIELD ) ||
      $DISABLE_PARTICIPANTS_FIELD != 'N' ) ) {
      if ( $useHtml ) {
        $body .= "<tr><td valign=\"top\">" .
          translate("Participants") .  ":</td><td>";
      } else {
        $body .= translate("Participants") . ":\n";
      }
      for ( $i = 0; $i < count ( $participants ); $i++ ) {
        if ( $useHtml ) {
          $body .= $names[$participants[$i]] . "<br />\n";
        } else {
          $body .= "  " . $names[$participants[$i]] . "\n";
        }
      }
      for ( $i = 0; $i < count ( $ext_participants ); $i++ ) {
        if ( $useHtml ) {
          $body .= $ext_participants[$i] . " (" .
            translate("External User") . ")<br />\n";
        } else {
          $body .= "  " . $ext_participants[$i] . " (" .
            translate("External User") . ")\n";
        }
      }
    }

    if ( $useHtml ) {
      $body .= "</table>\n" .
        "</body></html>\n";
    }
  
    $subject = translate("Reminder") . ": " . stripslashes ( $name );


    if ( $debug )
      echo "Sending mail to $recip (in $userlang)\n";
    if ( $only_testing ) {
      if ( $debug )
        echo "<hr /><pre>To: $recip\nSubject: $subject\nFrom:". 
          translate ( "Administrator" ). "\n\n$body\n\n</pre>\n";
    } else {
      $mail = new WebCalMailer;
      user_load_variables ( $user, "temp" );
      if ( strlen ( $GLOBALS["EMAIL_FALLBACK_FROM"] ) ) {
        $mail->From = $GLOBALS["EMAIL_FALLBACK_FROM"];
        $mail->FromName = translate ( "Administrator" );
      } else {
        $mail->From = translate ( "Administrator" );
      }
      $mail->IsHTML( $useHtml );
      $recipName = ( $isExt ? $user : $GLOBALS ['tempfullname'] );
      $mail->AddAddress( $recip, $recipName );
      $mail->Subject = $subject;
      if ( $isExt ) //send ics attachment to External Users
        $mail->IcsAttach ( $id ) ;
      $mail->Body  = $body;
      $mail->Send();
      $mail->ClearAll();

      $cal_text = ( $isExt ? translate("External User") : '' );
      activity_log ( $id, "system", $user, LOG_REMINDER, $cal_text );
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
      "WHERE cal_id = ?", array ( time() - date("Z"), $times_sent, $id ) );
  }
}


// Process an event for a single day.  Check to see if it has
// a reminder, when it needs to be sent and when the last time it
// was sent.
function process_event ( $id, $name, $start, $end ) {
  global $debug, $only_testing;
  
  $reminder = array();
  
  if ( $debug )
    printf ( "Event %d: \"%s\" at %s on %s <br />\n",
      $id, $name, date("H:i:s", $start ) , date("Ymd", $start ));

    //get reminders array
    $reminder = getReminders ( $id );

    if ( ! empty ( $reminder ) ) {
      if ( $debug )
        echo "  Reminder set for event. <br />\n";
      $times_sent = $reminder['times_sent'];
      if ( ! empty ( $reminder['date'] ) ) {  //we're using a date
        $remind_time =  $reminder['date'];
      } else { // we're using offsets
        $offset =  $reminder['offset'] * 60; //convert to seconds
        if ( $reminder['related'] == 'N' ) { //relative to start
          $remind_time = ( $reminder['before'] == 'Y'? $start - $offset : $start + $offset );
        } else { //relative to end/due
          $remind_time = ( $reminder['before'] == 'Y'? $end - $offset : $end + $offset );        
        }
      }
      //factor in repeats if set
      if ( $reminder['repeats'] > 0 && $reminder['times_sent'] <= $reminder['repeats'] ) {
        $remind_time += ( $reminder['duration'] * 60 * $times_sent );
        if ( ! empty ( $offset ) ) $offset += ( $reminder['duration'] * 60 );
        $times_sent += 1;
      }

      if ( $debug )
        if ( ! empty ( $offset ) ) echo "  Mins Before: $offset <br />\n";
      if ( $debug ) {
        echo "  Event time is: " . date ( "m/d/Y H:i", $start ) . " GMT<br />\n";
        echo "  Remind time is: " . date ( "m/d/Y H:i", $remind_time ) . " GMT<br />\n";
        echo "Server Timezone: " . $GLOBALS['SERVER_TIMEZONE'] . 
          "<br />\nServer Difference from GMT (minutes) : " .
          date ("Z"). "<br />\n";    
        echo "Effective delivery time is: " . 
          date ( "m/d/Y H:i", $remind_time + date ("Z")  ) . " " .
            date ("T"). "<br /><br />\n";
      }

      if ( time() >= $remind_time + date ("Z")) {
        if ( $debug )
          echo "  Last sent on: " .  
       ($reminder['last_sent'] == 0 ? "NEVER" : date ( "m/d/Y H:i", 
         $reminder['last_sent'] ) ). "<br />\n";
        if ( $reminder['last_sent'] < $remind_time ) {
          // Send a reminder
          if ( $debug )
            echo "  SENDING REMINDER! <br />\n";
          send_reminder ( $id, date("Ymd", $start ) );
          // now update the db...
          log_reminder ( $id, $times_sent );
        }
      }
    }
}


$startdate = time(); // today
for ( $d = 0; $d < $DAYS_IN_ADVANCE; $d++ ) {
  $date = date ( "Ymd", time() + ( $d * 24 * 3600 ) );
  //echo "Date: $date\n";
  // Get non-repeating events for this date.
  // An event Fwill be included one time for each participant.
  $ev = get_entries ( "", $date );
  // Keep track of duplicates
  $completed_ids = array ( );
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    $id = $ev[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
  if ( $ev[$i]->getCalType() == "T" || $ev[$i]->getCalType() == "N") {   
    process_event ( $id, $ev[$i]->getName(), $ev[$i]->getDateTimeTS(), 
      $ev[$i]->getDueDateTimeTS() );
  } else {
    process_event ( $id, $ev[$i]->getName(), $ev[$i]->getDateTimeTS(), 
      $ev[$i]->getEndDateTimeTS() ); 
  }
  }
  $rep = get_repeating_entries ( "", $date );
  for ( $i = 0; $i < count ( $rep ); $i++ ) {
    $id = $rep[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
  if ( $rep[$i]->getCalType() == "T" || $rep[$i]->getCalType() == "N" ) {
    process_event ( $id, $rep[$i]->getName(), $rep[$i]->getDateTimeTS(), 
      $rep[$i]->getDueDateTimeTS() );
  } else {
    process_event ( $id, $rep[$i]->getName(), $rep[$i]->getDateTimeTS(), 
      $rep[$i]->getEndDateTimeTS() );  
  }
  }
}

if ( $debug )
  echo "Done.<br />\n";

?>
