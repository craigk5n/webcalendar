<?php
/* $Id$ */
include_once 'includes/init.php';
require ( 'includes/classes/WebCalMailer.class.php' );

$error = '';

if ( _WC_READONLY )
  $error = print_not_auth ();
// Give user a chance to add comments to approval email.
if ( ! empty ( $_POST ) ) {
  $comments = $WC->getPOST ( 'comments' );
  $cancel = $WC->getPOST ( 'cancel' );
} else
if ( empty ( $ret ) ) {
  $q_string = ( ! empty ( $_SERVER['QUERY_STRING'] )
    ? '?' . $_SERVER['QUERY_STRING'] : '' );

  build_header ();
  echo '
    <form action="approve_entry.php' . $q_string
   . '" method="post" name="add_comments">
      <table border="0" cellspacing="5" summary="">
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
  // translate( 'Your comments will be included in an email to the event creator.' )
  . str_replace ( 'XXX', translate ( 'event creator' ),
    translate ( 'Your comments will be emailed to the XXX.' ) )
   . ')</td>
        </tr>
      </table>
    </form>
  </body>
</html>
';
  exit;
}

// If User Access Control is enabled, we check to see if they are
// allowed to approve for the specified user.
if ( ! $WC->isLogin() &&
  access_user_calendar ( 'approve', $WC->userId()) )
  $app_user = $user;

if ( empty ( $error ) && $eid > 0 )
  update_status ( 'A', $app_user, $eid, $type );

if ( ! empty ( $comments ) && empty ( $cancel ) ) {
  $mail = new WebCalMailer;
  // Email event creator to notify that it was approved with comments.
  // Get the name of the event.
  $res = dbi_execute ( 'SELECT cal_name, cal_description, cal_date,
    cal_create_by FROM webcal_entry WHERE cal_id = ?', array ( $eid ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $name = $row[0];
    $description = $row[1];
    $eventstart = $row[2];
    $creator = $row[3];
    dbi_free_result ( $res );
  }

  // TODO figure out if creator wants approved comment email.
  // Check UAC.
  $send_user_mail = access_user_calendar ( 'email', $creator, $WC->loginId() );

  $htmlmail = getPref ( 'EMAIL_HTML', 1, $creator );
  $WC->User->loadVariables ( $creator, 'temp' );
  $user_TIMEZONE = getPref ( 'TIMEZONE', 1, $creator );
  set_env ( 'TZ', $user_TIMEZONE );
  $default_language = getPref ( 'LANGUAGE', 2 );
  $user_language = getPref ( 'LANGUAGE', 1, $creator );
  if ( $send_user_mail == 'Y' && strlen ( $tempemail ) && getPref ( '_SEND_EMAIL', 2 ) ) {
    reset_language ( empty ( $user_language ) || ( $user_language == 'Browser-defined' )
      ? $default_language : $user_language );

    // translate ( 'Hello' )
    $msg = str_replace ( 'XXX', $tempfullname, translate ( 'Hello, XXX.' ) )
    // translate ( 'An appointment has been approved and comments added by' )
    . "\n\n" . str_replace ( 'XXX', $login_fullname,
      translate ( 'XXX has approved appointment and added comments' ) ) . "\n\n"
    // translate ( 'The subject was' )
    . str_replace ( 'XXX', $name, translate ( 'Subject XXX' ) ) . "\n"
    // translate ( 'The description is' )
    . str_replace ( 'XXX', $description, translate ( 'Description XXX' ) ) . "\n"
    // translate ( 'Date' )
    . str_replace ( 'XXX', date_to_str ( $fmtdate ), translate ( 'Date XXX' ) )
    // translate ( 'Time' )
    . ' ' . ( empty ( $hour ) && empty ( $minute )
      ? '' : str_replace ( 'XXX',
        // Display using user's GMT offset and display TZID.
        display_time ( $eventstart, 2,
          getPref ( 'TIME_FORMAT', 1, $creator ) ),
        translate ( 'Time XXX' ) ) ) . "\n";

    if ( $server_url = getPref ( 'SERVER_URL', 2 ) ) {
      // DON'T change & to &amp; here. email will handle it
      $url = $server_url . 'view_entry.php?eid=' . $eid . '&em=1';
      if ( $htmlmail == 'Y' )
        $url = activate_urls ( $url );

      $msg .= "\n" . $url;
    }
    if ( ! empty ( $comments ) )
      // translate ( 'Comments' )
      $msg .= "\n\n" . str_replace ( 'XXX', $comments,
        translate ( 'Comments XXX' ) );

    $from = ( strlen ( $login_email ) ? $login_email : 
	  getPref ('_EMAIL_FALLBACK_FROM' ) );
    // Send mail.
    $mail->WC_Send ( $login_fullname, $tempemail,
      $tempfullname, $name, $msg, $htmlmail, $from );
    activity_log ( $eid, $WC->loginId(), $creator, LOG_NOTIFICATION,
      str_replace ( 'XXX', $app_user,
        translate ( 'Approved w/Comments by XXX.' ) ) );
  }
}
// Return to login TIMEZONE.
set_env ( 'TZ', getPref ( 'TIMEZONE' ) );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  do_redirect ( ! empty ( $ret ) && $ret == 'listall'
    ? 'list_unapproved.php'
    : ( ( ! empty ( $ret ) && $ret == 'list'
        ? 'list_unapproved.php?'
        : 'view_entry.php?eid=' . $eid . '&amp;' ) . 'user=' . $app_user ) );
  exit;
}
// Process errors.
$mail->MailError ( $mailerError, $error );

?>
