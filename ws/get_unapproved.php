<?php
/*
 * $Id$
 *
 * Description:
 *  Web Service functionality to get unapproved events.
 *  This will list events for the current user and for any other
 *  user that the current user is authorized to approve events for.
 *  Uses REST-style web service.
 *
 * Comments:
 *  Client apps must use the same authentication as the web browser.
 *  If WebCalendar is setup to use web-based authentication, then
 *  the login.php found in this directory should be used to obtain
 *  a session cookie.
 *
 * Developer Notes:
 *  If you enable the WS_DEBUG option below, all data will be written
 *  to a debug file in /tmp also.
 *
 */

$WS_DEBUG = false;

require_once "ws.php";

// Initialize...
ws_init ();

//Header ( "Content-type: text/xml" );
Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$sentIds = array ();

$out = '';

// Public is not allowed to approve anything
if ( $login == '__public__' ) {
  $out .= "<error>" . translate("Not authorized") . "</error>\n";
  $out .= "</events>\n";
  exit;
}

if ( empty ( $user ) )
  $user = $login;

// If viewing different user then yourself...
if ( $login != $user ) {
  if ( $ALLOW_VIEW_OTHER != 'Y' ) {
    $out .= "<error>" . translate("Not authorized") . "</error>\n";
    $out .= "</events>\n";
    exit;
  }
  //$out .= "<!-- Allowing user to view other user's calendar -->\n";
}





// Get users that this user can approve
$userList = get_users_to_approve ();

$out = "<unapproved>\n";

$out .= "  <userlist>\n";
for ( $i = 0; $i < count ( $userList ); $i++ ) {
  $out .= "    <login>" . ws_escape_xml ( $userList[$i] ) . "</login>\n";
}
$out .= "  </userlist>\n";

$out .= "<events>\n";
for ( $i = 0; $i < count ( $userList ); $i++ ) {
  if ( $WS_DEBUG )
    $out .= "<!-- Getting unapproved for user '" . $userList[$i] . "' -->\n";
  $out .= get_unapproved ( $userList[$i] );
}

$out .= "</events>\n</unapproved>\n";

// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  ws_log_message ( $out );
}

// Send output now...
echo $out;


// Process an event.  For unapproved events, we may find that
// the same event is listed more than once (if two participants
// are not yet approved.)  In that case, we send the event just
// once since the participant list (with status) is sent with
// the event.
function process_event ( $id, $name, $event_date, $event_time ) {
  global $WS_DEBUG, $out, $sentIds;

  if ( ! empty ( $sentIds[$id] ) ) {
    if ( $WS_DEBUG )
      ws_log_message ( "Event id=$id \"$name\" already sent" );
    return '';
  } else {
    if ( $WS_DEBUG )
      ws_log_message ( "Event id=$id \"$name\" at $event_time on $event_date" );
    $sentIds[$id] = true;
    return ws_print_event_xml ( $id, $event_date );
  }
}



// Get the list of unapproved events for the specified user.
function get_unapproved ( $user )
{
  global $temp_fullname, $key, $login, $NONUSER_ENABLED;
  
  $count = 0; 
  $ret = '';
  user_load_variables ( $user, 'temp_' );
  //echo "Listing events for $user<br />";

  $sql = 'SELECT we.cal_id, we.cal_name, ' .
    'we.cal_date, we.cal_time ' .
    'FROM webcal_entry we, webcal_entry_user weu' .
    'WHERE we.cal_id = weu.cal_id ' .
    'AND weu.cal_login = ? ' .
    'AND weu.cal_status = \'W\' ' .
    'ORDER BY we.cal_date';
  $rows = dbi_get_cached_rows ( $sql , array ( $user ) );
  echo "<!-- SQL:\n $sql \n-->\n";
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


// Get an array of users who the current user has permission
// to approve events for.
// Returns an array of logins.
function get_users_to_approve () {
  global $NONUSER_ENABLED, $PUBLIC_ACCESS, $login, $user, $is_admin;
  $my_non_users = $app_users = array ();
  $app_user_hash = array ( );
  $non_users = get_nonuser_cals ( );
  foreach ( $non_users as $nonuser ) {
    if ( user_is_nonuser_admin ( $login, $nonuser['cal_login'] ) ) {
      $my_non_users[]['cal_login'] = $nonuser['cal_login'];
      //echo $nonuser['cal_login'] . "<br />";
    }
  }

  // First, we list ourself
  $app_users[] = $login;
  $app_user_hash[$login] = 1;
  if ( access_is_enabled () ) {
    if ( $NONUSER_ENABLED == 'Y' ) {
      $all = array_merge ( get_my_users ( ), $my_non_users );
    } else {
      $all = get_my_users ( );
    }
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
