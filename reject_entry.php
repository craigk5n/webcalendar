<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();

include "includes/translate.inc";

$error = "";

if ( $id > 0 ) {
  if ( ! dbi_query ( "UPDATE webcal_entry_user SET cal_status = 'R' " .
    "WHERE cal_login = '$login' AND cal_id = $id" ) ) {
    $error = translate("Error approving event") . ": " . dbi_error ();
  }

  // Email participants to notify that it was rejected.
  $sql = "SELECT webcal_entry_user.cal_login, webcal_user.cal_lastname, " .
    "webcal_user.cal_email " .
    "FROM webcal_entry_user, webcal_user " .
    "WHERE webcal_entry_user.cal_id = $id AND " .
    "webcal_entry_user.cal_login = webcal_user.cal_login ";
  //echo $sql."<BR>";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] != $login ) {
	$partlogin[] = $row[0];
	$partname[] = $row[1];
	$partemail[] = $row[2];
      }
      if ( $row[0] == $login ) {
	$rejname = $row[1];
	$rejemail = $row[2];
      }
    }
    dbi_free_result($res);
  }   
  
  // Get the name of the event
  $sql = "SELECT cal_name FROM webcal_entry WHERE cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $name = $row[0];
    dbi_free_result ( $res );
  }

  for ( $i = 0; $i < count ( $partlogin ); $i++ ) {
    $msg = translate("Hello") . ", " . $partname[$i] . ".\n\n" .
      translate("An appointment has been rejected by") .
      " " . $rejname .  ". " .
      translate("The subject was") . " \"" . $name . "\"\n\n";
 
    if ( strlen ( $rejemail ) )
      $extra_hdrs = "From: $rejemail\nX-Mailer: " . translate("Title");
    else
      $extra_hdrs = "X-Mailer: " . translate("Title");

    mail ( $partemail[$i],
      translate("Title") . " " . translate("Notification") . ": " . $name,
      $msg, $extra_hdrs );
  }
  

}

if ( $ret == "list" )
  do_redirect ( "list_unapproved.php" );
else
  do_redirect ( "view_entry.php?id=$id" );
?>
