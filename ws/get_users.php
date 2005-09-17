<?php
/*
 * $Id$
 *
 * Description:
 *	Web Service functionality to get a list of all users.
 *	Uses XML (but not SOAP at this point since that would be
 *      overkill and require extra packages to install).
 *
 * Comments:
 *	Client apps must use the same authentication as the web browser.
 *	If WebCalendar is setup to use web-based authentication, then
 *	the login.php found in this directory should be used to obtain
 *	a session cookie.
 *
 * Developer Notes:
 *	If you enable the WS_DEBUG option below, all data will be written
 *	to a debug file in /tmp also.
 *
 */

$WS_DEBUG = false;

require_once "ws.php";

// Initialize...
ws_init ();

//Header ( "Content-type: text/xml" );
Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$out = "<users>\n";

// If login is public user, make sure public can view others...
if ( $login == "__public__" && $login != $user ) {
  if ( $public_access_others != 'Y' ) {
    $out .= "<error>" . translate("Not authorized") . "</error>\n";
    $out .= "</events>\n";
    exit;
  }
}

$userlist = get_my_users ( );

for ( $i = 0; $i < count ( $userlist ); $i++ ) {
  $admin_xml = ( $userlist[$i]['cal_is_admin'] == 'Y' ) ?
    "    <admin>1</admin>\n" : "";
  $out .=
    "  <user>\n" .
    "    <login>" . $userlist[$i]['cal_login'] . "</login>\n" .
    "    <lastname>" . ws_escape_xml ( $userlist[$i]['cal_lastname'] ) .
    "</lastname>\n" .
    "    <firstname>" . ws_escape_xml ( $userlist[$i]['cal_firstname'] ) .
    "</firstname>\n" .
    "    <fullname>" . ws_escape_xml ( $userlist[$i]['cal_fullname'] ) .
    "</fullname>\n" .
    "    <email>" . ws_escape_xml ( $userlist[$i]['cal_email'] ) .
    "</email>\n" .
    $admin_xml .
    "  </user>\n";
}

$out .= "</users>";

// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  ws_log_message ( $out );
}

// Send output now...
echo $out;

?>
