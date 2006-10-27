<?php
/*
 * $Id$
 *
 * Description:
 *	Web Service functionality to update, delete or add events.
 *
 * Input Parameters:
 *	id - event id
 *	username - user login of user to add/edit
 *	action - approve, reject, delete
 *
 * Result:
 *	On success:
 *		<result><success/></result>
 *	On failure/error:
 *		<result><error>Error message here...</error></result>
 *
 * Notes:
 *
 * Developer Notes:
 *	If you enable the WS_DEBUG option below, all data will be written
 *	to a debug file in /tmp also.
 *
 * Security:
 *	- The current user must have permission to modify the event
 *	  in the way specified.
 *
 */

$WS_DEBUG = false;

$error = '';

require_once "ws.php";

// Initialize...
ws_init ();

//Header ( "Content-type: text/xml" );
Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$out = '<result>';

$id = getIntValue ( 'id' );
$user = getGetValue ( 'username' );
if ( empty ( $user ) )
  $user = $login;
$action = getGetValue ( 'action' );
if ( $action != 'approve' && $action != 'delete' && $action != 'reject' ) {
  $error = "Unsupported action: " . ws_escape_xml ( $action );
}
if ( empty ( $error ) && empty ( $id ) ) {
  $error = "No event id specified";
}

// If public user, they cannot do this...
if ( empty ( $error ) && $login == '__public__' ) {
  $error = translate( 'Not authorized' );
}

// Only admin users can modify events on the public calendar
if ( empty ( $error ) && $PUBLIC_ACCESS == 'Y' && $user == '__public__' &&
  ! $is_admin ) {
  $error = translate( 'Not authorized' ) . ' ' . "(not admin)";
}

if ( empty ( $error ) && ! $is_admin && $user != $login ) {
  // User has request to modify event on someone else's calendar and
  // the user is not an admin user.
  if ( access_is_enabled () ) {
    if ( ! access_user_calendar ( 'approve', $user ) ) {
      $error = translate( 'Not authorized' );
    }
  } else {
    // TODO: support boss/assistant when UAC is not enabled
    $error = translate( 'Not authorized' );
  }
}

if ( $action == 'approve' ) {
  update_status ( 'A', $user, $id );
} else if ( $action == 'reject' ) {
  update_status ( 'R', $user, $id );
} else if ( $action == 'delete' ) {
  update_status ( 'D', $user, $id );
}

if ( empty ( $error ) ) {
  $out .= '<success/>';
} else {
  $out .= '<error>' . ws_escape_xml ( $error ) . '</error>';
}
$out .= "</result>\n";

// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  ws_log_message ( $out );
}

// Send output now...
echo $out;

?>
