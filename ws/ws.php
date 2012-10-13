<?php
/**
 * Page description:
 * This file includes functions needed by WebCalendar web services.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */

/**
 * Initialize web service. This will take care of user validation.
 */
function ws_init() {
  global $admin_can_add_user, $admin_can_delete_user,
  $site_extras, $user_inc;

  // Load include files.
  define( '__WC_BASEDIR', '../' ); // Points to the base WebCalendar directory
                 // relative to current working directory.
  define( '__WC_INCLUDEDIR', __WC_BASEDIR . 'includes/' );
  define( '__WC_CLASSDIR', __WC_INCLUDEDIR . 'classes/' );

  foreach( array(
      'access',
      'config',
      'dbi4php',
      'functions',
      'site_extras',
      'translate',
      'validate',
    ) as $i ) {
    include_once __WC_INCLUDEDIR . $i . '.php';
  }
  foreach( array(
      'WebCalendar',
      'Event',
      'RptEvent',
    ) as $i ) {
    require_once __WC_CLASSDIR . $i . '.class';
  }
  $WebCalendar = new WebCalendar( __FILE__ );
  $WebCalendar->initializeFirstPhase();

  include_once __WC_INCLUDEDIR . $user_inc;

  $WebCalendar->initializeSecondPhase();

  load_global_settings();
  load_user_preferences();

  $WebCalendar->setLanguage();
}

/**
 * Format a text string for use in the XML returned to the client.
 */
function ws_escape_xml ( $str ) {
  $str = str_replace ( "\r\n", "\\n", $str );
  $str = str_replace ( "\n", "\\n", $str );
  $str = str_replace ( '<br>', "\\n", $str );
  $str = str_replace ( '<br>', "\\n", $str );
  $str = str_replace ( '\n', "<br>", $str );
  $str = str_replace ( '&amp;', '&', $str );
  $str = str_replace ( '&', '&amp;', $str );
  return ( str_replace ( '<', '&lt;', str_replace ( '>', '&gt;', $str ) ) );
}

/**
 * Send a single event. This will include all participants (with status).
 */
function ws_print_event_xml ( $id, $event_date, $extra_tags = '' ) {
  global $ALLOW_EXTERNAL_USERS, $DISABLE_PARTICIPANTS_FIELD,
  $DISABLE_PRIORITY_FIELD, $EXTERNAL_REMINDERS, $noStr, $pri, $SERVER_URL,
  $single_user, $single_user_login, $site_extras, $WS_DEBUG, $yesStr;

  // Get participants first...
  $res = dbi_execute ( 'SELECT cal_login, cal_status FROM webcal_entry_user
    WHERE cal_id = ? AND cal_status IN (\'A\',\'W\') ORDER BY cal_login',
    array ( $id ) );
  $participants = array();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[] = array (
        'cal_login' => $row[0],
        'cal_status' => $row[1],
        );
    }
  }

  // Get external participants.
  $ext_participants = array();
  $num_ext_participants = 0;
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

  if ( count ( $participants ) == 0 && ! $num_ext_participants && $WS_DEBUG ) {
    $out .= '
<!-- ' . str_replace ( 'XXX', $id,
      translate ( 'No party for XXX' ) ) . ' -->';
    return;
  }

  // Get event details.
  $res = dbi_execute ( 'SELECT cal_create_by, cal_date, cal_time, cal_mod_date,
    cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, cal_name,
    cal_description FROM webcal_entry WHERE cal_id = ?', array ( $id )
    );
  if ( ! $res ) {
    $out .= '
' . str_replace ( 'XXX', $id,
      translate ( 'Db error event XXX not found' ) );
    return;
  }

  if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
    $out .= '
' . str_replace ( 'XXX', $id,
      translate ( 'id XXX not found in db' ) );
    return;
  }

  $create_by = $row[0];
  $name = $row[9];
  $description = $row[10];

  $out = '
<event>
  <id>' . $id . '</id>
  <name>' . ws_escape_xml ( $name ) . '</name>' . ( empty( $SERVER_URL ) ? '' : '
  <url>' . $SERVER_URL . ( substr ( $SERVER_URL, -1, 1 ) == '/' ? '' : '/' )
     . 'view_entry.php?id=' . $id . '</url>' ) . '
  <description>' . ws_escape_xml ( $description ) . '</description>
  <dateFormatted>' . date_to_str ( $event_date ) . '</dateFormatted>
  <date>' . $event_date . '</date>
  <time>';

  if ( $row[2] == 0 && $row[5] == 1440 )
    $out .= '0</time>
  <timeFormatted>All Day';
  elseif ( $row[2] >= 0 )
    $out .= sprintf ( "%04d", $row[2] / 100 ) . '</time>
  <timeFormatted>' . display_time ( $event_date . sprintf ( "%06d", $row[2] ) );
  else
    $out .= '-1</time>
  <timeFormatted>Untimed';

  $out .= '</timeFormatted>' . ( $row[5] > 0 ? '
  <duration>' . $row[5] . '</duration>' : '' );

  if ( ! empty ( $DISABLE_PRIORITY_FIELD ) && $DISABLE_PRIORITY_FIELD == 'Y' ) {
    $out .= '
  <priority>' . $row[6] . '-' . $pri[ceil ( $row[6] / 3 )] . '</priority>';
  }

  $out .= ( ! empty ( $DISABLE_ACCESS_FIELD ) && $DISABLE_ACCESS_FIELD == 'Y' ? '
  <access>'
     . ( $row[8] == 'P' ? translate ( 'Public' ) : translate ( 'Confidential' ) )
     . '</access>' : '' ) . ( ! strlen ( $single_user_login ) ? '
  <createdBy>' . $row[0] . '</createdBy>' : '' ) . '
  <updateDate>' . date_to_str ( $row[3] ) . '</updateDate>
  <updateTime>' . display_time ( $row[4] ) . '</updateTime>';

  // Site extra fields.
  $extras = get_site_extra_fields ( $id );
  $se = '';
  for ( $i = 0, $cnt = count ( $site_extras ); $i < $cnt; $i++ ) {
    $extra_name = $site_extras[$i][0];
    $extra_descr= $site_extras[$i][1];
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
          ? $yesStr : $noStr );
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

  if ( $single_user != 'Y' &&
    ( empty ( $DISABLE_PARTICIPANTS_FIELD ) ||
      $DISABLE_PARTICIPANTS_FIELD != 'Y' ) ) {
    $out .= '
  <participants>';
    foreach ( $participants as $i ) {
      $out .= '
    <participant status="' . $i['cal_status'] . '">'
       . $i['cal_login'] . '</participant>';
    }
    foreach ( $ext_participants as $i ) {
      $out .= '
    <participant>' . ws_escape_xml ( $i ) . '</participant>';
    }

    $out .= '
  </participants>';
  }

  return $out . ( empty( $extra_tags ) ? '' : $extra_tags ) . '
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
