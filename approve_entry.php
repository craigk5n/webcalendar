<?php
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class' );

load_user_categories();

$error = "";

if ( $readonly == 'Y' ) {
  $error = translate("You are not authorized");
}

//give user a change to add comments to approval email
if ( ! empty ( $_POST ) ) {
  $comments = getPostValue ( 'comments' );
  $cancel   = getPostValue ( 'cancel' );   
} else {
   $q_string = $_SERVER['QUERY_STRING'];

   print_header ();
   echo "<form action=\"approve_entry.php?$q_string\" method=\"post\" name=\"add_comments\" >\n";
   echo "<table border=\"0\" cellspacing=\"5\">\n" .
     "<tr><td align=\"center\" valign=\"bottom\"><h3>" . 
     translate ( "Additional Comments (optional)" ) . "</h3></td><tr>\n";
   echo "<tr><td align=\"center\">" .
     "<textarea name=\"comments\" rows=\"5\" cols=\"60\" ></textarea></td></tr>\n";
   echo "<tr><td align=\"center\"><input type=\"submit\" value=\"" . 
     translate ( "Approve and Send" ) . "\" />&nbsp;&nbsp;&nbsp;";
   echo "<input type=\"submit\" value=\"" . 
     translate ( "Approve and Exit" ) . "\" /></tr></tr>\n<tr><td>";
   etranslate ( "(Your comments will be included in an email to the event creator)" );
   echo "</td></tr></table></form>\n"; 
   echo "</body>\n</html>";
   exit;
}

$view_type = "view_entry";  

// Allow administrators to approve public events
if ( $PUBLIC_ACCESS == "Y" && ! empty ( $public ) && $is_admin )
  $app_user = "__public__";
else
  $app_user = ( $is_assistant || $is_nonuser_admin ? $user : $login );

// If User Access Control is enabled, we check to see if they are
// allowed to approve for the specified user.
if ( access_is_enabled () && ! empty ( $user ) &&
  $user != $login ) {
  if ( access_user_calendar ( 'approve', $user ) )
    $app_user = $user;
}

if ( empty ( $error ) && $id > 0 ) {
  $approve_type = LOG_APPROVE; //used in activity log below
  // Update any extension events related to this one.
  $res = dbi_execute ( "SELECT cal_id, cal_type FROM webcal_entry " .
    "WHERE cal_ext_for_id = ?", array( $id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ext_id = $row[0];
      $approve_type = ( $row[1] == 'E' || $row[1] == 'M'? LOG_APPROVE : LOG_APPROVE_T ); 
      if ( ! dbi_execute ( "UPDATE webcal_entry_user SET cal_status = 'A' " .
        "WHERE cal_login = ? AND cal_id = ?", array( $app_user, $ext_id ) ) ) {
        $error = translate("Error approving event") . ": " . dbi_error ();
      }
    }
    dbi_free_result ( $res );
  }
  
  if ( ! dbi_execute ( "UPDATE webcal_entry_user SET cal_status = 'A' " .
    "WHERE cal_login = ? AND cal_id = ?", array( $app_user, $id ) ) ) {
    $error = translate("Error approving event") . ": " . dbi_error ();
  } else {
    activity_log ( $id, $login, $app_user, $approve_type, "" );
  }
}

if ( strlen ( $comments ) && empty ( $cancel ) ) {
  $mail = new WebCalMailer;
  // Email event creator to notify that it was approved with comments.
  // Get the name of the event
  $sql = "SELECT cal_name, cal_description, cal_date, cal_time, cal_create_by " .
    "FROM webcal_entry WHERE cal_id = ?";
  $res = dbi_execute ( $sql, array( $id ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $name = $row[0];
    $description = $row[1];
    $fmtdate = $row[2];
    $time = $row[3];
    $creator = $row[4];    
    dbi_free_result ( $res );
  }

  if ($time != '-1') {
    $hour = substr($time,0,2);
    $minute = substr($time,2,2);
  } else {
   $hour =  $minute = 0;
 }
  $eventstart = $fmtdate .  sprintf( "%06d", ( $hour * 10000 ) + ( $minute * 100 ) );
  //TODO figure out if creator wants approved comment email
		//check UAC
    $send_user_mail = "Y"; 
		if ( access_is_enabled () ) {
			$send_user_mail = access_user_calendar ( 'email', $creator, $login);
		}	 
    $htmlmail = get_pref_setting ( $creator, "EMAIL_HTML" );
    $t_format = get_pref_setting ( $creator, "TIME_FORMAT" );
    user_load_variables ( $creator, "temp" );
    $user_TIMEZONE = get_pref_setting ( $creator, "TIMEZONE" );
    $user_TZ = get_tz_offset ( $user_TIMEZONE, '', $eventstart );
    $user_language = get_pref_setting ( $creator, "LANGUAGE" );
    if ( $send_user_mail == "Y" && strlen ( $tempemail ) &&
      $SEND_EMAIL != "N" ) {
      if ( empty ( $user_language ) || ( $user_language == 'none' )) {
        reset_language ( $LANGUAGE );
      } else {
        reset_language ( $user_language );
      }
      $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
      translate("An appointment has been approved and comments added by") .
      " " . $login_fullname .  ".\n\n" .
      translate("The subject was") . " \"" . $name . " \"\n" .
      translate("The description is") . " \"" . $description . "\"\n" .
      translate("Date") . ": " . date_to_str ( $fmtdate ) . "\n" .
      ( ( empty ( $hour ) && empty ( $minute ) ? "" : translate("Time") . ": " .
      // Display using user's GMT offset and display TZID
      display_time ( $eventstart, 2, '' , $user_TIMEZONE, $t_format ) ) ). "\n";
      if ( ! empty ( $SERVER_URL ) ) {
        //DON'T change & to &amp; here. email will handle it
        $url = $SERVER_URL .  $view_type . ".php?id=" .  $id . "&em=1";
        if ( $htmlmail == 'Y' ) {
          $url =  activate_urls ( $url ); 
        }
        $msg .= "\n" . $url;
      }
      if ( strlen ( $comments ) ) {
        $msg .= "\n\n" . translate ( "Comments" ) . ": " . $comments;
      }
      $from = $EMAIL_FALLBACK_FROM;
      if ( strlen ( $login_email ) ) $from = $login_email;

      if ( strlen ( $from ) ) {
        $mail->From = $from;
        $mail->FromName = $login_fullname;
      } else {
        $mail->From = $login_fullname;
      }
      $mail->IsHTML( $htmlmail == 'Y' ? true : false );
      $mail->AddAddress( $tempemail, $tempfullname );
      $mail->WCSubject ( $name );
      $mail->Body  = $htmlmail == 'Y' ? nl2br ( $msg ) : $msg;
      $mail->Send();
      $mail->ClearAll();

      activity_log ( $id, $login, $creator, LOG_NOTIFICATION,
        "Approved w/Comments by $app_user" );
  }
}
if ( empty ( $error ) ) {
  if ( ! empty ( $ret ) && $ret == "listall" )
    do_redirect ( "list_unapproved.php" );
  else if ( ! empty ( $ret ) && $ret == "list" )
    do_redirect ( "list_unapproved.php?user=$app_user" );
  else
    do_redirect ( $view_type . ".php?id=$id&amp;user=$app_user" );
  exit;
}
print_header ();
echo "<h2>" . translate("Error") . "</h2>\n";
echo "<p>" . $error . "</p>\n";
print_trailer ();
?>
