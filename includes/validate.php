<?php

$validate_redirect = false;
$session_not_found = false;

if ( $single_user == "Y" ) {
  $login = $single_user_login;
} else {
  if ( $use_http_auth ) {
    // HTTP server did validation for us....
    if ( empty ( $PHP_AUTH_USER ) )
      $session_not_found = true;
    else
      $login = $PHP_AUTH_USER;
  } else {
    // We can't actually check the database yet since we haven't connected
    // to the database.  That happens in connect.php.

    // Check for session.  If not found, then note it for later
    // handling in connect.php.
    if ( empty ( $webcalendar_session ) && empty ( $login ) ) {
      $session_not_found = true;
    }

    else {
      // Check for cookie...
      if ( ! empty ( $webcalendar_session ) ) {
        $encoded_login = $webcalendar_session;
        if ( empty ( $encoded_login ) ) {
          // invalid session cookie
          $session_not_found = true;
        } else {
          $login_pw = split('\|', decode_string ($encoded_login));
          $login = $login_pw[0];
          $cryptpw = $login_pw[1];
          // make sure we are connected to the database for password check
          $c = dbi_connect ( $db_host, $db_login, $db_password, $db_database );
          if ( ! $c ) {
            echo "Error connecting to database:<BLOCKQUOTE>" . dbi_error () . "</BLOCKQUOTE>\n";
            exit;
          }

          if (!user_valid_crypt($login, $cryptpw)) {
            do_debug ( "User not logged in; redirecting to login page" );
            do_redirect ( "login.php" );
          }

          do_debug ( "Decoded login from cookie: $login" );
        }
      }
    }
  }
}

?>
