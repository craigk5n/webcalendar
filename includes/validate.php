<?php
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}



// Do a sanity check.  Make sure we can access webcal_config table.
// We call this right after the first call to dbi_connect() (from
// either connect.php or here in validate.php).
function doDbSanityCheck () {
  global $db_login, $db_host, $db_database;
  $res = @dbi_query ( "SELECT COUNT(cal_value) FROM webcal_config",
    false, false );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      // Found database.  All is peachy.
      dbi_free_result ( $res );
    } else {
      // Error accessing table.
      // User has wrong db name or has not created tables.
      // Note: cannot translate this since we have not included
      // translate.php yet.
      dbi_free_result ( $res );
      die_miserable_death (
        "Error finding WebCalendar tables in database '$db_database' " .
        "using db login '$db_login' on db server '$db_host'.<br/><br/>\n" .
        "Have you created the database tables as specified in the " .
        "<a href=\"docs/WebCalendar-SysAdmin.html\" target=\"other\">WebCalendar " .
        "System Administrator's Guide</a>?" );
    }
  } else {
    // Error accessing table.
    // User has wrong db name or has not created tables.
    // Note: cannot translate this since we have not included translate.php yet.
    die_miserable_death (
      "Error finding WebCalendar tables in database '$db_database' " .
      "using db login '$db_login' on db server '$db_host'.<br/><br/>\n" .
      "Have you created the database tables as specified in the " .
      "<a href=\"docs/WebCalendar-SysAdmin.html\" target=\"other\">WebCalendar " .
      "System Administrator's Guide</a>?" );
  }
}

$validate_redirect = false;
$session_not_found = false;

// Catch-all for getting the username when using HTTP-authentication
if ( $use_http_auth ) {
  if ( empty ( $PHP_AUTH_USER ) ) {
    if ( !empty ( $_SERVER ) && isset ( $_SERVER['PHP_AUTH_USER'] ) ) {
      $PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER'];
    } else if ( !empty ( $HTTP_SERVER_VARS ) &&
      isset ( $HTTP_SERVER_VARS['PHP_AUTH_USER'] ) ) {
      $PHP_AUTH_USER = $HTTP_SERVER_VARS['PHP_AUTH_USER'];
    } else if ( isset ( $REMOTE_USER ) ) {
      $PHP_AUTH_USER = $REMOTE_USER;
    } else if ( !empty ( $_ENV ) && isset ( $_ENV['REMOTE_USER'] ) ) {
      $PHP_AUTH_USER = $_ENV['REMOTE_USER'];
    } else if ( !empty ( $HTTP_ENV_VARS ) &&
      isset ( $HTTP_ENV_VARS['REMOTE_USER'] ) ) {
      $PHP_AUTH_USER = $HTTP_ENV_VARS['REMOTE_USER'];
    } else if ( @getenv ( 'REMOTE_USER' ) ) {
      $PHP_AUTH_USER = getenv ( 'REMOTE_USER' );
    } else if ( isset ( $AUTH_USER ) ) {
      $PHP_AUTH_USER = $AUTH_USER;
    } else if ( !empty ( $_ENV ) && isset ( $_ENV['AUTH_USER'] ) ) {
      $PHP_AUTH_USER = $_ENV['AUTH_USER'];
    } else if ( !empty ( $HTTP_ENV_VARS ) &&
      isset ( $HTTP_ENV_VARS['AUTH_USER'] ) ) {
      $PHP_AUTH_USER = $HTTP_ENV_VARS['AUTH_USER'];
    } else if ( @getenv ( 'AUTH_USER' ) ) {
      $PHP_AUTH_USER = getenv ( 'AUTH_USER' );
    }
  }
}

if ( $single_user == "Y" ) {
  $login = $single_user_login;
} else {
  if ( $use_http_auth ) {
    // HTTP server did validation for us....
    if ( empty ( $PHP_AUTH_USER ) )
      $session_not_found = true;
    else
      $login = $PHP_AUTH_USER;

  } elseif ( substr($user_inc,0,9) == 'user-app-' ) {
    // Use another application's authentication
    if (! $login = user_logged_in()) app_login_screen(clean_whitespace($login_return_path));
  
  } else {
    if ( ! empty ( $settings['session'] ) && $settings['session'] == 'php' ) {
      session_start ();
      if ( ! empty ( $_SESSION['webcalendar_session'] ) ) {
        $webcalendar_session = $_SESSION['webcalendar_session'];
      }
    }
    // We can't actually check the database yet since we haven't connected
    // to the database.  That happens in connect.php.

    // Check for session.  If not found, then note it for later
    // handling in connect.php.
    else if ( empty ( $webcalendar_session ) && empty ( $login ) ) {
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
          // Security fix.  Don't allow certain types of characters in
          // the login.  WebCalendar does not escape the login name in
          // SQL requests.  So, if the user were able to set the login
          // name to be "x';drop table u;",
          // they may be able to affect the database.
          if ( ! empty ( $login ) ) {
            if ( $login != addslashes ( $login ) ) {
              die_miserable_death ( "Illegal characters in login " .
                "<tt>" . htmlentities ( $login ) . "</tt>" );
            }
          }
          // make sure we are connected to the database for password check
          $c = @dbi_connect ( $db_host, $db_login, $db_password, $db_database );
          if ( ! $c ) {
            die_miserable_death (
              "Error connecting to database:<blockquote>" .
              dbi_error () . "</blockquote>\n" );
          }
          doDbSanityCheck ();

          if (!user_valid_crypt($login, $cryptpw)) {
            do_debug ( "User not logged in; redirecting to login page" );
            if ( empty ( $login_return_path ) )
              do_redirect ( "login.php" );
            else
              do_redirect ( "login.php?return_path=$login_return_path" );
          }

          do_debug ( "Decoded login from cookie: $login" );
        }
      }
    }
  }
}
?>
