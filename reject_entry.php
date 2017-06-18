<?php
/* $Id: reject_entry.php,v 1.62.2.5 2012/02/28 02:07:45 cknudsen Exp $ */
include_once 'includes/init.php';
require_valide_referring_url ();
require ( 'includes/classes/WebCalMailer.class' );
$mail = new WebCalMailer;

$error = '';

if ( $readonly == 'Y' )
  $error = print_not_auth (4);

//give user a change to add comments to rejection email
if ( ! empty ( $_POST ) ) {
  $comments = getPostValue ( 'comments' );
} else {
   $q_string = ( ! empty ( $_SERVER['QUERY_STRING'] ) ?  '?'. $_SERVER['QUERY_STRING'] : '' );

   print_header ();
   echo "<form action=\"reject_entry.php$q_string\" method=\"post\" name=\"add_comments\" >\n";
   echo "<table border=\"0\" cellspacing=\"5\">\n" .
     "<tr><td align=\"center\" valign=\"bottom\"><h3>" .
     translate ( 'Additional Comments (optional)' ) . "</h3></td><tr>\n";
   echo "<tr><td align=\"center\">" .
     "<textarea name=\"comments\" rows=\"5\" cols=\"60\" ></textarea></td></tr>\n";
   echo "<tr><td align=\"center\"><input type=\"submit\" value=\"" .
     translate ( 'Continue' ) . "\" /></tr></tr>\n<tr><td>";
   etranslate ( '(Your comments will be emailed to the other participants.)' );
   echo "</td></tr></table></form>\n";
   echo "</body>\n</html>";
   exit;
}

$user = getValue ( 'user' );
$id = getValue ( 'id' );

// Allow administrators to approve public events
if ( $PUBLIC_ACCESS == 'Y' && ! empty ( $public ) && $is_admin )
  $app_user = '__public__';
else
  $app_user = ( $is_assistant || $is_nonuser_admin ? $user : $login );

// If User Access Control is enabled, we check to see if they are
// allowed to approve for the specified user.
if ( access_is_enabled () && ! empty ( $user ) &&
  $user != $login ) {
  if ( access_user_calendar ( 'approve', $user ) )
    $app_user = $user;
}

$view_type = 'view_entry';
$type = getGetValue ( 'type' );

if ( empty ( $error ) && $id > 0 ) {
  update_status ( 'R', $app_user, $id, $type );

  // Email participants to notify that it was rejected.
  // Get list of participants
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_entry_user
    WHERE cal_id = ? and cal_status = \'A\'', array ( $id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) )
      $partlogin[] = $row[0];
    dbi_free_result ( $res );
  }

  // Get the name of the event
  $res = dbi_execute ( 'SELECT cal_name, cal_description, cal_date, cal_time
    FROM webcal_entry WHERE cal_id = ?', array ( $id ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $name = $row[0];
    $description = $row[1];
    $fmtdate = $row[2];
    $time = sprintf ( "%06d", $row[3] );
    dbi_free_result ( $res );
  }

  $eventstart = date_to_epoch ( $fmtdate . $time );
  for ( $i = 0, $cnt = count ( $partlogin ); $i < $cnt; $i++ ) {
    // does this user want email for this?
    $send_user_mail = get_pref_setting ( $partlogin[$i],
      'EMAIL_EVENT_REJECTED' );
    //check UAC
    $can_mail = 'Y';
    if ( access_is_enabled () ) {
      $can_mail = access_user_calendar ( 'email', $partlogin[$i], $login);
    }
    $htmlmail = get_pref_setting ( $partlogin[$i], 'EMAIL_HTML' );
    $t_format = get_pref_setting ( $partlogin[$i], 'TIME_FORMAT' );
    user_load_variables ( $partlogin[$i], 'temp' );
    $user_TIMEZONE = get_pref_setting ( $partlogin[$i], 'TIMEZONE' );
    set_env ( 'TZ', $user_TIMEZONE);
    $user_language = get_pref_setting ( $partlogin[$i], 'LANGUAGE' );
    if ( $send_user_mail == 'Y' && strlen ( $tempemail ) &&
      $SEND_EMAIL != 'N' && $can_mail == 'Y') {
      if ( empty ( $user_language ) || ( $user_language == 'none' )) {
        reset_language ( $LANGUAGE );
      } else {
        reset_language ( $user_language );
      }
      $msg = translate ( 'Hello' ) . ', ' . $tempfullname . ".\n\n" .
      translate ( 'An appointment has been rejected by' ) .
      ' ' . $login_fullname . ".\n\n" .
      translate ( 'The subject was' ) . ' "' . $name . " \"\n" .
      translate ( 'The description is' ) . ' "' . $description . "\"\n" .
      translate ( 'Date' ) . ': ' . date_to_str ( $fmtdate ) . "\n" .
      ( ( empty ( $hour ) && empty ( $minute ) ? '' : translate ( 'Time' ) . ': ' .
      // Display using user's TIMEZONE and display TZID
      display_time ( '', 2, $eventstart, $t_format ) ) ). "\n";
      if ( ! empty ( $SERVER_URL ) ) {
        //DON'T change & to &amp; here. email will handle it
        $url = $SERVER_URL . $view_type . '.php?id=' . $id . '&em=1';
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
      activity_log ( $id, $login, $partlogin[$i], LOG_NOTIFICATION,
        "Rejected by $app_user" );
    }
  }
}
//return to login TIMEZONE
set_env ( 'TZ', $TIMEZONE );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  if ( ! empty ( $ret ) && $ret == 'listall' )
    do_redirect ( 'list_unapproved.php' );
  else if ( ! empty ( $ret ) &&  $ret == 'list' )
    do_redirect ( "list_unapproved.php?user=$app_user" );
  else
    do_redirect ( $view_type . ".php?id=$id&amp;user=$app_user" );
  exit;
}
//process errors
$mail->MailError ( $mailerError, $error ); ?>
