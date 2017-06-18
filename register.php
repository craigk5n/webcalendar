<?php
/* $Id: register.php,v 1.36.2.5 2011/04/27 00:27:35 rjones6061 Exp $ */
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase ();

include 'includes/' . $user_inc;
include_once 'includes/access.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase ();

load_global_settings ();

// TODO make this an option for external users.
$htmlmail = false;

load_user_preferences ( 'guest' );

$WebCalendar->setLanguage ();

require ( 'includes/classes/WebCalMailer.class' );
$mail = &new WebCalMailer;

$appStr = generate_application_name ();

$error = ( empty ( $ALLOW_SELF_REGISTRATION ) || $ALLOW_SELF_REGISTRATION != 'Y'
  ? print_not_auth (26) : '' );

if ( empty ( $SELF_REGISTRATION_FULL ) || $SELF_REGISTRATION_FULL != 'Y' )
  $SELF_REGISTRATION_FULL = 'N';

$form_control = ( $SELF_REGISTRATION_FULL == 'Y' ? 'email' : 'full' );

/* See if new username is unique.
 *
 * Return true if all is OK.
 */
function check_username ( $user ) {
  global $control, $error;

  if ( strlen ( $user ) == 0 ) {
    $error = translate ( 'Username cannot be blank.' );
    return false;
  }
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user WHERE cal_login = ?',
    array ( $user ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $user ) {
      $control = '';
      $error = translate ( 'Username already exists.' );
      return false;
    }
  }
  return true;
}

/* See if  email is unique.
 *
 * Return true if all is OK.
 */
function check_email ( $uemail ) {
  global $control, $error;

  if ( ! strlen ( $uemail ) ) {
    $error = translate ( 'Email address cannot be blank.' );
    return false;
  }
  $res = dbi_execute ( 'SELECT cal_email FROM webcal_user WHERE cal_email = ?',
    array ( $uemail ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $uemail ) {
      $control = '';
      $error = translate ( 'Email address already exists.' );
      return false;
    }
  }
  return true;
}

/* Generate unique password.
 */
function generate_password () {
  $pass = '';
  $pass_length = 8;
  $salt = 'abchefghjkmnpqrstuvwxyz0123456789';
  srand ( ( double ) microtime () * 1000000 );
  $i = 0;
  while ( $i < $pass_length ) {
    $pass .= substr ( $salt, rand () % 33, 1 );
    $i++;
  }
  return $pass;
}

$uemail = $ufirstname = $ulastname = $upassword1 = $upassword2 = $user = '';

// We can limit what domain is allowed to self register.
// $self_registration_domain should have this format "192.168.220.0:255.255.240.0";
$valid_ip = validate_domain ();
if ( empty ( $valid_ip ) )
  $error = print_not_auth (36);

// We could make $control a unique value if necessary.
$control = getPostValue ( 'control' );
if ( empty ( $error ) && ! empty ( $control ) ) {
  $uemail = getPostValue ( 'uemail' );
  $ufirstname = getPostValue ( 'ufirstname' );
  $uis_admin = 'N';
  $ulastname = getPostValue ( 'ulastname' );
  $user = trim ( getPostValue ( 'user' ) );
  // translate ( 'Illegal characters in login' )
  if ( $user != addslashes ( $user ) )
    $error = str_replace ( 'XXX', htmlentities ( $user ),
      translate ( 'Illegal characters in login XXX.' ) );

  // Check to make sure user doesn't already exist.
  check_username ( $user );

  // Check to make sure email address doesn't already exist.
  check_email ( $uemail );
}

if ( empty ( $error ) && ! empty ( $control ) ) {
  if ( $control == 'full' ) {
    // Process full account addition.
    $upassword1 = getPostValue ( 'upassword1' );
    $upassword2 = getPostValue ( 'upassword2' );
    // Do some checking of user info.
    if ( ! empty ( $user ) && ! empty ( $upassword1 ) ) {
      if ( get_magic_quotes_gpc () ) {
        $upassword1 = stripslashes ( $upassword1 );
        $user = stripslashes ( $user );
      }
      $user = trim ( $user );
      if ( $user != addslashes ( $user ) )
        $error = str_replace ( 'XXX', htmlentities ( $user ),
          translate ( 'Illegal characters in login XXX.' ) );
    } else
    if ( $upassword1 != $upassword2 ) {
      $control = '';
      $error = translate ( 'The passwords were not identical.' );
    }

    if ( empty ( $error ) ) {
      user_add_user ( $user, $upassword1, $ufirstname, $ulastname,
        $uemail, $uis_admin );
      activity_log ( 0, 'system', $user, LOG_NEWUSER_FULL,
        translate ( 'New user via self-registration.' ) );
    }
  } elseif ( $control == 'email' ) {
    // Process account info for email submission.
    // Need to generate unique passwords and email them to the new user.
    $new_pass = generate_password ();
    // TODO allow admin to approve account and emails prior to processing.
    user_add_user ( $user, $new_pass, $ufirstname, $ulastname,
      $uemail, $uis_admin );

    $tempName = trim ( $ufirstname . ' ' . $ulastname );
    $msg = str_replace ( ', XXX.',
      ( strlen ( $tempName ) ? ', ' . $tempName . '.' : '.' ),
      translate ( 'Hello, XXX.' ) ) . "\n\n"
     . translate ( 'A new WebCalendar account has been set up for you.' )
     . "\n\n"
    // translate ( 'Your username is' )
    . str_replace ( 'XXX', $user, translate ( 'Your username is XXX.' ) )
     . "\n\n"
    // translate ( 'Your password is' )
    . str_replace ( 'XXX', $new_pass, translate ( 'Your password is XXX.' ) )
     . "\n\n"
    // translate ( 'Please visit' )
    // translate ( 'to log in and start using your account' )
    . str_replace ( 'XXX', $appStr,
      translate ( 'Please visit XXX to log in and start using your account!' ) )
     . "\n";
    // Add URL to event, if we can figure it out.
    if ( ! empty ( $SERVER_URL ) ) {
      $url = $SERVER_URL . 'login.php';
      if ( $htmlmail == 'Y' )
        $url = activate_urls ( $url );

      $msg .= "\n\n" . $url;
    }
    $msg .= "\n\n"
     . translate ( 'You may change your password after logging in the first time.' )
     . "\n\n" . translate ( 'If you received this email in error' ) . "\n\n";
    $adminStr = translate ( 'Administrator', true );
    $name = $appStr . ' ' . translate ( 'Welcome' ) . ': ' . $ufirstname;
    // Send  via WebCalMailer class.
    $mail->WC_Send ( $adminStr, $uemail, $ufirstname . ' '
       . $ulastname, $name, $msg, $htmlmail, $EMAIL_FALLBACK_FROM );
    activity_log ( 0, 'system', $user, LOG_NEWUSER_EMAIL,
      translate ( 'New user via email.' ) );
  }
}

echo send_doctype ( $appStr );
echo '
    <script type="text/javascript" src="includes/js/prototype.js"></script>
    <script type="text/javascript">
      var validform = false;

      function valid_form () {
        if ( document.selfreg.upassword1.value.length == 0 ) {
          alert ( "'
           . translate ( 'You have not entered a password.', true ) . '" );
          return false;
        }
        if ( document.selfreg.user.value.length == 0 ) {
          alert ( "' . translate ( 'Username cannot be blank.', true ) . '" );
          return false;
        }
        if ( document.selfreg.upassword1.value != document.selfreg.upassword2.value ) {
          alert ( "'
           . translate ( 'The passwords were not identical.', true ) . '" );
          return false;
        }

        checkers ( \'user\', \'register\' );
        checkers ( \'uemail\', \'email\' );

        return validform;
      }

      function checkers ( formfield, params ) {
        var ajax = new Ajax.Request ( \'ajax.php\',
          {method: \'post\',
          parameters: \'page=\' + params + \'&name=\' + $F ( formfield ),
          onComplete: showResponse} );
      }

      function showResponse ( originalRequest ) {
        if ( originalRequest.responseText ) {
          text = originalRequest.responseText;
          '// This causes javascript errors in Firefox, but these can be ignored.
 . 'alert ( text );
          if ( formfield == \'user\' )
            document.selfreg.user.focus ();

          if ( formfield == \'uemail\' )
            document.selfreg.uemail.focus ();

          validform = false;
        } else {
          validform =  true;
        }
      }
    </script>
    <link rel="stylesheet" type="text/css" href="css_cacher.php?login=__public__" />'

// Print custom header (since we do not call print_header function).
 . ( ! empty ( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y'
  ? load_template ( $login, 'S' ) : '' ) . '
  </head>
  <body id="register">
    <h2>' . $appStr . ' ' . translate ( 'Registration' ) . '</h2>'
 . ( ! empty ( $error )
  ? '
    <span style="color:#FF0000; font-weight:bold;">' . translate ( 'Error' )
   . ": $error" . '</span><br />'
  : '<br /><br />' . ( empty ( $control ) ? '' : '
    <form action="login.php" method="post">
      <input type="hidden" name="login" value="' . $user . '" />
      <table align="center" cellspacing="10" cellpadding="10">
        <tr>
          <td rowspan="3"><img src="images/register.gif"></td>
          <td>' . translate ( 'Welcome to WebCalendar' ) . '</td>
        </tr>' . ( $SELF_REGISTRATION_FULL == 'Y' ? '
        <tr>
          <td colspan="3" align="center"><label>'
       . translate ( 'Your email should arrive shortly.' ) . '</label></td>
        </tr>' : '' ) . '
        <tr>
          <td colspan="3" align="center"><input type="submit" value="'
     . translate ( 'Return to Login screen' ) . '" /></td>
        </tr>
      </table>
    </form>' ) . '
    <form action="register.php" method="post" onSubmit="return valid_form()"
        name="selfreg">
      <input type="hidden" name="control" value="' . $form_control . '" />
      <table align="center" cellpadding="10" cellspacing="10">
        <tr>
          <td rowspan="3"><img src="images/register.gif" alt="" /></td>
          <td align="right"><label>' . translate ( 'Username' ) . ':</label></td>
          <td align="left"><input type="text" name="user" id="user" value="'
   . $user . '" size="20" maxlength="20" onChange="check_name();" /></td>
        </tr>
        <tr>
          <td align="right"><label>' . translate ( 'First Name' )
   . ':</label></td>
          <td align="left"><input type="text" name="ufirstname" value="'
   . $ufirstname . '" size="25" maxlength="25" /></td>
        </tr>
        <tr>
          <td align="right"><label>' . translate ( 'Last Name' ) . ':</label></td>
          <td align="left"><input type="text" name="ulastname" value="'
   . $ulastname . '" size="25" maxlength="25" /></td>
        </tr>
        <tr>
          <td align="right" colspan="2"><label>' . translate ( 'E-mail address' )
   . ':</label></td>
          <td align="left"><input type="text" name="uemail" id="uemail" value="'
   . $uemail . '" size="40" maxlength="75" onChange="check_uemail();" /></td>
        </tr>
        <tr>
          <td ' . ( $SELF_REGISTRATION_FULL != 'Y'
    ? 'align="right" colspan="2"><label>' . translate ( 'Password' )
     . ':</label></td>
          <td align="left"><input name="upassword1" value="' . $upassword1
     . '" size="15" type="password" /></td>
        </tr>
        <tr>
          <td align="right" colspan="2"><label>'
           . translate ( 'Password (again)' )     . ':</label></td>
          <td align="left"><input name="upassword2" value="' . $upassword2
     . '" size="15" type="password" />'
    : 'colspan="3" align="center"><label>'
     . translate ( 'Your account information will be emailed to you.' )
     . '</label>' ) . '</td>
        </tr>
        <tr>
          <td colspan="3" align="center"><input type="submit" value="'
   . translate ( 'Submit' ) . '" /></td>
        </tr>
      </table>
    </form>' ) . '<br /><br /><br /><br /><br /><br /><br /><br />
    <span class="cookies">' . translate ( 'cookies-note' )
 . '</span><br />
    <hr />
    <br /><br />
    <a href="' . $PROGRAM_URL . '" id="programname">' . $PROGRAM_NAME . '</a>';
// Print custom trailer (since we do not call print_trailer function).
if ( ! empty ( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y' ) {
  $res = dbi_execute ( 'SELECT cal_template_text FROM webcal_report_template
    WHERE cal_template_type = \'T\' and cal_report_id = 0' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      echo $row[0];

    dbi_free_result ( $res );
  }
}

?>
 </body>
</html>
