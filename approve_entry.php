<?php
/* $Id$ */
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class' );

load_user_categories ();

$error = '';

if ( $readonly == 'Y' )
  $error = print_not_auth ();
// give user a change to add comments to approval email
if ( ! empty ( $_POST ) ) {
  $comments = getPostValue ( 'comments' );
  $cancel = getPostValue ( 'cancel' );
} else
if ( empty ( $ret ) ) {
  $q_string = ( ! empty ( $_SERVER['QUERY_STRING'] )
    ? '?' . $_SERVER['QUERY_STRING'] : '' );

  print_header ();
  echo '
    <form action="approve_entry.php' . $q_string
   . '" method="post" name="add_comments" >
      <table border="0" cellspacing="5">
        <tr>
          <td align="center" valign="bottom"><h3>'
   . translate ( 'Additional Comments (optional)' ) . '</h3></td>
        <tr>
        <tr>
          <td align="center"><textarea name="comments" rows="5" '
   . 'cols="60"></textarea></td>
        </tr>
        <tr>
          <td align="center">
            <input type="submit" value="' . translate ( 'Approve and Send' )
   . '" />&nbsp;&nbsp;&nbsp;
            <input type="submit" value="' . translate ( 'Approve and Exit' )
   . '" />
          </td>
        </tr>
        <tr>
          <td>('
   . translate ( 'Your comments will be included in an email to the event creator.' )
   . ')</td>
        </tr>
      </table>
    </form>
  </body>
</html>
';
  exit;
}
// Allow administrators to approve public events
$app_user = ( $PUBLIC_ACCESS == 'Y' && ! empty ( $public ) && $is_admin
  ? '__public__' : ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
// If User Access Control is enabled, we check to see if they are
// allowed to approve for the specified user.
if ( access_is_enabled () && ! empty ( $user ) && $user != $login &&
    access_user_calendar ( 'approve', $user ) )
  $app_user = $user;

if ( empty ( $error ) && $id > 0 )
  update_status ( 'A', $app_user, $id, $type );

if ( ! empty ( $comments ) && empty ( $cancel ) ) {
  $mail = new WebCalMailer;
  // Email event creator to notify that it was approved with comments.
  // Get the name of the event
  $res = dbi_execute ( 'SELECT cal_name, cal_description, cal_date, cal_time,
    cal_create_by FROM webcal_entry WHERE cal_id = ?', array ( $id ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $name = $row[0];
    $description = $row[1];
    $fmtdate = $row[2];
    $time = sprintf ( "%06d", $row[3] );
    $creator = $row[4];
    dbi_free_result ( $res );
  }

  $eventstart = date_to_epoch ( $fmtdate . $time );
  // TODO figure out if creator wants approved comment email
  // check UAC
  $send_user_mail = ( access_is_enabled ()
    ? access_user_calendar ( 'email', $creator, $login ) : 'Y' );

  $htmlmail = get_pref_setting ( $creator, 'EMAIL_HTML' );
  user_load_variables ( $creator, 'temp' );
  $user_TIMEZONE = get_pref_setting ( $creator, 'TIMEZONE' );
  set_env ( 'TZ', $user_TIMEZONE );
  $user_language = get_pref_setting ( $creator, 'LANGUAGE' );
  if ( $send_user_mail == 'Y' && strlen ( $tempemail ) && $SEND_EMAIL != 'N' ) {
    reset_language ( empty ( $user_language ) || ( $user_language == 'none' )
      ? $LANGUAGE : $user_language );

    $msg = translate ( 'Hello' ) . ", $tempfullname.\n\n"
     . translate ( 'An appointment has been approved and comments added by' )
     . " $login_fullname.\n\n" . translate ( 'The subject was' ) . " \"$name\"\n"
     . translate ( 'The description is' ) . " \"$description\"\n"
     . translate ( 'Date' ) . ': ' . date_to_str ( $fmtdate ) . "\n"
     . ( empty ( $hour ) && empty ( $minute ) ? '' : translate ( 'Time' )
       . ': ' . // Display using user's GMT offset and display TZID
      display_time ( '', 2, $eventstart,
        get_pref_setting ( $creator, 'TIME_FORMAT' ) ) ) . "\n";
    if ( ! empty ( $SERVER_URL ) ) {
      // DON'T change & to &amp; here. email will handle it
      $url = $SERVER_URL . 'view_entry.php?id=' . $id . '&em=1';
      if ( $htmlmail == 'Y' )
        $url = activate_urls ( $url );

      $msg .= "\n" . $url;
    }
    if ( ! empty ( $comments ) )
      $msg .= "\n\n" . translate ( 'Comments' ) . ': ' . $comments;

    $from = ( strlen ( $login_email ) ? $login_email : $EMAIL_FALLBACK_FROM );

    if ( strlen ( $from ) ) {
      $mail->From = $from;
      $mail->FromName = $login_fullname;
    } else
      $mail->From = $login_fullname;

    $mail->IsHTML ( $htmlmail == 'Y' ? true : false );
    $mail->AddAddress ( $tempemail, $tempfullname );
    $mail->WCSubject ( $name );
    $mail->Body = $htmlmail == 'Y' ? nl2br ( $msg ) : $msg;
    $mail->Send ();
    $mail->ClearAll ();

    activity_log ( $id, $login, $creator, LOG_NOTIFICATION,
      'Approved w/Comments by ' . $app_user );
  }
}
// return to login TIMEZONE
set_env ( 'TZ', $TIMEZONE );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  do_redirect ( ! empty ( $ret ) && $ret == 'listall'
    ? 'list_unapproved.php'
    : ( ( ! empty ( $ret ) && $ret == 'list'
        ? 'list_unapproved.php?'
        : 'view_entry.php?id=' . $id . '&amp;' ) . 'user=' . $app_user ) );
  exit;
}
// process errors
$mail->MailError ( $mailerError, $error );

?>
