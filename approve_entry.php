<?php
include_once 'includes/init.php';
require_valid_referring_url ();
require ( 'includes/classes/WebCalMailer.class' );

$error = '';

if ( $readonly == 'Y' )
  $error = print_not_auth();
// Give user a chance to add comments to approval email.
if ( getPostValue( 'comments' ) !== null ) {
  $comments = getPostValue ( 'comments' );
  $cancel = getPostValue ( 'cancel' );
} else
if ( empty ( $ret ) ) {
  $q_string = ( ! empty ( $_SERVER['QUERY_STRING'] )
    ? '?' . $_SERVER['QUERY_STRING'] : '' );

  print_header();
  echo '
    <form action="approve_entry.php' . $q_string
   . '" method="post" name="add_comments">
      <table cellspacing="5">
        <tr>
          <td class="aligncenter alignbottom"><h3>'
   . translate ( 'Additional Comments (optional)' ) . '</h3></td>
        <tr>
        <tr>
          <td class="aligncenter"><textarea name="comments" rows="5" '
   . 'cols="60"></textarea></td>
        </tr>
        <tr>
          <td class="aligncenter">
            <input type="submit" value="' . translate ( 'Approve and Send' )
   . '" />&nbsp;&nbsp;&nbsp;
            <input type="submit" id="cancel" name="cancel" value="'
   . translate( 'Approve and Exit' ) . '" />
          </td>
        </tr>
        <tr>
          <td>'
  . translate ( '(Your comments will be emailed to the event creator.)' ) . '</td>
        </tr>
      </table>
    </form>
  </body>
</html>
';
  exit;
}

$user = getValue ( 'user' );
$type = getValue ( 'type' );
$id = getValue ( 'id' );

// Allow administrators to approve public events.
$app_user = ( $PUBLIC_ACCESS == 'Y' && ! empty ( $public ) && $is_admin
  ? '__public__' : ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
// If User Access Control is enabled, we check to see if they are
// allowed to approve for the specified user.
if ( access_is_enabled() && ! empty ( $user ) && $user != $login &&
    access_user_calendar ( 'approve', $user ) )
  $app_user = $user;

if ( empty ( $error ) && $id > 0 )
  update_status ( 'A', $app_user, $id, $type );

if ( ! empty ( $comments ) && empty ( $cancel ) ) {
  $mail = new WebCalMailer;
  // Email event creator to notify that it was approved with comments.
  // Get the name of the event.
  $res = dbi_execute ( 'SELECT cal_name, cal_description, cal_date, cal_time, cal_create_by
  FROM webcal_entry
  WHERE cal_id = ?', [$id] );
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
  // TODO figure out if creator wants approved comment email.
  // Check UAC.
  $send_user_mail = ( access_is_enabled()
    ? access_user_calendar ( 'email', $creator, $login ) : 'Y' );

  $htmlmail = get_pref_setting ( $creator, 'EMAIL_HTML' );
  user_load_variables ( $creator, 'temp' );
  $user_TIMEZONE = get_pref_setting ( $creator, 'TIMEZONE' );
  set_env ( 'TZ', $user_TIMEZONE );
  $user_language = get_pref_setting ( $creator, 'LANGUAGE' );
  if ( $send_user_mail == 'Y' && strlen ( $tempemail ) && $SEND_EMAIL != 'N' ) {
    reset_language ( empty ( $user_language ) || ( $user_language == 'none' )
      ? $LANGUAGE : $user_language );

    $msg = str_replace ( 'XXX', $tempfullname, translate ( 'Hello, XXX.' ) )
    . "\n\n" . str_replace ( 'XXX', $login_fullname,
      translate ( 'XXX has approved an appointment and added comments.' ) ) . "\n\n"
    . str_replace ( 'XXX', $name, translate ( 'Subject XXX' ) ) . "\n"
    . str_replace ( 'XXX', $description, translate ( 'Description XXX' ) ) . "\n"
    . str_replace ( 'XXX', date_to_str ( $fmtdate ), translate ( 'Date XXX' ) )
    . ' ' . ( empty ( $hour ) && empty ( $minute )
      ? '' : str_replace ( 'XXX',
        // Display using user's GMT offset and display TZID.
        display_time ( '', 2, $eventstart,
          get_pref_setting ( $creator, 'TIME_FORMAT' ) ),
        translate ( 'Time XXX' ) ) ) . "\n";

    if ( ! empty ( $SERVER_URL ) ) {
      // DON'T change & to &amp; here. email will handle it
      $url = $SERVER_URL . 'view_entry.php?id=' . $id . '&em=1';
      if ( $htmlmail == 'Y' )
        $url = activate_urls ( $url );

      $msg .= "\n" . $url;
    }
    if ( ! empty ( $comments ) )
      $msg .= "\n\n" . str_replace ( 'XXX', $comments,
        translate ( 'Comments XXX' ) );

    $from = ( strlen ( $login_email ) ? $login_email : $EMAIL_FALLBACK_FROM );
    // Send mail.
    $mail->WC_Send ( $login_fullname, $tempemail,
      $tempfullname, $name, $msg, $htmlmail, $from );
    activity_log ( $id, $login, $creator, LOG_NOTIFICATION,
      str_replace ( 'XXX', $app_user,
        translate ( 'Approved w/Comments by XXX.' ) ) );
  }
}
// Return to login TIMEZONE.
set_env ( 'TZ', $TIMEZONE );
if ( empty ( $error ) && empty ( $mailerError ) ) {
  do_redirect ( ! empty ( $ret ) && $ret == 'listall'
    ? 'list_unapproved.php'
    : ( ( ! empty ( $ret ) && $ret == 'list'
        ? 'list_unapproved.php?'
        : 'view_entry.php?id=' . $id . '&amp;' ) . 'user=' . $app_user ) );
  exit;
}
// Process errors.
$mail->MailError ( $mailerError, $error );

?>
