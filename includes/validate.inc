<?php

if ( strlen ( $single_user_login ) > 0 ) {
  $login = $single_user_login;
} else {
  if ( $use_http_auth ) {
    // do nothing here... the validation takes place in connect.inc
    // since we need db access to validate.
    $login = $PHP_AUTH_USER;
  } else {
    // Check for cookie...
    // We really need to improve the security here.
    //$encoded_login = $HTTP_COOKIE_VARS["webcalendar_session"];
    $encoded_login = $webcalendar_session;
    if ( strlen ( $encoded_login ) == 0 ) {
      do_debug ( "User not logged in; redirecting to login page" );
      do_redirect ( "login.php" );
    }
    $login = decode_string ( $encoded_login );
    do_debug ( "Decoded login from cookie: $login" );
  }
}

?>
