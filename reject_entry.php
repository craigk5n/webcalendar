<?php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
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
  // Get list of participants
  $sql = "SELECT cal_login FROM webcal_entry_user WHERE cal_id = $id and cal_status = 'A'";
  //echo $sql."<BR>";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) )
      $partlogin[] = $row[0];
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
    // does this user want email for this?
    $sendmail = get_pref_setting ( $partlogin[$i],
      "EMAIL_EVENT_REJECTED" );
    user_load_variables ( $partlogin[$i], "temp" );
    if ( $sendmail == "Y" && strlen ( $tempemail ) ) {
      $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
        translate("An appointment has been rejected by") .
        " " . $login_fullname .  ". " .
        translate("The subject was") . " \"" . $name . "\"\n\n";
 
      $from = $email_fallback_from;
      if ( strlen ( $login_email ) )
        $from = $login_email;

      $extra_hdrs = "From: $from\nX-Mailer: " . translate("Title");

      mail ( $tempemail,
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
