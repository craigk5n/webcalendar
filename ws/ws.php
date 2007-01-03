<?php
/* This file includes functions needed by WebCalendar web services.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */


/**
  * Initialize web service.
  * This will take care of user validation.
  */
function ws_init ( )
{
  global $user_inc, $basedir, $includedir, $site_extras,
    $admin_can_add_user, $admin_can_delete_user;

  // Load include files.
  $basedir = ".."; // points to the base WebCalendar directory relative to
                   // current working directory
  $includedir = "../includes";

  require_once "$includedir/classes/WebCalendar.class";
  require_once "$includedir/classes/Event.class";
  require_once "$includedir/classes/RptEvent.class";

  $WebCalendar =& new WebCalendar ( __FILE__ );

  include_once "$includedir/translate.php";
  include_once "$includedir/config.php";
  include_once "$includedir/dbi4php.php";
  include_once "$includedir/access.php";
  include_once "$includedir/functions.php";

  $WebCalendar->initializeFirstPhase();

  include_once "$includedir/$user_inc";
  include_once "$includedir/validate.php";
  include_once "$includedir/site_extras.php";

  $WebCalendar->initializeSecondPhase();

  load_global_settings ();
  load_user_preferences ();

  $WebCalendar->setLanguage();
}



// Format a text string for use in the XML returned to the client
function ws_escape_xml ( $str )
{
  $str = str_replace ( "\r\n", "\\n", $str );
  $str = str_replace ( "\n", "\\n", $str );
  $str = str_replace ( '<br />', "\\n", $str );
  $str = str_replace ( '<br />', "\\n", $str );
  $str = str_replace ( '\n', "<br />", $str );
  $str = str_replace ( '&amp;', '&', $str );
  $str = str_replace ( '&', '&amp;', $str );
  return ( str_replace ( "<", "&lt;", str_replace ( ">", "&gt;", $str ) ) );
}

// Send a single event
// This will include all participants (with status)
function ws_print_event_xml ( $id, $event_date, $extra_tags='' ) {
  global $site_extras, $WS_DEBUG,
    $SERVER_URL, $single_user, $single_user_login,
    $DISABLE_PRIORITY_FIELD, $DISABLE_PARTICIPANTS_FIELD,
    $ALLOW_EXTERNAL_USERS, $EXTERNAL_REMINDERS;

  // get participants first...
  $sql = "SELECT cal_login, cal_status FROM webcal_entry_user " .
    "WHERE cal_id = ? AND cal_status IN ('A','W') " .
    "ORDER BY cal_login";
  $res = dbi_execute ( $sql , array( $id ) );
  $participants = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[] = array (
        'cal_login' => $row[0],
        'cal_status' => $row[1],
      );
    }
  }

  // get external participants
  $ext_participants = array ();
  $num_ext_participants = 0;
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == 'Y' &&
    ! empty ( $EXTERNAL_REMINDERS ) && $EXTERNAL_REMINDERS == 'Y' ) {
    $sql = "SELECT cal_fullname, cal_email FROM webcal_entry_ext_user " .
      "WHERE cal_id = ? AND cal_email IS NOT NULL " .
      "ORDER BY cal_fullname";
    $res = dbi_execute ( $sql , array( $id ) );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $ext_participants[$num_ext_participants] = $row[0];
        $ext_participants_email[$num_ext_participants++] = $row[1];
      }
    }
  }

  if ( count ( $participants ) == 0 && ! $num_ext_participants ) {
    if ( $WS_DEBUG )
      $out .= "<!-- No participants found for event id: $id -->\n";
    return;
  }

  // get event details
  $res = dbi_execute (
    "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
    "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
    "cal_name, cal_description FROM webcal_entry WHERE cal_id = ?" , array( $id )
  );
  if ( ! $res ) {
    $out .= "Db error: could not find event id $id.\n";
    return;
  }


  if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
    $out .= "Error: could not find event id $id in database.\n";
    return;
  }

  $create_by = $row[0];
  $name = $row[9];
  $description = $row[10];

  $out = "<event>\n";
  $out .= "  <id>$id</id>\n";
  $out .= "  <name>" . ws_escape_xml ( $name ) . "</name>\n";
  if ( ! empty ( $SERVER_URL ) ) {
    if ( substr ( $SERVER_URL, -1, 1 ) == "/" ) {
      $out .= "  <url>" .  $SERVER_URL . "view_entry.php?id=" .
        $id . "</url>\n";
    } else {
      $out .= "  <url>" .  $SERVER_URL . "/view_entry.php?id=" .
        $id . "</url>\n";
    }
  }
  $out .= "  <description>" . ws_escape_xml ( $description ) .
    "</description>\n";
  $out .= "  <dateFormatted>" . date_to_str ( $event_date ) .
    "</dateFormatted>\n";
  $out .= "  <date>" . $event_date . "</date>\n";
  if ( $row[2] == 0 && $row[5] == ( 60 * 24 )  ) {
    $out .= "  <time>0</time>\n";
    $out .= "  <timeFormatted>All Day</timeFormatted>\n";
  } else if ( $row[2] >= 0 ) {
    $out .= "  <time>" . sprintf ( "%04d", $row[2] / 100 ) . "</time>\n";
    $out .= "  <timeFormatted>" . display_time ( $event_date .
      sprintf ( "%06d",  $row[2] ) ) . "</timeFormatted>\n";
  } else {
    $out .= "  <time>-1</time>\n";
    $out .= "  <timeFormatted>Untimed</timeFormatted>\n";
  }
  if ( $row[5] > 0 )
    $out .= "  <duration>" . $row[5] . "</duration>\n";
  if ( ! empty ( $DISABLE_PRIORITY_FIELD ) && $DISABLE_PRIORITY_FIELD == 'Y' ) {
    $pri[1] = translate ( 'High' );
    $pri[2] = translate ( 'Medium' );
    $pri[3] = translate ( 'Low' );
    $out .= "  <priority>" . $row[6] . '-' . $pri[ceil($row[6]/3)] . "</priority>\n";
  }
  if ( ! empty ( $DISABLE_ACCESS_FIELD ) && $DISABLE_ACCESS_FIELD == 'Y' )
    $out .= "  <access>" .
      ( $row[8] == "P" ? translate ( 'Public' ) : $translations['Confidential'] ) .
      "</access>\n";
  if ( ! strlen ( $single_user_login ) )
    $out .= "  <createdBy>" . $row[0] . "</createdBy>\n";
  $out .= "  <updateDate>" . date_to_str ( $row[3] ) . "</updateDate>\n";
  $out .= "  <updateTime>" . display_time ( $row[4] ) . "</updateTime>\n";

  // site extra fields
  $extras = get_site_extra_fields ( $id );
  $se = '';
  for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
    $extra_name = $site_extras[$i][0];
    $extra_descr = $site_extras[$i][1];
    $extra_type = $site_extras[$i][2];
    if ( ! empty ( $extras[$extra_name]['cal_name'] ) ) {
      $tag = preg_replace ( "/[^A-Za-z0-9]+/", "", translate ( $extra_descr ) );
      $tag = strtolower ( $tag );
      $tagname = str_replace ( '"', '', $extra_name );
      $se .= "    <siteExtra>\n";
      $se .= "      <number>$i</number>\n";
      $se .= "      <name>" . ws_escape_xml ( $extra_name ) . "</name>\n";
      $se .= "      <description>" . ws_escape_xml ( $extra_descr ) . "</description>\n";
      $se .= "      <type>" . $extra_type . "</type>\n";
      $se .= "      <value>";
      if ( $extra_type == EXTRA_DATE ) {
        //$se .= date_to_str ( $extras[$extra_name]['cal_date'] );
        $se .= $extras[$extra_name]['cal_date'];
      } else if ( $extra_type == EXTRA_MULTILINETEXT ) {
        $se .= ws_escape_xml ( $extras[$extra_name]['cal_data'] );
      } else if ( $extra_type == EXTRA_REMINDER ) {
        $se .= ( $extras[$extra_name]['cal_remind'] > 0 ?
          translate ( 'Yes') : translate ( 'No') );
      } else {
        // default method for EXTRA_URL, EXTRA_TEXT, etc...
        $se .= ws_escape_xml ( $extras[$extra_name]['cal_data'] );
      }
      $se .= "</value>\n    </siteExtra>\n";
    }
  }
  if ( $se != '' )
    $out .= "  <siteExtras>\n" . $se . "  </siteExtras>\n";
  if ( $single_user != 'Y' && ( empty ( $DISABLE_PARTICIPANTS_FIELD ) ||
    $DISABLE_PARTICIPANTS_FIELD != 'Y' ) ) {
    $out .= "  <participants>\n";
    for ( $i = 0; $i < count ( $participants ); $i++ ) {
      $out .= "    <participant status=\"" .
        $participants[$i]['cal_status'] . "\">" .
        $participants[$i]['cal_login'] .
        "</participant>\n";
    }
    for ( $i = 0; $i < count ( $ext_participants ); $i++ ) {
      $out .= "    <participant>" . ws_escape_xml ( $ext_participants[$i] ) .
        "</participant>\n";
    }
    $out .= "  </participants>\n";
  }

  if ( ! empty ( $extra_tags ) )
    $out .= $extra_tags;
  $out .= "</event>\n";

  return $out;
}


// Log a message to a file in /tmp
function ws_log_message ( $msg )
{
  $fd = fopen ( "/tmp/webcal-ws.log", "a+", true );
  fwrite ( $fd, gmdate ( "Y-m-d H:i:s" )  );
  fwrite ( $fd, "\n" . $msg . "\n\n" );
  fclose ( $fd );
}



?>
