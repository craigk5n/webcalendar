<?php

// db settings are in config.inc

// Establish a database connection.
$c = dbi_connect ( $db_host, $db_login, $db_password, $db_database );
if ( ! $c ) {
  echo "Error connecting to database:<BLOCKQUOTE>" .
    dbi_error () . "</BLOCKQUOTE>\n";
  exit;
}

//if ( ! dbi_select_db ( $db_database ) ) {
//  echo "Error selecting \"$db_database\" database!<P>" . dbi_error ();
//  exit;
//}

//if ( strlen ( $login ) && strstr ( $PHP_SELF, "index.php" ) < 0 ) {
if ( strlen ( $login ) == 0 && $use_http_auth ) {
  if ( strstr ( $PHP_SELF, "login.php" ) >= 0 ) {
    // ignore since login.php will redirect to index.php
  } else {
    send_http_login ();
  }
} else if ( strlen ( $login ) ) {
  if ( strstr ( $PHP_SELF, "login.php" ) ) {
    // ignore since login.php will redirect to index.php
  } else {
    user_load_variables ( $login, "login_" );
    if ( $login_login != "" ) {
      $is_admin =  ( $login_admin == "Y" ? true : false );
      $lastname = $login_lastname;
      $firstname = $login_firstname;
      $fullname = $login_fullname;
      $user_email = $login_email;
    } else {
      // Invalid login
      if ( $use_http_auth ) {
        send_http_login ();
      } else {
        // This shouldn't happed since login should be validated in validate.inc
        echo "Error getting user info for login \"$login\".";
        exit;
      }
    }
  }
}
//else if ( ! strlen ( $single_user_login ) ) {
//  echo "Error(3)! no login info found: " . dbi_error () . "<P><B>SQL:</B> $sql";
//  exit;
//}
?>
