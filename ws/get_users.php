<?php
/**
 * Description:
 *  Web Service functionality to get a list of all users.
 *  Uses XML (but not SOAP at this point since that would be
 *       overkill and require extra packages to install).
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

$out = '
<users>';

// If login is public user, make sure public can view others...
if ( $login == '__public__' && $login != $user && $PUBLIC_ACCESS_OTHERS != 'Y' ) {
  $out .= '
  <error>' . translate ( 'Not authorized' ) . '</error>
</events>
';
  exit;
}

$userlist = get_my_users();

for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
  $out .= '
  <user>
    <login>' . $userlist[$i]['cal_login'] . '</login>
    <lastname>' . ws_escape_xml ( $userlist[$i]['cal_lastname'] ) . '</lastname>
    <firstname>' . ws_escape_xml ( $userlist[$i]['cal_firstname'] ) . '</firstname>
    <fullname>' . ws_escape_xml ( $userlist[$i]['cal_fullname'] ) . '</fullname>
    <email>' . ws_escape_xml ( $userlist[$i]['cal_email'] ) . '</email>'
   . ( $userlist[$i]['cal_is_admin'] == 'Y' ? '
    <admin>1</admin>' : '' ) . '
  </user>';
}

$out .= '
</users>
';

// If web service debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( $out );

// Send output now...
echo $out;

?>
