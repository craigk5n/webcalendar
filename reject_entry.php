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
  $sql = "SELECT webcal_entry_user.cal_login, webcal_user.cal_firstname, " .
    "webcal_user.cal_lastname, webcal_user.cal_email " .
    "FROM webcal_entry_user, webcal_user " .
    "WHERE webcal_entry_user.cal_id = $id AND " .
    "webcal_entry_user.cal_login = webcal_user.cal_login ";
  //echo $sql."<BR>";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] != $login ) {
	$partlogin[] = $row[0];
        if ( strlen ( $row[1] ) && strlen ( $row[2] ) )
	  $partname[] = "$row[1] $row[2] ($row[0])";
        else
	  $partname[] = $row[0];
	$partemail[] = $row[3];
      }
      if ( $row[0] == $login ) {
        if ( strlen ( $row[1] ) && strlen ( $row[2] ) )
	  $rejname = "$row[1] $row[2] ($row[0])";
        else
	  $rejname = $row[0];
	$rejemail = $row[3];
      }
    }
    dbi_free_result($res);
  }   

  // find out which want email for this
  for ( $i = 0; $i < count ( $partlogin ); $i++ ) {
    $sendmail[$i] = get_pref_setting ( $partlogin[$i],
      "EMAIL_EVENT_REJECTED" );
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
    if ( $sendmail[$i] == "Y" ) {
      $msg = translate("Hello") . ", " . $partname[$i] . ".\n\n" .
        translate("An appointment has been rejected by") .
        " " . $rejname .  ". " .
        translate("The subject was") . " \"" . $name . "\"\n\n";
 
      $from = $GLOBALS["email_fallback_from"];
      if ( strlen ( $rejemail ) )
        $from = rejemail;

      $extra_hdrs = "From: $from\nX-Mailer: " . translate("Title");

      mail ( $partemail[$i],
        translate("Title") . " " . translate("Notification") . ": " . $name,
        $msg, $extra_hdrs );
    }
  }
  

}

if ( $ret == "list" )
  do_redirect ( "list_unapproved.php" );
else
  do_redirect ( "view_entry.php?id=$id" );
?>
