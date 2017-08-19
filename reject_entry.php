<?php
include_once 'includes/init.php';
require_valid_referring_url ();
require ( 'includes/classes/WebCalMailer.class' );
$mail = new WebCalMailer;

$error = '';

if ( $readonly == 'Y' )
  $error = print_not_auth();

// Give user a chance to add comments to rejection email.
if ( ! empty ( $_POST ) )
  $comments = getPostValue ( 'comments' );
else {
  print_header();
  echo '
    <form action="reject_entry.php'
   . ( empty ( $_SERVER['QUERY_STRING'] ) ? '' : '?' . $_SERVER['QUERY_STRING'] )
   . '" method="post" name="add_comments">
      <table cellspacing="5">
        <tr>
          <td class="aligncenter alignbottom"><h3>'
   . translate ( 'Additional Comments (optional)' ) . '</h3></td>
        </tr>
        <tr>
          <td class="aligncenter"><textarea name="comments" rows="5" cols="60">'
   . '</textarea></td>
        </tr>
        <tr>
          <td class="aligncenter"><input type="submit" value="'
   . translate ( 'Continue' ) . '" /></td>
        </tr>
        <tr>
          <td>'
   . translate ( '(Your comments will be emailed to the other participants.)' )
   . '</td>
        </tr>
      </table>
    </form>
  </body>
</html>';
  exit;
}

$user = getValue ( 'user' );
$id = getValue ( 'id' );

// Allow administrators to approve public events.
$app_user = ( $PUBLIC_ACCESS == 'Y' && ! empty ( $public ) && $is_admin
  ? '__public__' : ( $is_assistant || $is_nonuser_admin ? $user : $login ) );

// If User Access Control is enabled,
// we check to see if they are allowed to approve for the specified user.
if ( access_is_enabled() && ! empty ( $user ) && $user != $login ) {
  if ( access_user_calendar ( 'approve', $user ) )
    $app_user = $user;
}

if ( empty ( $error ) && $id > 0 ) {
  update_status ( 'R', $app_user, $id, getGetValue ( 'type' ) );

  // Email participants to notify that it was rejected.
  // Get list of participants.
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_entry_user
  WHERE cal_id = ?
    AND cal_status = "A"', [$id] );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $partlogin[] = $row[0];
    }
    dbi_free_result ( $res );
  }

  // Get the name of the event.
  $res = dbi_execute ( 'SELECT cal_name, cal_description, cal_date, cal_time
  FROM webcal_entry
  WHERE cal_id = ?', [$id] );
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
    // Does this user want email for this?
    $send_user_mail = get_pref_setting ( $partlogin[$i],
      'EMAIL_EVENT_REJECTED' );
    // Check UAC.
    $can_mail = 'Y';
    if ( access_is_enabled() )
      $can_mail = access_user_calendar ( 'email', $partlogin[$i], $login );

    $htmlmail = get_pref_setting ( $partlogin[$i], 'EMAIL_HTML' );
    $t_format = get_pref_setting ( $partlogin[$i], 'TIME_FORMAT' );
    user_load_variables ( $partlogin[$i], 'temp' );
    $user_TIMEZONE = get_pref_setting ( $partlogin[$i], 'TIMEZONE' );
    set_env ( 'TZ', $user_TIMEZONE );
    $user_language = get_pref_setting ( $partlogin[$i], 'LANGUAGE' );
    if ( $send_user_mail == 'Y' &&
      strlen ( $tempemail ) && $SEND_EMAIL != 'N' && $can_mail == 'Y' ) {
      reset_language ( empty ( $user_language ) || $user_language == 'none'
        ? $LANGUAGE : $user_language );

      $msg =
      str_replace ( 'XXX', $tempfullname, translate ( 'Hello, XXX.' ) ) . '

' . str_replace ( 'XXX', $login_fullname,
        translate ( 'XXX has rejected an appointment.' ) ) . '

' . str_replace ( 'XXX', $name, translate ( 'Subject XXX' ) ) . '
' . str_replace ( 'XXX', $description, translate ( 'Description XXX' ) ) . '
' . str_replace ( 'XXX', translate ( date_to_str ( $fmtdate ), 'N' ),
        translate ( 'Date XXX' ) ) . '
' . ( empty ( $hour ) && empty ( $minute ) ? ''
        : // Display using user's TIMEZONE and display TZID.
        str_replace ( 'XXX',
          translate ( display_time ( '', 2, $eventstart, $t_format ), 'N' ),
          translate ( 'Time XXX' ) ) );
      if ( ! empty ( $SERVER_URL ) ) {
        // DON'T change & to &amp; here. Email will handle it.
        $url = $SERVER_URL . 'view_entry.php?id=' . $id . '&em=1';
        $msg .= '

' . ( $htmlmail == 'Y' ? activate_urls ( $url ) : $url );
      }
      if ( strlen ( $comments ) )
        $msg .= '

' . str_replace ( 'XXX', $comments, translate ( 'Comments XXX' ) );

      $from = $EMAIL_FALLBACK_FROM;
      if ( strlen ( $login_email ) )
        $from = $login_email;

      // Send via WebCalMailer class.
      $mail->WC_Send ( $login_fullname, $tempemail,
        $tempfullname, $name, $msg, $htmlmail, $from );
      activity_log ( $id, $login, $partlogin[$i], LOG_NOTIFICATION,
        str_replace ( 'XXX', $app_user, translate ( 'Rejected by XXX.' ) ) );
    }
  }
}

// Return to login TIMEZONE.
set_env ( 'TZ', $TIMEZONE );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  if ( ! empty ( $ret ) && $ret == 'listall' )
    do_redirect ( 'list_unapproved.php' );
  else
    do_redirect ( ( ! empty ( $ret ) && $ret == 'list'
        ? 'list_unapproved.php?' : 'view_entry.php?id=' . $id . '&amp;' )
       . 'user=' . $app_user );

  exit;
}

// Process errors.
$mail->MailError ( $mailerError, $error );

?>
