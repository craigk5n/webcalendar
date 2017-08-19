<?php
/**
 * Description:
 *  Web Service functionality to get unapproved events. This will list events
 *  for the current user and for any other user for whom the current user is
 *  authorized to approve events. Uses REST-style web service.
 *
 * Comments:
 *  Client apps must use the same authentication as the web browser. If
 *  WebCalendar is setup to use web-based authentication, then the login.php
 *  found in this directory should be used to obtain a session cookie.
 *
 * Developer Notes:
 *  If you enable the WS_DEBUG option below,
 *  all data will be written to a debug file in /tmp also.
 */

$WS_DEBUG = false;

require_once 'ws.php';

// Initialize...
ws_init();

// header ( 'Content-type: text/xml' );
header ( 'Content-type: text/plain' );

echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";

// Public is not allowed to approve anything.
if ( $login == '__public__' ) {
  $out = '
  <error>' . translate ( 'Not authorized' ) . '</error>
</events>
';
  exit;
}

if ( empty ( $user ) )
  $user = $login;

// If viewing different user then yourself...
if ( $login != $user && $ALLOW_VIEW_OTHER != 'Y' ) {
    $out = '
  <error>' . translate ( 'Not authorized' ) . '</error>
</events>
';
    exit;
}

$sentIds = [];

// Get users that this user can approve.
$userList = get_users_to_approve();

$out = '
<unapproved>
  <userlist>';
$out2 = '';
$unapprovedStr = translate ( 'Getting unapproved for user XXX.' );

for ( $i = 0, $cnt = count ( $userList ); $i < $cnt; $i++ ) {
  $out .= '
    <login>' . ws_escape_xml ( $userList[$i] ) . '</login>';

  $out2 .= ( $WS_DEBUG ? '
<!-- ' . str_replace ( 'XXX', $userList[$i], $unapprovedStr ) . ' -->' : '' )
   . get_unapproved ( $userList[$i] );
}

$out .= '
  </userlist>
  <events>' . $out2 . '
  </events>
</unapproved>
';

// If web service debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( $out );

// Send output now...
echo $out;

/**
 * Process an event. For unapproved events, we may find that the same event is
 * listed more than once (if two participants are not yet approved.)
 * In that case, we send the event just once since the participant list
 * (with status) is sent with the event.
 */
function process_event ( $id, $name, $event_date, $event_time ) {
  global $out, $sentIds, $WS_DEBUG;

  if ( ! empty ( $sentIds[$id] ) ) {
    if ( $WS_DEBUG )
      ws_log_message ( str_replace ( ['XXX', 'YYY'], [$id, $name],
          translate ('Event id=XXX YYY already sent.' ) ) );
    return '';
  } else {
    if ( $WS_DEBUG )
      ws_log_message ( str_replace ( ['XXX', 'YYY', 'ZZZ', 'AAA'],
          [$id, $name, $event_time, $event_date],
          translate ( 'Event id=XXX YYY at ZZZ on AAA.' ) ) );
    $sentIds[$id] = true;
    return ws_print_event_xml ( $id, $event_date );
  }
}

// Get the list of unapproved events for the specified user.
function get_unapproved ( $user ) {
  global $key, $login, $NONUSER_ENABLED, $temp_fullname;

  $count = 0;
  $ret = '';
  user_load_variables ( $user, 'temp_' );
  // echo 'Listing events for ' . $user . '<br />';

  $sql = 'SELECT we.cal_id, we.cal_name, we.cal_date, we.cal_time
    FROM webcal_entry we, webcal_entry_user weu
    WHERE we.cal_id = weu.cal_id AND weu.cal_login = ? AND weu.cal_status = \'W\'
    ORDER BY we.cal_date';
  $rows = dbi_get_cached_rows ( $sql, [$user] );
  echo '
<!-- SQL:
' . $sql . '
-->
';
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $id = $row[0];
      $name = $row[1];
      $date = $row[2];
      $time = $row[3];
      $ret .= process_event ( $id, $name, $date, $time, $user );
    }
  }
  return $ret;
}

// Get an array of users for whom the current user has event approval permission.
// Returns an array of logins.
function get_users_to_approve() {
  global $is_admin, $login, $NONUSER_ENABLED, $PUBLIC_ACCESS, $user;
  $app_user_hash = $app_users = $my_non_users = [];
  $non_users = get_nonuser_cals();
  foreach ( $non_users as $nonuser ) {
    if ( user_is_nonuser_admin ( $login, $nonuser['cal_login'] ) ) {
      $my_non_users[]['cal_login'] = $nonuser['cal_login'];
      // echo $nonuser['cal_login'] . "<br />";
    }
  }

  // First, we list ourself.
  $app_users[] = $login;
  $app_user_hash[$login] = 1;
  if ( access_is_enabled() ) {
    $all = ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == 'Y'
      ? array_merge ( get_my_users(), $my_non_users ) : get_my_users() );
    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
      if ( access_user_calendar ( 'approve', $x ) ) {
        if ( empty ( $app_user_hash[$x] ) ) {
          $app_users[] = $x;
          $app_user_hash[$x] = 1;
        }
      }
    }
  } else {
    if ( $is_admin && $PUBLIC_ACCESS == 'Y' &&
      ( empty ( $user ) || $user != '__public__' ) ) {
      $app_users[] = '__public__';
      $app_users_hash['__public__'] = 1;
    }
    $all = $my_non_users;
    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
      if ( empty ( $app_user_hash[$x] ) ) {
        $app_users[] = $x;
        $app_user_hash[$x] = 1;
      }
    }
  }

  return $app_users;
}

?>
