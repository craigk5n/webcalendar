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
    $sql = "SELECT cal_lastname, cal_firstname, cal_is_admin, " .
      "cal_passwd, cal_email " .
      "FROM webcal_user WHERE cal_login = '$login'";
    $res = dbi_query ( $sql );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $use_http_auth && $row[3] != $PHP_AUTH_PW ) {
          send_http_login ();
        } else {
          $lastname = $row[0];
          $firstname = $row[1];
          $is_admin =  ( $row[2] == "Y" ? true : false );
          $user_email = $row[4];
        }
      } else if ( $use_http_auth ) {
        // this is an invalid login name and password
        send_http_login ();
      } else {
        echo "Error(1)! no login info found: " . dbi_error () . "<P><B>SQL:</B> $sql";
        exit;
      }
    } else {
      echo "Error(2)! no login info found: " . dbi_error () . "<P><B>SQL:</B> $sql";
      exit;
    }
  }
}
//else if ( ! strlen ( $single_user_login ) ) {
//  echo "Error(3)! no login info found: " . dbi_error () . "<P><B>SQL:</B> $sql";
//  exit;
//}
?>
