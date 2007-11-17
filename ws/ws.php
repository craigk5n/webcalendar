<?php
/* This file includes functions needed by WebCalendar web services.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */

  // Points to the base WebCalendar directory relative to current working directory.
  define ( '_WC_BASE_DIR', '..' );
  define ( '_WC_INCLUDE_DIR', _WC_BASE_DIR . '/includes/' );
  
/* Initialize web service.  This will take care of user validation.
 */
function ws_init () {
  global $admin_can_add_user, $WC->User->adminCanDeleteUser (),
  $site_extras;

  // Load include files.

  include_once _WC_INCLUDE_DIR . 'translate.php';
  require_once _WC_INCLUDE_DIR . 'classes/WebCalendar.class';
  require_once _WC_INCLUDE_DIR . 'classes/Event.class';
  require_once _WC_INCLUDE_DIR . 'classes/RptEvent.class';

  $WC =& new WebCalendar ( __FILE__ );

  include_once _WC_INCLUDE_DIR . 'config.php';
  include_once _WC_INCLUDE_DIR . 'dbi4php.php';
  include_once _WC_INCLUDE_DIR . 'access.php';
  include_once _WC_INCLUDE_DIR . 'functions.php';

  $WC->initializeFirstPhase ();

  include_once _WC_INCLUDE_DIR . 'site_extras.php';

  $WC->initializeSecondPhase ();

  $WC->setLanguage ();
}

/* Format a text string for use in the XML returned to the client.
 */
function ws_escape_xml ( $str ) {
  $str = str_replace ( "\r\n", "\\n", $str );
  $str = str_replace ( "\n", "\\n", $str );
  $str = str_replace ( '<br />', "\\n", $str );
  $str = str_replace ( '<br />', "\\n", $str );
  $str = str_replace ( '\n', "<br />", $str );
  $str = str_replace ( '&amp;', '&', $str );
  $str = str_replace ( '&', '&amp;', $str );
  return ( str_replace ( '<', '&lt;', str_replace ( '>', '&gt;', $str ) ) );
}

/* Send a single event. This will include all participants (with status).
 */
function ws_print_event_xml ( $eid, $event_date, $extra_tags = '' ) {
  global $site_extras, $WS_DEBUG;
  
  $server_url = getPref ( 'SERVER_URL', 2 );
  // Get participants first...
  $res = dbi_execute ( 'SELECT cal_login, cal_status FROM webcal_entry_user
    WHERE cal_id = ? AND cal_status IN (\'A\',\'W\') ORDER BY cal_login',
    array ( $eid ) );
  $participants = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[] = array (
        'cal_login' => $row[0],
        'cal_status' => $row[1],
        );
    }
  }

  // Get external participants.
  $ext_participants = array ();
  $num_ext_participants = 0;
  if ( getPref ( 'ALLOW_EXTERNAL_USERS' ) && getPref ( 'EXTERNAL_REMINDERS' ) ) {
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

  if ( count ( $participants ) == 0 && ! $num_ext_participants && $WS_DEBUG ) {
    $out .= '
<!-- ' . str_replace ( 'XXX', $eid,
      translate ( 'No participants found for event id XXX.' ) ) . ' -->';
    return;
  }

  // Get event details.
  $res = dbi_execute ( 'SELECT cal_create_by, cal_date, cal_mod_date,
    cal_duration, cal_priority, cal_type, cal_access, cal_name,
    cal_description FROM webcal_entry WHERE cal_id = ?', array ( $eid )
    );
  if ( $res ) {
    if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
      echo translate ( 'Error' ) . ': ' . str_replace ( 'XXX', $eid,
      translate ( 'could not find event id XXX in database.' ) ) . "\n";
      return;
		}
  }

  $create_by = $row[0];
  $name = $row[9];
  $description = $row[10];

  $out = '
<event>
  <id>' . $eid . '</id>
  <name>' . ws_escape_xml ( $name ) . '</name>' . ( ! empty ( $server_url ) ? '
  <url>' . $server_url . ( substr ( $server_url, -1, 1 ) == '/' ? '' : '/' )
     . 'view_entry.php?eid=' . $eid . '</url>' : '' ) . '
  <description>' . ws_escape_xml ( $description ) . '</description>
  <dateFormatted>' . date_to_str ( $event_date ) . '</dateFormatted>
  <date>' . $event_date . '</date>
  <time>';
  //TODO fix cal_time (row[2])
  if (  $row[5] == 1440 )
    $out .= '0</time>
  <timeFormatted>All Day';
  elseif ( $row[2] >= 0 )
    $out .= sprintf ( "%04d", $row[2] / 100 ) . '</time>
  <timeFormatted>' . display_time ( $event_date );
  else
    $out .= '-1</time>
  <timeFormatted>Untimed';

  $out .= '</timeFormatted>' . ( $row[5] > 0 ? '
  <duration>' . $row[5] . '</duration>' : '' );

  if ( ! getPref ( 'DISABLE_PRIORITY_FIELD' ) ) {
    $pri[1] = translate ( 'High' );
    $pri[2] = translate ( 'Medium' );
    $pri[3] = translate ( 'Low' );
    $out .= '
  <priority>' . $row[6] . '-' . $pri[ceil ( $row[6] / 3 )] . '</priority>';
  }

  $out .= ( ! getPref ( 'DISABLE_ACCESS_FIELD' ) ? '
  <access>'
     . ( $row[8] == 'P' ? translate ( 'Public' ) : translate ( 'Confidential' ) )
     . '</access>' : '' ) . ( ! _WC_SINGLE_USER ? '
  <createdBy>' . $row[0] . '</createdBy>' : '' ) . '
  <updateDate>' . date_to_str ( $row[3] ) . '</updateDate>
  <updateTime>' . display_time ( $row[4] ) . '</updateTime>';

  // Site extra fields.
  $extras = get_site_extra_fields ( $eid );
  $se = '';
  for ( $i = 0, $cnt = count ( $site_extras ); $i < $cnt; $i++ ) {
    $extra_name = $site_extras[$i][0];
    $extra_descr = $site_extras[$i][1];
    $extra_type = $site_extras[$i][2];
    if ( ! empty ( $extras[$extra_name]['cal_name'] ) ) {
      $tag = strtolower ( preg_replace ( '/[^A-Za-z0-9]+/', '',
          translate ( $extra_descr ) ) );
      $tagname = str_replace ( '"', '', $extra_name );

      $se .= '
    <siteExtra>
      <number>' . $i . '</number>
      <name>' . ws_escape_xml ( $extra_name ) . '</name>
      <description>' . ws_escape_xml ( $extra_descr ) . '</description>
      <type>' . $extra_type . '</type>
      <value>';

      if ( $extra_type == EXTRA_DATE )
        // $se .= date_to_str ( $extras[$extra_name]['cal_date'] );
        $se .= $extras[$extra_name]['cal_date'];
      elseif ( $extra_type == EXTRA_MULTILINETEXT )
        $se .= ws_escape_xml ( $extras[$extra_name]['cal_data'] );
      elseif ( $extra_type == EXTRA_REMINDER )
        $se .= ( $extras[$extra_name]['cal_remind'] > 0
          ? translate ( 'Yes' ) : translate ( 'No' ) );
      else
        // Default method for EXTRA_URL, EXTRA_TEXT, etc...
        $se .= ws_escape_xml ( $extras[$extra_name]['cal_data'] );

      $se .= '</value>
    </siteExtra>';
    }
  }

  $out .= ( $se != '' ? '
  <siteExtras>' . $se . '
  </siteExtras>' : '' );

  if ( ! _WC_SINGLE_USER && ! getPref ( 'DISABLE_PARTICIPANTS_FIELD' ) ) {
    $out .= '
  <participants>';
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      $out .= '
    <participant status="' . $participants[$i]['cal_status'] . '">'
       . $participants[$i]['cal_login'] . '</participant>';
    }
    for ( $i = 0, $cnt = count ( $ext_participants ); $i < $cnt; $i++ ) {
      $out .= '
    <participant>' . ws_escape_xml ( $ext_participants[$i] ) . '</participant>';
    }

    $out .= '
  </participants>';
  }

  return $out . ( ! empty ( $extra_tags ) ? $extra_tags : '' ) . '
</event>
';
}

// Log a message to a file in /tmp.
function ws_log_message ( $msg ) {
  $fd = fopen ( '/tmp/webcal-ws.log', 'a+', true );
  fwrite ( $fd, gmdate ( 'Y-m-d H:i:s' ) . "\n$msg\n\n" );
  fclose ( $fd );
}

?>
