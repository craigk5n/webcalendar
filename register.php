<?php // $Id: register.php,v 1.50.2.1 2012/02/28 15:43:10 cknudsen Exp $
include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';
require_valid_referring_url ();

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include_once 'includes/access.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();

// TODO make this an option for external users.
$htmlmail = false;

load_user_preferences( 'guest' );

$WebCalendar->setLanguage();

require 'includes/classes/WebCalMailer.class';
$mail = new WebCalMailer;

$appStr  = generate_application_name();
$notauth = print_not_auth();

$error = ( empty( $ALLOW_SELF_REGISTRATION ) || $ALLOW_SELF_REGISTRATION != 'Y'
  ? $notauth : '' );

if( empty( $SELF_REGISTRATION_FULL ) || $SELF_REGISTRATION_FULL != 'Y' )
  $SELF_REGISTRATION_FULL = 'N';

$form_control = ( $SELF_REGISTRATION_FULL == 'Y' ? 'email' : 'full' );

/**
 * See if username and email are unique.
 *
 * param: $isWhat  string  What are we looking for; user login or user email?
 * param: $isWher  string  Where are we looking; "login" or "email"?
 *
 * Return true if all is OK.
 */
function checks( $isWhat, $isWher ) {
  global $control, $error;

  if( ! strlen( $isWhat ) ) {
    $error = ( $isWher == 'login'
      ? translate( 'Username cannot be blank.' )
      : translate( 'Email address cannot be blank.' ) );
    return false;
  }
  $res = dbi_execute( 'SELECT cal_' . $isWher . ' FROM webcal_user
    WHERE cal_' . $isWher . ' = ?', array( $isWhat ) );

  if( $res ) {
    $row = dbi_fetch_row( $res );

    if( $row[0] == $isWhat ) {
      $control = '';
      $error = ( $isWher == 'login'
        ? translate( 'Username already exists.' )
        : translate( 'Email address already exists.' ) );
      return false;
    }
  }
  return true;
}

/**
 *  Generate unique password.
 */
function generate_password() {
  $pass = '';
  $pass_length = 8;
  $salt = 'abchefghjkmnpqrstuvwxyz0123456789';
  srand( ( double ) microtime() * 1000000 );
  $i = 0;
  while( $i < $pass_length ) {
    $pass .= substr( $salt, rand() % 33, 1 );
    $i++;
  }
  return $pass;
}

$uemail =
$ufirstname =
$ulastname =
$upassword1 =
$upassword2 =
$user = '';

// We can limit what domain is allowed to self register.
// $self_registration_domain should have this format "192.168.220.0:255.255.240.0";
$valid_ip = validate_domain();

if( empty( $valid_ip ) )
  $error = $notauth;

// We could make $control a unique value if necessary.
$control = getPostValue( 'control' );
$illegalCharStr = translate( 'Illegal characters in login XXX.' );

if( empty( $error ) && ! empty( $control ) ) {
  $uemail     = getPostValue( 'uemail' );
  $ufirstname = getPostValue( 'ufirstname' );
  $uis_admin  = 'N';
  $ulastname  = getPostValue( 'ulastname' );
  $user       = trim( getPostValue( 'user' ) );

  if( $user != addslashes( $user ) )
    $error = str_replace( 'XXX', htmlentities( $user ), $illegalCharStr );

  // Check to make sure user doesn't already exist.
  checks( $user, 'login' );

  // Check to make sure email address doesn't already exist.
  checks( $uemail, 'email' );
}

if( empty( $error ) && ! empty( $control ) ) {
  if( $control == 'full' ) {
    // Process full account addition.
    $upassword1 = getPostValue( 'upassword1' );
    $upassword2 = getPostValue( 'upassword2' );

    // Do some checking of user info.
    if( ! empty( $user ) && ! empty( $upassword1 ) ) {
      if( get_magic_quotes_gpc() ) {
        $upassword1 = stripslashes( $upassword1 );
        $user       = stripslashes( $user );
      }
      $user = trim( $user );

      if( $user != addslashes( $user ) )
        $error = str_replace( 'XXX', htmlentities( $user ), $illegalCharStr );
    } elseif( $upassword1 != $upassword2 ) {
      $control = '';
      $error = translate( 'The passwords were not identical.' );
    }

    if( empty( $error ) ) {
      user_add_user( $user, $upassword1, $ufirstname, $ulastname,
        $uemail, $uis_admin );
      activity_log( 0, 'system', $user, LOG_NEWUSER_FULL,
        translate( 'New user via self-registration.' ) );
    }
  } elseif( $control == 'email' ) {
    // Process account info for email submission.
    // Need to generate unique passwords and email them to the new user.
    $new_pass = generate_password();
    // TODO allow admin to approve account and emails prior to processing.
    user_add_user( $user, $new_pass, $ufirstname, $ulastname,
      $uemail, $uis_admin );

    $tempName = trim( $ufirstname . ' ' . $ulastname );
    $msg = str_replace( ', XXX.',
      ( strlen( $tempName ) ? ', ' . $tempName . '.' : '.' ),
      translate( 'Hello, XXX.' ) ) . "\n\n"
     . translate( 'A new WebCalendar account has been set up for you.' )
     . "\n\n"
    . str_replace( 'XXX', $user, translate( 'Your username is XXX.' ) )
     . "\n\n"
    . str_replace( 'XXX', $new_pass, translate( 'Your password is XXX.' ) )
     . "\n\n"
    . str_replace( 'XXX', $appStr,
      translate( 'Please visit XXX to log in and start using your account!' ) )
     . "\n";

    // Add URL to event, if we can figure it out.
    if( ! empty( $SERVER_URL ) ) {
      $url = $SERVER_URL . 'login.php';

      if( $htmlmail == 'Y' )
        $url = activate_urls( $url );

      $msg .= "\n\n" . $url;
    }
    $msg .= "\n\n"
     . translate( 'You may change your password after logging in the first time.' )
     . "\n\n" . translate( 'If you received this email in error' ) . "\n\n";
    $adminStr = translate( 'Administrator', true );
    $name = $appStr . ' ' . translate( 'Welcome' ) . ': ' . $ufirstname;
    // Send via WebCalMailer class.
    $mail->WC_Send( $adminStr, $uemail, $ufirstname . ' '
       . $ulastname, $name, $msg, $htmlmail, $EMAIL_FALLBACK_FROM );
    activity_log( 0, 'system', $user, LOG_NEWUSER_EMAIL,
      translate( 'New user via email.' ) );
  }
}

echo send_doctype( $appStr ) . '
    <!--[if IE 5]><script src="includes/js/ie5.js"></script><![endif]-->
    <script src="includes/js/prototype.js"></script>
    <script>
      var
        validform = false,
        xlate = [];

      xlate[\'inputPassword\']   = \''
 . translate( 'You have not entered a password.', true ) . '\',
      xlate[\'noBlankUsername\'] = \''
 . translate( 'Username cannot be blank.', true ) . '\',
      xlate[\'passwordsNoMatch\'] = \''
 . translate( 'The passwords were not identical.', true ) . '\';
    </script>
    <script src="includes/js/register.js"></script>
    <link href="css_cacher.php?login=__public__" rel="stylesheet" />
    <link href="includes/css/styles.css" rel="stylesheet" />'

// Print custom header (since we do not call print_header function).
 . ( ! empty( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y'
  ? load_template( $login, 'S' ) : '' ) . '
  </head>
  <body id="register">
    <h2>' . $appStr . ' ' . translate( 'Registration' ) . '</h2>'
 . ( ! empty( $error )
  ? '
    <span style="color:#FF0000; font-weight:bold;">' . translate( 'Error' )
   . ": $error" . '</span><br />'
  : '<br /><br />' . ( empty( $control ) ? '' : '
    <form action="login.php" method="post">
      <input type="hidden" name="login" value="' . $user . '" />
      <table class="aligncenter" cellspacing="10" cellpadding="10">
        <tr>
          <td rowspan="3"><img src="images/register.gif"></td>
          <td>' . translate( 'Welcome to WebCalendar' ) . '</td>
        </tr>' . ( $SELF_REGISTRATION_FULL == 'Y' ? '
        <tr>
          <td colspan="3" class="aligncenter"><label>'
       . translate( 'Your email should arrive shortly.' ) . '</label></td>
        </tr>' : '' ) . '
        <tr>
          <td colspan="3" class="aligncenter"><input type="submit" value="'
     . translate( 'Return to Login screen' ) . '" /></td>
        </tr>
      </table>
    </form>' ) . '
    <form action="register.php" method="post" onSubmit="return valid_form()"
        name="selfreg">
      <input type="hidden" name="control" value="' . $form_control . '" />
      <table class="aligncenter" cellpadding="10" cellspacing="10">
        <tr>
          <td rowspan="3"><img src="images/register.gif" alt="" /></td>
          <td class="alignright"><label class="colon">' . translate( 'Username' ) . '</label></td>
          <td class="alignleft"><input type="text" id="user" name="user" value="'
   . $user . '" size="20" maxlength="20" onChange="check_name();" /></td>
        </tr>
        <tr>
          <td class="alignright"><label class="colon">' . translate( 'First Name' ) . '</label></td>
          <td class="alignleft"><input type="text" name="ufirstname" value="'
   . $ufirstname . '" size="25" maxlength="25" /></td>
        </tr>
        <tr>
          <td class="alignright"><label class="colon">' . translate( 'Last Name' ) . '</label></td>
          <td class="alignleft"><input type="text" name="ulastname" value="'
   . $ulastname . '" size="25" maxlength="25" /></td>
        </tr>
        <tr>
          <td class="alignright" colspan="2"><label class="colon">' . translate( 'E-mail address' ) . '</label></td>
          <td class="alignleft"><input type="text" name="uemail" id="uemail" value="'
   . $uemail . '" size="40" maxlength="75" onChange="check_uemail();" /></td>
        </tr>
        <tr>
          <td ' . ( $SELF_REGISTRATION_FULL != 'Y'
    ? 'class="alignright" colspan="2"><label class="colon">' . translate( 'Password' ) . '</label></td>
          <td class="alignleft"><input name="upassword1" value="' . $upassword1
     . '" size="15" type="password" /></td>
        </tr>
        <tr>
          <td class="alignright" colspan="2"><label>'
           . translate( 'Password (again)' ) . '</label></td>
          <td class="alignleft"><input name="upassword2" value="' . $upassword2
     . '" size="15" type="password" />'
    : 'colspan="3" class="aligncenter"><label>'
     . translate( 'Your account information will be emailed to you.' )
     . '</label>' ) . '</td>
        </tr>
        <tr>
          <td colspan="3" class="aligncenter"><input type="submit" value="'
   . translate( 'Submit' ) . '" /></td>
        </tr>
      </table>
    </form>' ) . '<br /><br /><br /><br /><br /><br /><br /><br />
    <span class="cookies">' . translate( 'cookies-note' )
 . '</span><br />
    <hr />
    <br /><br />
    <a href="' . $PROGRAM_URL . '" id="programname">' . $PROGRAM_NAME . '</a>';
// Print custom trailer (since we do not call print_trailer function).
if( ! empty( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y' ) {
  $res = dbi_execute( 'SELECT cal_template_text FROM webcal_report_template
    WHERE cal_template_type = \'T\' and cal_report_id = 0' );
  if( $res ) {
    if( $row = dbi_fetch_row( $res ) )
      echo $row[0];

    dbi_free_result( $res );
  }
}

?>
  </body>
</html>
