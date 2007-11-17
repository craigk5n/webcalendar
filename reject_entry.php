<?php
/* $Id$ */
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class.php' );
$mail = new WebCalMailer;

$error = '';

if ( _WC_READONLY ) {
  $error = print_not_auth ();
}

//give user a change to add comments to rejection email
if ( ! empty ( $_POST ) ) {
  $comments = $WC->getPOST ( 'comments' ); 
} else {
   $q_string = ( ! empty ( $_SERVER['QUERY_STRING'] ) ?  '?'. $_SERVER['QUERY_STRING'] : '' );

   build_header ();
   echo "<form action=\"reject_entry.php$q_string\" method=\"post\" name=\"add_comments\" >\n";
   echo '<table border="0" cellspacing="5" summary="">' . "\n" .
     "<tr><td align=\"center\" valign=\"bottom\"><h3>" . 
     translate ( 'Additional Comments (optional)' ) . "</h3></td><tr>\n";
   echo "<tr><td align=\"center\">" .
     "<textarea name=\"comments\" rows=\"5\" cols=\"60\" ></textarea></td></tr>\n";
   echo "<tr><td align=\"center\"><input type=\"submit\" value=\"" . 
     translate ( 'Continue' ) . "\" /></tr></tr>\n<tr><td>";
   etranslate ( '(Your comments will be included in an email to the other participants)' );
   echo "</td></tr></table></form>\n"; 
   echo "</body>\n</html>";
   exit;
}
$app_user = ( $WC->isNonuserAdmin() ? $WC->userId() : $WC->loginId() );

// If User Access Control is enabled, we check to see if they are
// allowed to approve for the specified user.
if (! empty ( $user ) && ! $WC->isLogin( $user )   &&
  access_user_calendar ( 'approve', $user ) ) {
  $app_user = $user;
}

$view_type = 'view_entry';
$type = $WC->getGET ( 'type' );
$default_language = getPref ( 'LANGUAGE', 2 );
if ( empty ( $error ) && $eid > 0 ) {
  update_status ( 'R', $app_user, $eid, $type );

  // Email participants to notify that it was rejected.
  // Get list of participants
  $sql = "SELECT cal_login FROM webcal_entry_user WHERE cal_id = ? and cal_status = 'A'";
  //echo $sql."<br />";
  $res = dbi_execute ( $sql, array ( $eid ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) )
      $partlogin[] = $row[0];
    dbi_free_result ( $res );
  }

  // Get the name of the event
  $sql = 'SELECT cal_name, cal_description, cal_date ' .
    'FROM webcal_entry WHERE cal_id = ?';
  $res = dbi_execute ( $sql, array ( $eid ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $name = $row[0];
    $description = $row[1];
    $eventstart = $row[2];
    dbi_free_result ( $res );
  }

  for ( $i = 0, $cnt = count ( $partlogin ); $i < $cnt; $i++ ) {
    // does this user want email for this?
    $send_user_mail = getPref ( 'EMAIL_EVENT_REJCTED', 1, $partlogin[$i] );
    //check UAC
    $can_mail = access_user_calendar ( 'email', $partlogin[$i], 
	  $WC->loginId());
    $htmlmail = getPref ( 'EMAIL_HTML', 1, $partlogin[$i] );
    $t_format = getPref ( 'TIME_FORMAT', 1, $partlogin[$i] );
    $WC->User->loadVariables ( $partlogin[$i], 'temp' );
    $user_TIMEZONE = getPref ( 'TIMEZONE', 1, $partlogin[$i] );
    set_env ( 'TZ', $user_TIMEZONE);
    $user_language = getPref ( 'LANGUAGE', 1, $partlogin[$i] );
    if ( $send_user_mail == 'Y' && strlen ( $tempemail ) &&
      getPref ( 'SEND_EMAIL', 2 ) && $can_mail == 'Y') {
      if ( empty ( $user_language ) || ( $user_language == 'none' )) {
        reset_language ( $default_language );
      } else {
        reset_language ( $user_language );
      }
      $msg = translate( 'Hello' ) . ', ' . $tempfullname . ".\n\n" .
      translate( 'An appointment has been rejected by' ) .
      ' ' . $login_fullname . ".\n\n" .
      translate( 'The subject was' ) . ' "' . $name . " \"\n" .
      translate( 'The description is' ) . ' "' . $description . "\"\n" .
      translate( 'Date' ) . ': ' . date_to_str ( $fmtdate ) . "\n" .
      ( ( empty ( $hour ) && empty ( $minute ) ? '' : translate( 'Time' ) . ': ' .
      // Display using user's TIMEZONE and display TZID
      display_time ( $eventstart, 2, $t_format ) ) ). "\n";
      if ( $server_url = getPref ( 'SERVER_URL', 2 ) ) {
        //DON'T change & to &amp; here. email will handle it
        $url = $server_url .  $view_type . '.php?eid=' .  $eid . '&em=1';
        if ( $htmlmail == 'Y' ) {
          $url =  activate_urls ( $url ); 
        }
        $msg .= "\n" . $url;
      }
      if ( strlen ( $comments ) ) {
        $msg .= "\n\n" . translate ( 'Comments' ) . ': ' . $comments;
      }
      $from = $EMAIL_FALLBACK_FROM;
      if ( strlen ( $login_email ) ) $from = $login_email;
      //send via WebCalMailer class
      $mail->WC_Send ( $login_fullname, $tempemail, 
        $tempfullname, $name, $msg, $htmlmail, $from );
      activity_log ( $eid, $WC->loginId(), $partlogin[$i], LOG_NOTIFICATION,
        "Rejected by $app_user" );
    }
  }
}
//return to login TIMEZONE
set_env ( 'TZ', getPref ( 'TIMEZONE' ) );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  if ( ! empty ( $ret ) && $ret == 'listall' )
    do_redirect ( 'list_unapproved.php' );
  else if ( ! empty ( $ret ) &&  $ret == 'list' )
    do_redirect ( "list_unapproved.php?user=$app_user" );
  else
    do_redirect ( $view_type . ".php?eid=$eid&amp;user=$app_user" );
  exit;
}
//process errors
$mail->MailError ( $mailerError, $error ); ?>
